# SaaS Change Log

## 2026-10-09 — Compact Task quick-view drawer

- Added an opt-in content-height mode to the shared drawer and enabled it for the Task quick view. Compact drawers no longer inherit full-height flex sizing, reset to the top when opened, and keep Open Full View and Save Changes beside the form while scrolling when content exceeds the viewport.

## 2026-10-09 — Restore Lead kanban quick-view translations

- Added the missing English Lead kanban quick-view strings so the drawer displays translated labels and actions instead of raw translation keys.

## 2026-10-09 — Fix middleware and empty contact-number warnings

- Removed unused middleware constructor assignments that created PHP dynamic-property warnings.
- Guarded the person unique-ID generation when optional contact numbers are empty after filtering.

## 2026-10-09 — Repair person email uniqueness validation

- Restored the missing tenant `persons.emails` and `persons.contact_numbers` system attributes with a forward migration, preserving existing customized attribute records.
- Scoped remote uniqueness lookups by entity type and return a validation response when the requested attribute is missing.
- Fixed unique-value checks for new records so a null entity ID checks for duplicates across existing records rather than silently skipping them.
- Allowed users with Lead/Person create or edit access to use the shared read-only email uniqueness endpoint, without requiring unrelated Settings permissions.
- Added regression coverage for restored metadata, duplicate email detection, and editing a record without rejecting its own email.

## 2026-10-10 — Restore tenant workspace user repository scope

- Restored `UserRepository::scopeToWorkspace()` for Task create/edit and other existing dropdown paths; isolated tenant databases provide the workspace boundary, and queries outside an active tenant return no users.
- Added a tenant-switching regression assertion to verify that workspace-scoped user listings only return users from the active tenant database.
- Restored active-user and permission filtering for Lead kanban assignee search, and hid comparison percentages when the previous period has no baseline.

## 2026-10-09 — Wave 1 complete (P11–P16)

- Activity reminders: tenant-DB schema without `tenant_id`; `activities:dispatch-reminders` fans out over landlord tenants; `SendActivityReminder` switches tenant DB per job.
- Lead kanban UX: assignee filter, next activity on cards, quick-update route; Product Entrusted attribute rename + text storage migrations applied to `tenant_demo`.
- `App\Services\TenantClock` + `ApplyTenantClock` middleware and Spatie tenant current/forget listeners (no TenantManagement).
- Dashboard reporting helpers/views and empty-baseline percentage behavior; EN win/lost share strings.
- Zaristech branding: Admin logos/favicon, Filament brand `#2846A0`, EN help/footer copy, `.env.example` APP_NAME.
- Expanded regression coverage for reminder scheduling, tenant fan-out/delivery, Task validation/scopes/reminder fan-out, Product Entrusted conversion/rollback, tenant-clock reset, dashboard request dates, and Zaristech branding.
- Made Product Entrusted numeric-value normalization database-driver independent while preserving the existing zero-trimming behavior.
- Added missing English translations for the lead kanban user-filter tabs.
- Guarded the person organization lookup when its system attribute record is missing, following the reference CRM behavior.
- Restored missing standard lead/person attributes per tenant and adapted the reference CRM's deferred product quick-create modal to the tenant-local catalog endpoint.
- Validation: full Pest suite, focused Wave 1 regression tests, Pint, and `git diff --check`.

## 2026-10-09 — PHASE 10 Task package (multi-DB)

- Ported `packages/Webkul/Task` from `Krayin_laravel_CRM_local` `feature/todo-management` with all shared-DB tenancy removed (`tenant_id`, `HasTenant`, `TenantContext`, `tenant_users`).
- Registered Task in Composer, Concord, and `bootstrap/providers.php`; added `tasks` entitlement in `config/features.php`.
- `tasks:check-reminders` fans out over active landlord tenants via `$tenant->execute()`.
- Admin EN lang keys, dashboard tasks widget blade, and two-database isolation Pest tests added; `tenants:migrate demo` applied Task schema.

## 2026-10-09 — PHASE 9 Wave-1 port playbook

