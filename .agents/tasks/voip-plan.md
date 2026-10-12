# VoIP/Telephony Module — Implementation Plan

## 1. Selected Package and Justification

**`twilio/twilio-php`** — [https://packagist.org/packages/twilio/twilio-php](https://packagist.org/packages/twilio/twilio-php)

- **License:** Twilio Helper Library License (permissive, functionally MIT-equivalent for commercial use)
- **Maintenance:** Actively maintained by Twilio; releases up to 2025, auto-generated from OpenAPI. Last commit within days of writing.
- **PHP compatibility:** Explicitly supports PHP 8.2, 8.3, 8.4 per README. No Laravel framework dependency — pure PHP SDK. Drops cleanly into Laravel 12.
- **Features covered:** Outbound call initiation (`calls->create()`), call status webhooks (TwiML + status callbacks), call detail fetch (`calls($sid)->fetch()`), recording metadata (`recordings->read(['callSid'=>...])`), webhook HMAC signature verification (`Twilio\Security\RequestValidator`).

**Rejected alternatives:**

| Package | Reason for rejection |
|---|---|
| `aloha/twilio` | Last updated ~2019 (Laravel 5-era), no Laravel 10+ support, no PHP 8 compatibility claims, effectively abandoned. |
| `team-telnyx/telnyx-php` | PHP 8.1+ compatible per README, but Telnyx is a less common provider; requires additional Telnyx account setup vs. Twilio which is the de-facto standard. No concrete advantage over official Twilio SDK for this project. |
| `vonage/vonage-php-sdk` | Actively maintained and PHP 8.3 compatible, but Vonage's voice API has less documentation coverage, more complex JWT auth setup, and no existing precedent in this codebase. |
| `marcelog/pami` | Asterisk AMI only; requires self-hosted Asterisk PBX. This project has no Asterisk infrastructure and prefers cloud provider. |

**Decision:** `twilio/twilio-php` is the single best choice — actively maintained, PHP 8.3 tested, requires only a Twilio account (no self-hosted infrastructure), covers all required features, and `guzzlehttp/guzzle ^7.8` (already installed) satisfies its HTTP dependency.

---

## 2. Module Structure

```
packages/Webkul/Telephony/
├── composer.json
└── src/
    ├── Contracts/
    │   └── CallLog.php
    ├── Config/
    │   ├── acl.php
    │   ├── core_config.php
    │   └── menu.php
    ├── Database/
    │   └── Migrations/
    │       └── YYYY_MM_DD_HHMMSS_create_call_logs_table.php
    ├── DataGrids/
    │   └── CallLogDataGrid.php
    ├── Http/
    │   └── Controllers/
    │       ├── CallLogController.php
    │       ├── SettingsController.php
    │       └── WebhookController.php
    ├── Models/
    │   ├── CallLog.php
    │   └── CallLogProxy.php
    ├── Providers/
    │   ├── EventServiceProvider.php
    │   ├── ModuleServiceProvider.php
    │   └── TelephonyServiceProvider.php
    ├── Repositories/
    │   └── CallLogRepository.php
    ├── Resources/
    │   ├── lang/
    │   │   └── en/
    │   │       └── app.php
    │   └── views/
    │       ├── call-logs/
    │       │   └── index.blade.php
    │       ├── persons/
    │       │   ├── call-history.blade.php
    │       │   └── click-to-call.blade.php
    │       └── settings/
    │           └── index.blade.php
    ├── Routes/
    │   └── web.php
    └── Services/
        ├── TelephonyService.php
        ├── TenantTelephonyRouter.php
        └── TwilioClientFactory.php
```

**Also added (not in package):**
- `database/migrations/landlord/YYYY_..._create_tenant_telephony_routes_table.php` — landlord DB only
- `bootstrap/providers.php` — append `Webkul\Telephony\Providers\TelephonyServiceProvider::class`

---

## 3. Database Schema

### Tenant migration: `call_logs` table
Location: `packages/Webkul/Telephony/src/Database/Migrations/` — picked up by `loadMigrationsFrom()` in `TelephonyServiceProvider::boot()`, so it runs when `TenantMaintenanceCommand` calls `Artisan::call('migrate', ['--database' => 'tenant'])` after `$tenant->makeCurrent()`.

```
call_logs
├── id                  bigIncrements
├── provider_call_id    varchar(191) UNIQUE NOT NULL   (Twilio CallSid)
├── direction           enum('inbound','outbound') NOT NULL
├── status              varchar(50) NOT NULL            (initiated, ringing, answered, completed, failed, busy, no-answer)
├── from_number         varchar(30) NOT NULL
├── to_number           varchar(30) NOT NULL
├── contact_id          bigint unsigned NULLABLE        FK → persons.id onDelete('set null')
├── lead_id             bigint unsigned NULLABLE        FK → leads.id onDelete('set null')
├── user_id             bigint unsigned NOT NULL        FK → users.id onDelete('cascade')  (agent who initiated / received)
├── duration_seconds    int NULLABLE
├── recording_url       text NULLABLE
├── recording_sid       varchar(191) NULLABLE
├── started_at          timestamp NULLABLE
├── ended_at            timestamp NULLABLE
├── provider_payload    json NULLABLE                   (raw Twilio status callback payload)
├── created_at          timestamp
└── updated_at          timestamp
```

Index: `(contact_id, started_at)` for person call history queries.

### Landlord migration: `tenant_telephony_routes` table
Location: `database/migrations/landlord/` — runs only against the landlord DB.

```
tenant_telephony_routes
├── id              bigIncrements
├── tenant_id       bigint FK → tenants.id cascadeOnDelete
├── twilio_phone_number  varchar(30) UNIQUE NOT NULL   (+E.164)
├── is_active       boolean default true
├── created_at      timestamp
└── updated_at      timestamp
```

---

## 4. Multi-Tenancy Approach

### Webhook → Tenant routing
Twilio sends all inbound call webhooks to a **single root-domain URL** (e.g. `https://platform.example.com/telephony/webhook`), not to tenant subdomains. The `WebhookController::handle()` method:
1. Validates the HMAC signature using `Twilio\Security\RequestValidator` with the platform-level auth token (env-level, not per-tenant, because the webhook arrives before tenant context is established). Abort 403 on failure.
2. Extracts the `To` phone number from the POST payload.
3. Queries `tenant_telephony_routes` on the **landlord** DB: `WHERE twilio_phone_number = $to AND is_active = 1`.
4. Calls `$tenant->execute(fn() => ...)` (Spatie's `makeCurrent` + `forgetCurrent`) to run all business logic (CallLog upsert, event dispatch) within the tenant's database context.

This is the same pattern as `CentralWebhookRouter` + `UnifiedInboxWebhookController` in this codebase.

### Per-tenant credentials
Stored in the `core_config` table (tenant DB) under codes:
- `telephony.credentials.account_sid`
- `telephony.credentials.auth_token`
- `telephony.credentials.from_number`
- `telephony.credentials.status_callback_base_url` (optional override)

`TwilioClientFactory` reads from `CoreConfigRepository` first; falls back to env vars `TWILIO_ACCOUNT_SID` / `TWILIO_AUTH_TOKEN` / `TWILIO_FROM_NUMBER` if no tenant-level config is set. This allows a single shared Twilio account for a simple deployment while enabling per-tenant override.

**Auth token is never returned to the frontend.** The settings form shows a placeholder ("saved" indicator) instead of pre-filling the stored token.

**Webhook signature validation note:** The platform-level (global env) auth token is used for webhook HMAC validation because the webhook arrives at the root URL before tenant context is known. Per-tenant auth tokens are used for outbound API calls made within tenant context.

### Jobs
`queues_are_tenant_aware_by_default=true` is already set. Any queued jobs dispatched from within a tenant context automatically carry the tenant ID via Spatie's `MakeQueueTenantAwareAction`. No `TenantAware` interface implementation needed for Telephony jobs.

### Cross-tenant access prevention
- `CallLogController::recordingProxy()` fetches the `CallLog` through the current tenant's `CallLogRepository` (which only sees the tenant DB). No cross-tenant ID lookup is possible.
- Bouncer permission checks guard all controller actions.
- The webhook controller never trusts a user-supplied tenant ID; it resolves the tenant from the cryptographically-validated `To` number in the landlord routing table.

---

## 5. Service Layer Design

```
TelephonyService
├── initiateCall(string $to, string $from, string $statusCallbackUrl, int $userId): CallLog
│   └── Uses: $twilioClient->calls->create($to, $from, ['statusCallback'=>..., 'url'=>twiml_url])
│          + CallLogRepository::create([direction=outbound, status=initiated, ...])
│
├── getCallDetails(string $callSid): array
│   └── Uses: $twilioClient->calls($callSid)->fetch() → returns Twilio CallInstance properties
│
├── fetchRecordingMetadata(string $callSid): array
│   └── Uses: $twilioClient->recordings->read(['callSid'=>$callSid]) → array of RecordingInstance
│
└── parseAndValidateWebhook(Request $request): array
    └── Uses: Twilio\Security\RequestValidator->validate($url, $params, $signature)
           Throws 403 if invalid; returns $request->all() if valid.

TwilioClientFactory
└── make(): Twilio\Rest\Client
    └── Reads credentials from CoreConfigRepository (tenant context) or env fallback.

TenantTelephonyRouter
├── resolveTenant(string $toNumber): ?Tenant
│   └── Queries landlord DB: tenant_telephony_routes WHERE twilio_phone_number=$toNumber
├── registerRoute(int $tenantId, string $phoneNumber): void
└── removeRoute(int $tenantId, string $phoneNumber): void
```

---

## 6. UI Integration Plan

**No core Contact/Person files are modified.** Integration uses `view_render_event` hooks, which are already built into the Admin Blade views.

### Click-to-call button
Hook: `admin.contact.persons.view.actions.after` (fires inside the action buttons row on the Person detail page — see `packages/Webkul/Admin/src/Resources/views/contacts/persons/view.blade.php` line ~48).

`EventServiceProvider` listens to this event and injects `telephony::persons.click-to-call` template. The partial:
- Receives `$person` via `$viewRenderManager->getParams()['person']`.
- Shows a phone icon button for each entry in `$person->contact_numbers`.
- On click: Alpine.js `fetch()` POST to `admin.telephony.call_logs.store` with `{to: number, contact_id: person.id}`.
- Disabled/hidden when `core()->getConfigData('telephony.credentials.account_sid')` is empty.

### Call history panel
Hook: `admin.contact.persons.view.right.before` (fires at the top of the right-side activity panel on the Person view).

`EventServiceProvider` injects `telephony::persons.call-history` template. The partial:
- Fetches last 10 calls via Alpine.js `init()` → GET `admin.telephony.call_logs.index?filters[contact_id][eq]=$personId&per_page=10`.
- Renders a simple table: direction badge, status pill, duration, start time, agent name.
- Link to full call log filtered by contact.

### Standalone call log page
`/admin/telephony/call-logs` — full DataGrid page accessible from the `telephony` menu item.

---

## 7. Ordered Implementation Steps

- [ ] 1. Install `twilio/twilio-php` via Composer.
      `composer require twilio/twilio-php`
      Verify: `composer install` completes, `php artisan test --compact` still green.

- [ ] 2. Create the package directory tree and `composer.json` stub for `packages/Webkul/Telephony/`.
      Files: `packages/Webkul/Telephony/composer.json`, all `src/` subdirectories.
      Verify: directories exist; `composer dump-autoload` resolves `Webkul\Telephony\` namespace.

- [ ] 3. Create `CallLog` contract, model, proxy, repository.
      Files: `src/Contracts/CallLog.php`, `src/Models/CallLog.php`, `src/Models/CallLogProxy.php`, `src/Repositories/CallLogRepository.php`.
      Verify: classes load without syntax errors (`php artisan tinker` can `new \Webkul\Telephony\Models\CallLog`).

- [ ] 4. Create `ModuleServiceProvider` and `TelephonyServiceProvider`. Register provider in `bootstrap/providers.php`.
      Files: `src/Providers/ModuleServiceProvider.php`, `src/Providers/TelephonyServiceProvider.php`, `bootstrap/providers.php`.
      Verify: `php artisan route:list` completes without exception.

- [ ] 5. Create Config files: `menu.php`, `acl.php`, `core_config.php`.
      Files: `src/Config/menu.php`, `src/Config/acl.php`, `src/Config/core_config.php`.
      Verify: Telephony menu item appears in admin navigation (or `config('menu.admin')` contains it after `php artisan config:clear`).

- [ ] 6. Create the tenant migration for `call_logs` and the landlord migration for `tenant_telephony_routes`.
      Files: `src/Database/Migrations/XXXX_create_call_logs_table.php`, `database/migrations/landlord/XXXX_create_tenant_telephony_routes_table.php`.
      Verify: `php artisan migrate --database=landlord` runs the landlord migration cleanly; tenant migration can be run via `TenantMaintenanceCommand`.

- [ ] 7. Create `TwilioClientFactory`, `TelephonyService`, `TenantTelephonyRouter`.
      Files: `src/Services/TwilioClientFactory.php`, `src/Services/TelephonyService.php`, `src/Services/TenantTelephonyRouter.php`.
      Verify: classes resolve from container without errors.

- [ ] 8. Create `WebhookController`, `CallLogController`, `SettingsController`, and `Routes/web.php`.
      Files: all three controllers, `src/Routes/web.php`.
      Verify: `php artisan route:list --name=telephony` shows all expected routes.

- [ ] 9. Create `CallLogDataGrid`.
      Files: `src/DataGrids/CallLogDataGrid.php`.
      Verify: instantiating the DataGrid class does not throw.

- [ ] 10. Create Blade views: call-logs/index, settings/index, persons/click-to-call, persons/call-history. Create `Resources/lang/en/app.php`.
       Files: all 4 view files, `src/Resources/lang/en/app.php`.
       Verify: `php artisan view:clear` succeeds; visiting the routes in a browser (or `Route::getRoutes()` check) shows no compilation errors.

- [ ] 11. Create `EventServiceProvider`, register it in `TelephonyServiceProvider::boot()`.
       Files: `src/Providers/EventServiceProvider.php`, update `src/Providers/TelephonyServiceProvider.php`.
       Verify: the contact/person view page renders without errors and contains the click-to-call partial (visible in page source).

- [ ] 12. Write Pest feature tests.
       Files: `packages/Webkul/Telephony/tests/Feature/WebhookControllerTest.php`, `TenantIsolationTest.php`, `CallLogControllerTest.php`, `TelephonyServiceTest.php`.
       Verify: `php artisan test --compact` — all new tests pass, no existing tests broken.

- [ ] 13. Run `./vendor/bin/pint` and fix any formatting issues.
       Verify: `./vendor/bin/pint --test` exits 0.

- [ ] 14. Update `CHANGELOG.md` with a single consolidated entry under `## Unreleased`.

---

## 8. Environment Variables Required

```dotenv
# Twilio provider credentials (global fallback; can be overridden per-tenant via Settings → Telephony)
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_FROM_NUMBER=+15551234567

# Optional: override the base URL used to build the status callback URL
# Defaults to APP_URL. Set this if the platform runs behind a load balancer
# and the webhook URL must point to the root domain (not a tenant subdomain).
TWILIO_STATUS_CALLBACK_BASE_URL=https://platform.example.com
```

**Security note:** `TWILIO_AUTH_TOKEN` must never appear in logs, responses, or version control. Per-tenant auth tokens stored in `core_config` are encrypted at rest if Laravel's `APP_KEY` rotation is properly managed; the column should be treated as sensitive.

---

## 9. Infrastructure Still Required

The following must be configured by the operator **outside this codebase** before live calling works:

1. **Twilio account** — Sign up at twilio.com. Obtain Account SID and Auth Token from the Console Dashboard.

2. **Twilio phone number** — Purchase or port a phone number in the Twilio Console. This number becomes `TWILIO_FROM_NUMBER` (outbound caller ID) and the inbound routing number mapped in `tenant_telephony_routes`.

3. **Webhook URL registration** — In the Twilio Console, set the phone number's "A Call Comes In" Voice webhook to: `POST https://platform.example.com/telephony/webhook`. Set the Status Callback URL to the same endpoint.

4. **TwiML endpoint accessibility** — The `/telephony/twiml` route must be publicly accessible (no auth) so Twilio can fetch call instructions. In development, use `ngrok` or a Twilio dev tunnel.

5. **Landlord DB entry** — After provisioning a Twilio number for a tenant, insert a row into `tenant_telephony_routes`: `{tenant_id: X, twilio_phone_number: '+15551234567', is_active: true}`. (A future Settings UI step in `SettingsController` can automate this.)

6. **Recording storage** — If call recording is required, enable recordings in Twilio Console per-number. Twilio stores recordings on its CDN; the `recording_url` in `call_logs` points to `api.twilio.com/...`. The `recordingProxy` controller action requires valid Twilio credentials to stream it — these never leave the server.

7. **Queue worker** — Ensure `php artisan queue:work` (or Horizon) is running, as `queues_are_tenant_aware_by_default=true` will enqueue any future job-based call processing correctly.

8. **Browser calling NOT included** — This implementation covers server-side click-to-call (Twilio dials the agent's phone, then dials the contact). Browser-based WebRTC calling (Twilio Client JS SDK, STUN/TURN servers, SIP over WebSocket) is a separate workstream requiring: Twilio Client Access Token generation endpoint, `twilio.js` browser library, microphone permission handling, and WebRTC media infrastructure. This is explicitly out of scope for this implementation.

---

## 10. Package Research Summary

| Package | URL | License | Last Activity | PHP 8.3 | Laravel 12 | Voice features | Infrastructure needed |
|---|---|---|---|---|---|---|---|
| **twilio/twilio-php** ✅ | [packagist](https://packagist.org/packages/twilio/twilio-php) | Twilio Helper Lib License | 2025 (active) | ✅ explicit | ✅ no Laravel dep | Outbound call, status webhooks, recordings, HMAC validation, TwiML | Twilio account + phone number |
| aloha/twilio ❌ | [packagist](https://packagist.org/packages/aloha/twilio) | MIT | ~2019 (dead) | ❌ | ❌ | SMS + calls (thin wrapper) | Same as twilio-php + abandoned |
| team-telnyx/telnyx-php | [packagist](https://packagist.org/packages/team-telnyx/telnyx-php) | MIT | 2024 | ✅ (PHP 8.1+) | ✅ no dep | Voice calls, webhooks | Telnyx account, SIP trunk |
| vonage/client | [packagist](https://packagist.org/packages/vonage/client) | Apache-2.0 | 2024 | ✅ | ✅ | Voice calls, webhooks | Vonage account, application setup |
| marcelog/pami | [packagist](https://packagist.org/packages/marcelog/pami) | LGPL-2.1 | 2022 | ⚠️ unclear | ❌ no Laravel | Asterisk AMI events | Self-hosted Asterisk PBX |

*Content was rephrased for compliance with licensing restrictions.*

---

## FEAT Artifacts

A detailed FEAT decomposition lives under `.agents/tasks/voip-telephony/`:
- `task.json` — task metadata and feature order
- `context.json` — full project context for coder steps
- `features/FEAT-001.json` — Package scaffold and migrations
- `features/FEAT-002.json` — Service layer, webhook controller, DataGrid
- `features/FEAT-003.json` — Views and UI injection
- `features/FEAT-004.json` — Pest tests