- Added `docs/agents/PLAN_PORT_FEATURES_COPILOT.md` for post-V1 Wave 1 (Task, reminders, kanban, Product Entrusted, timezone/locale, dashboard, branding).
- Wired `docs/agents/README.md` and coordinator Message B to the Wave-1 playbook.
- Locked D19–D22 in `docs/DECISIONS.md`; extended `PROGRESS` / `HANDOFF` / `CHECKLIST` for phases P9–P16.
- Explicitly deferred UnifiedInbox, AI, Stripe, Meta/CSV intake, and Google Calendar tenancy; forbids copying TenantManagement / CRM `tenant_id`.

## 2026-10-09 — Tenant support-account management

- Added a Super Admin support-user management resource to create, view, update, activate/deactivate, and delete multiple support identities per tenant. Each account uses an individual tenant CRM email and password; passwords are stored only as tenant-side hashes.
- Removed shared `.env` support-user seeding. Support accounts retain a dedicated read-only CRM role and remain hidden from normal CRM user/role lists and assignment/search options, while remaining visible in Super Admin tenant details.
- Support-account ownership/participant assignment is rejected, and support CRM write requests are denied server-side.

## 2026-10-09 — Super Admin recent-tenants table query fix

- Configured the recent-tenants widget query directly on Filament's table, removing the runtime `must have a [query()]` error.
- Added a regression assertion that initializes the widget table and verifies the five-tenant query limit.
- Validation passed: focused Super Admin and tenant overview tests (30 assertions), Pint, and Super Admin route registration.

## 2026-10-09 — Super Admin dashboard and tenant overview

- Replaced the default Filament information card with platform KPI cards, a six-month tenant-growth chart, and a recent-tenants table.
- Added per-tenant administrator/owner contact, CRM user counts, database health, leads, contacts, and organization counts, read from the tenant database without moving CRM data to the landlord.
- Expanded tenant rows and the tenant-details action with primary domains, plan/subscription status, trial dates, CRM health, and usage details.
- Added focused tests for Super Admin dashboard registration and tenant overview queries.
- Validation passed: full Pest suite (279 assertions, zero failures), focused dashboard/tenant overview tests, Pint, PHP syntax, route registration, and `git diff --check`. Browser verified the branded Super Admin login route; authenticated dashboard rendering still requires a platform account.

## 2026-10-09 — PHASE 8 isolation hardening complete

- Added explicit two-domain/two-database isolation coverage: each resolved tenant reads its own isolated record.
- Fixed cached Laravel filesystem disks during tenant switches and verified tenant A's local files are not visible through tenant B's local disk.
- Configured Laravel's database queue and failed-job storage to use the landlord connection, and documented queue migrations, `.env` settings, and worker startup in `ENV_NOTES.md`.
- Added an asynchronous queue-worker test proving a `TenantAware` job runs with its dispatching tenant and restores the previous no-tenant worker context.
- Reconciled PHASE 7/8 checklists and recorded `V1_COMPLETE` plus production follow-up notes in the progress and handoff documents.
- Validation: the full Pest suite passed with 264 assertions and no failures; Pint and `git diff --check` passed. The 95 reported deprecation notices are existing upstream PHP warnings.

## 2026-10-09 — PHASE 7 tenant storage and queue awareness

- Added a tenant-scoped storage switch task that roots the local and public filesystem disks under `storage/tenants/{slug}/app` and `storage/tenants/{slug}/app/public` whenever a tenant becomes current.
- Registered the storage switch task ahead of the DB switch task in `config/multitenancy.php` so filesystem paths follow the active tenant while preserving the default landlord root when no tenant is current.
- Added a focused unit test covering tenant-rooted storage and a tenant-aware queue job that validates the tenant remains current inside the job.
- Validation passed: focused PHASE 7 Pest test file executed successfully under the existing project deprecation warnings. The next step is PHASE 8 isolation tests and hardening.

## 2026-10-09 — PHASE 6 catalog and dependency cascade follow-up

- Expanded the entitlement catalog to cover every stock Krayin admin menu item and route group, including dashboard, mail, activities, user management, inventory, settings integrations, configuration, and help.
- Replaced free-text feature keys in Super Admin plan/tenant feature forms with the supported catalog selector.
- Added prerequisite cascades (for example, Organizations requires Contacts; Quotes require Leads and Products; Web Forms require Leads; Automation sections require Automation; campaigns require Marketing and Events). A dependent remains effectively disabled when a prerequisite is disabled, even if its own plan or tenant override is on.
- Ensured Contacts, Automation, and Other Settings parent menus point to an enabled child and hide when no child is available.
- Added tests for catalog coverage, feature dependencies, child-override cascade, and dependent-route 403 behavior. Existing databases were not migrated or modified.

## 2026-10-09 — PHASE 6 feature entitlements completed

- Added tenant-override → plan-default → configured-default resolution, tenant-keyed caching, and invalidation for override, plan-feature, and tenant-plan changes.
- Added web middleware that returns 403 for disabled mapped Krayin modules and filters unavailable admin menu entries while preserving independently enabled nested modules.
- Made tenant override deletion available in Super Admin so tenants can resume plan inheritance.
- Changed new tenant provisioning to inherit plan defaults rather than copying them into tenant override rows. Existing copied rows are intentionally preserved as overrides until explicitly deleted.
- Added isolated entitlement tests for precedence, fallback, unknown features, no tenant, route specificity, 403 responses, menu filtering, cache invalidation, and provisioning behavior.
- Validation passed: full Pest suite (226 assertions, zero failures), Pint, route registration, and whitespace checks. No live DB mutation or migration was performed. PHASE 7 — storage and queues is next.

## 2026-10-09 — PHASE 0 handoff setup

- Created the six project continuity documents: handoff, progress, decisions, checklist, change log, and environment notes.
- Recorded locked architecture decisions and phase acceptance criteria from the GitHub Copilot plan.
- Marked PHASE 0 complete; PHASE 1 (Krayin bootstrap) is the next phase.
- No application code, dependencies, environment secrets, or database state were changed.

## 2026-10-09 — Git branch and commit workflow

- Added the same mandatory branch hierarchy, phase/task branch naming, milestone-commit rules, and safeguards against staging unrelated changes to the three agent plan copies and the canonical Cursor plan.
- Updated the coordinator message and progress/handoff docs with the branch requirement.
- Created `docs/git-branch-workflow`; PHASE 1 remains the next implementation phase.

## 2026-10-09 — Commit attribution rule

- Added an explicit rule to all agent plan copies and the coordinator prompt: do not add a `Co-authored-by` trailer or assistant co-author attribution unless the user asks for it.

## 2026-10-09 — PHASE 1 started

- Began PHASE 1 on `phase/01-bootstrap-krayin`.
- Verified PHP 8.5.7, Composer 2.10.1, official Krayin v2.2.6 package requirements, and an open MAMP MySQL TCP port.
- Context7 MCP was unavailable due to transport failure; no framework/API code was written.

## 2026-10-09 — PHASE 1 completed

- Installed official `krayin/laravel-crm` v2.2.6 and Composer dependencies; lockfile resolves Laravel Framework v12.66.0.
- Configured ignored local `.env` for MAMP MySQL `127.0.0.1:8889` and isolated database `tenant_bootstrap`.
- Ran `php artisan krayin-crm:install --skip-env-check --skip-admin-creation`; migrations and seeders completed with 60 tables in `tenant_bootstrap` only.
- Verified `php artisan --version` and `php artisan route:list` succeed.
- No admin account was created. Stock code reports non-fatal PDO SSL-CA deprecation warnings under PHP 8.5.7.
- Marked PHASE 1 done. PHASE 2 is next but blocked until Context7 MCP is available for mandatory framework/package docs.
- PHASE 1 source and handoff updates were committed as `ae7a28c` (`feat(phase-01): bootstrap Krayin CRM 2.2.6`), without a co-author trailer.

## 2026-10-09 — PHASE 2 requested; blocked on Context7 credentials

- Created `phase/02-spatie-multitenancy` from the completed PHASE 1 branch.
- Retried Context7 library resolution for Laravel 12 and Spatie Multitenancy; both requests were rejected because the configured API key is invalid.
- Made no Laravel configuration, package, or application code changes; the mandated documentation gate is not met.

## 2026-10-09 — Context7 MCP authentication repair

- Removed the invalid Context7 Authorization header from the user-level VS Code MCP configuration without printing its value or adding a credential.
- Verified that the direct Context7 MCP endpoint works without Authorization and returned Spatie Multitenancy documentation. Laravel's Context7 index currently lacks Laravel 12 database pages, so the official Laravel 12 database page was checked separately.
- The already-running VS Code MCP integration may still need a reload to discard its cached configuration.

## 2026-10-09 — PHASE 2 completed

- Installed `spatie/laravel-multitenancy` v4.2.1 and published its configuration.
- Configured the default tenant connection, the named landlord connection, Spatie's domain finder, and `SwitchTenantDatabaseTask`; updated the environment example and local ignored `.env`.
- Confirmed `zaristech_saas` was absent, created it empty, and verified it still has zero tables while `tenant_bootstrap` retains its 60 Krayin tables.
- Manually made a tenant current and confirmed the tenant connection queried `tenant_bootstrap`; the landlord connection queried `zaristech_saas`.
- `composer validate`, config caching, `route:list`, and all 42 project tests succeeded (1 passed, 41 marked deprecated due to the stock PHP 8.5 `PDO::MYSQL_ATTR_SSL_CA` deprecation).
- Marked PHASE 2 complete. PHASE 3 is next; it must integrate the planned `Domain` model with tenant lookup before persisted domain routing is tested.
- Committed the implementation as `f5c1a65` (`feat(phase-02): configure tenant database switching`) without a co-author trailer.

## 2026-10-09 — PHASE 3 landlord schema and models completed

- Added the landlord migration for plans, plan features, tenants, domains, tenant feature overrides, subscriptions, platform users, and platform settings; added the corresponding landlord-connected Eloquent models.
- Replaced Spatie's default attribute-based finder with a finder that resolves the request hostname through `Domain` and refuses inactive domains or non-active tenants.
- Added the default `starter` plan and eight enabled stock-module feature keys; verified seeding is idempotent.
- Ran the landlord migration and verified eight SaaS tables plus the migrations repository exist only on `zaristech_saas`; tenant DB retains its 60 stock Krayin tables.
- Smoke-tested active domain resolution, inactive-domain and suspended-tenant rejection, and database switching; removed temporary smoke-test rows.
- Validation: PHP syntax, Pint, route/config boot, migration status, and the full test suite pass. Tests report existing PHP 8.5 `PDO::MYSQL_ATTR_SSL_CA` deprecation notices.
- Committed implementation as `749e600` (`feat(phase-03): add landlord schema and models`), without a co-author trailer. PHASE 4 is next.

## 2026-10-09 — PHASE 4 startup and Context7 DNS fallback

- Created `phase/04-tenant-provisioning` from `phase/03-landlord-schema`.
- Read the required continuity docs and Copilot plan; inspected Krayin installer/seeder and Spatie tenant command/action sources without changing application code.
- The initial Context7 lookups for Laravel Artisan and Spatie tenant Artisan behavior failed because `mcp.context7.com` could not be resolved. The user clarified that this is not a blocker; implementation continued using installed source as documented in the next entry.
- Safety note: Krayin's stock install command invokes `migrate:fresh`; its user seeder deletes existing users and inserts the published default admin credentials. Neither should be reused in tenant provisioning.

## 2026-10-09 — PHASE 4 tenant provisioning implementation

- Added strict tenant slug/domain validation, including all reserved names, and a MAMP-only database manager that derives database identifiers, refuses operations on `tenant_bootstrap` and `zaristech_saas`, and only drops databases created by that manager instance.
- Added a compensating tenant provisioning service for landlord records, an isolated tenant database, safe pending migrations, guarded CRM baseline seeders, explicit Bouncer administrator credentials, plan feature overrides, trial subscription, tenant storage, and activation. Rollback targets only artifacts created in that attempt.
- Added local/testing-only `tenants:provision` and `tenants:migrate` / `tenants:seed` commands for a specific tenant or `--all`; the password is supplied through local `.env` or an explicit option and is never logged.
- Added reserved slug/domain and protected database identifier tests. Syntax checks and command registration passed; the focused Pest suite passed 24 cases.
- Context7 and official Laravel documentation DNS lookups failed. Continued using installed Laravel 12, Spatie Multitenancy, and Krayin sources as directed; no dependencies or secrets were added.
- Did not provision a live test tenant or drop any database. Live migration, login, and failure rollback verification remain pending until a unique test domain/database and explicit admin credentials are available.

## 2026-10-09 — PHASE 4 completed and verified

- Expanded isolated Pest coverage for success, migrations/seeding, explicit administrator/hash creation, reserved and malformed identifiers, duplicate tenants/domains/databases/storage, MAMP configuration restrictions, actionable connection failures, and rollback after both migration and post-storage activation failures.
- Improved database-manager errors so schema inspection, creation, and cleanup failures identify the required MAMP connectivity/privileges without exposing credentials.
- Reconfirmed `tenant_demo`, `demo.krayin.localhost`, and `storage/tenants/demo` were unused, then ran the requested `tenants:provision` command successfully.
- Verified the active tenant/domain, eight copied feature defaults, trial subscription, 60 tenant tables, 93 migrations, active Administrator role/user, local configured password hash match, and tenant DB switching to `tenant_demo`.
- Smoke-tested `tenants:migrate demo` (no pending migrations) and `tenants:seed demo` successfully.
- Exercised compensating cleanup after a controlled migration failure for `demo-rollback`; the session-created `tenant_demo_rollback` database, tenant/domain rows, and storage were removed.
- Confirmed `zaristech_saas` remains at nine tables and `tenant_bootstrap` at 60; the existing database inventory is unchanged except for the intended new `tenant_demo`. No pre-existing database was dropped or modified.
- Context7 could not be reached for Laravel references and `laravel.com` documentation lookup failed DNS. Continued with installed Laravel 12, Spatie, and Krayin sources plus reachable Spatie docs; no dependency or secret was added.
- Validation passed: focused tests (99 assertions), full suite (196 assertions, zero failures), Pint, PHP syntax, command registration, and whitespace checks. The 77 full-suite deprecations are the existing upstream PHP 8.5 PDO SSL-CA notice.

## 2026-10-09 — Context7 policy: optional, never block

- Clarified across DECISIONS (D16), agent plans, coordinator message, and handoff: Context7 is not a hard dependency.
- If Context7 fails, agents must implement via official docs and/or vendor source without marking the phase BLOCKED.

## 2026-10-09 — PHASE 5 Filament Super Admin completed

- Installed Filament 3.3 and configured its landlord-backed panel at `/super-admin` with a dedicated `platform` guard; Krayin remains at `/admin`.
- Added resource screens for tenants, domains, plans, plan features, subscriptions, and tenant feature overrides. Tenant provisioning uses `TenantProvisioningService`, and its one-time administrator password is cleared from Livewire state before the response.
- Added active-platform-user authorization, a panel-specific authentication middleware to avoid Krayin's CRM login redirect, and the interactive `platform-admin:create` bootstrap command.
- Added tests for landlord guard assignment, CRM-user isolation, active and inactive platform admins, tenant form secrecy, and the provisioning-service delegation/password cleanup.
- Context7 and official Filament docs failed DNS; the installed Filament 3.3.56 package source was used. No tenant DB or landlord migration was run.
- Validation: focused tests (14 assertions), full Pest suite (210 assertions, zero failures), Pint, route registration, and whitespace checks passed. Existing PHP 8.5 PDO SSL-CA deprecation notices remain.
- Marked PHASE 5 complete. PHASE 6 — feature entitlements is next on `phase/06-feature-entitlements`.

## 2026-10-09 — Local MAMP virtual host

- Added a MAMP Apache vhost for `demo.krayin.localhost:8888` pointing to this workspace's `public/` directory; left the existing legacy `krayin.localhost` mapping untouched.
- Updated the local ignored `.env` APP_URL to the virtual host and documented CRM/Super Admin URLs plus MAMP config test/reload commands.
- Validated Apache configuration, gracefully reloaded MAMP Apache, and verified `/admin/login` and `/super-admin/login` return HTTP 200. `php artisan serve` is no longer needed while MAMP Apache is running.
- The MAMP vhost file and ignored `.env` are machine-local; only the repository documentation is committed.

## 2026-10-09 — Domain & URL process documented

- Added `docs/DOMAIN_AND_URLS.md`: correct platform vs tenant hosts (`platform.{base}/super-admin` vs `{slug}.{base}/admin`).
- Locked D17/D18; updated ENV_NOTES, coordinator prompt, and agent plans.
- Verified MAMP `krayinsaas.localhost` plus wildcard vhost serves this app; landlord maps the apex host to active demo tenant. Existing `krayin.localhost` still points to the unrelated legacy project.
- Gracefully reloaded Apache and confirmed the base CRM/Super Admin login routes and platform subdomain Super Admin login return HTTP 200. A per-tenant hostname still needs an active landlord Domain row.
