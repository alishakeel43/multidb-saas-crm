# SaaS Handoff — read me first

## Snapshot

- Date: 2026-10-09
- Working branch: `phase/07-storage-queues`
- Current phase: COMPLETE — `V1_COMPLETE`
- PHASE 8 status: COMPLETE
- PHASE 7 status: COMPLETE

## Final V1 isolation verification

- Separate active domain records for tenant A and tenant B resolve to their respective tenant records and separate SQLite databases; each tenant sees only its own isolated record.
- Tenant storage switching invalidates already-cached Laravel filesystem disks. A file written under tenant A's local disk is not visible through tenant B's disk.
- A queued `TenantAware` job was dispatched with tenant A current, processed by Laravel's database queue worker, and observed tenant A's context inside `handle()`; the worker returns to the prior no-tenant state afterward.
- Database queue and failed-job tables are configured on the landlord connection so a worker can retrieve tenant jobs centrally; setup commands are recorded in `ENV_NOTES.md`.
- Disabled feature routes return 403 and tenant CRM accounts are not accepted by the isolated `/super-admin` panel (existing focused tests).
- Focused tests pass for tenant storage isolation, two-domain/two-database isolation, asynchronous queue context, feature gating, and Super Admin access.
- Full Pest suite completed without failures (264 assertions); 95 existing upstream PHP deprecation notices remain.
- Known production follow-up: investigate the Composer security advisories previously reported, deploy with production-grade database/domain settings, and supervise the queue worker. The local MAMP worker process itself has not been separately exercised.

## Completed phases

- PHASE 0 created the continuity documents and recorded architecture decisions.
- PHASE 1 installed official Krayin v2.2.6 into the workspace and created the isolated `tenant_bootstrap` schema (60 tables). Installer admin creation was skipped.
- PHASE 2 configured Spatie Multitenancy with separate `tenant` and `landlord` connections and domain-based tenant selection. Implementation commit: `f5c1a65`.
- PHASE 3 added landlord models and migrations for plans, features, tenants, domains, subscriptions, platform users, and settings. `zaristech_saas` has eight SaaS tables plus Laravel's migrations table. Implementation commit: `749e600`.
- PHASE 4 implements guarded tenant DB management, safe Krayin baseline seeding, tenant provision/migrate/seed commands, explicit tenant administrator creation, and compensating rollback.
- PHASE 5 installs and configures Filament 3.3 at `/super-admin`, isolated from Krayin `/admin` using a dedicated `platform` guard and landlord `PlatformUser`. Tenant/domain/plan/plan-feature/subscription/tenant-feature resources and safe provisioning integration are in place.
- Local development uses MAMP Apache virtual host `http://krayinsaas.localhost:8888`, mapped to this repository's `public/` directory. The local ignored `.env` now sets `APP_URL` to that host; use the MAMP app to run Apache rather than `php artisan serve`.
- Verified MAMP Apache configuration syntax and graceful reload; base `http://krayinsaas.localhost:8888/admin/login` and `/super-admin/login`, plus `http://platform.krayinsaas.localhost:8888/super-admin/login`, return HTTP 200. Landlord domain row maps the base hostname to the active `demo` tenant. No `/etc/hosts` edit was needed and the pre-existing `krayin.localhost` vhost was left unchanged.
- The vhost configuration is local to `/Applications/MAMP/conf/apache/extra/httpd-vhosts.conf`; this machine-specific path is not part of the repository commit. The local `.env` APP_URL edit is ignored and is also not committed.

## PHASE 5 implementation and verification

- `SuperAdminPanelProvider` registers the default Filament panel at `/super-admin`, uses the dedicated landlord-backed `platform` guard, and leaves Krayin's `/admin` panel untouched.
- `PlatformUser` can access only the `super-admin` panel while active. Panel middleware redirects guests directly to Filament's login route rather than Krayin's custom CRM login route; tenant CRM users are not accepted by the platform guard.
- Super Admin resources manage tenants, domains, plans, plan feature defaults, subscriptions, and tenant feature overrides. Tenant UI omits the tenant database field and all database credentials; deletions are not exposed for core tenant/billing records.
- Tenant creation asks for the tenant and initial CRM administrator details, then calls `TenantProvisioningService`. The password is removed from Livewire state before the response, is not persisted on landlord records, and appears only as a masked, non-revealable form field.
- `php artisan platform-admin:create` securely bootstraps the first active platform admin through interactive prompts, requires password confirmation and a 12-character minimum, and accepts no credentials as command-line arguments. The command was not run, so no platform credentials were created.
- Context7 and official Filament docs were unavailable because of DNS. Used the installed Filament 3.3.56 source and verified routes and runtime behavior; no framework API was guessed from memory.
- No database migrations or tenant provisioning were run in PHASE 5.
- Composer reported eight security advisories across five dependencies during Filament installation. They were not investigated in PHASE 5; audit and resolve them as a separate dependency-hardening task before production.

## PHASE 6 implementation and verification

- `FeatureEntitlementService` resolves tenant override, then the assigned plan feature, then the configured feature default. Unknown features and requests without a current tenant are disabled. It enforces each feature's `depends_on` prerequisites recursively; a child cannot be enabled while a prerequisite is disabled, regardless of the child's plan/tenant toggle.
- Feature middleware is appended to Laravel's `web` group. It gates every stock Krayin admin menu module, including dashboard, leads and lead settings, contacts/organizations, products, quotes, mail, activities, user management, inventory, automation subsections, marketing events/campaigns, Web Forms, tags, Google Contacts, configuration, and help. The most-specific route prefix wins; exact shared settings routes use exact-name mapping.
- The admin menu hides disabled module entries; Contacts, Automation, and Other Settings parents point to an enabled child and disappear when all child features are effectively disabled. Every stock admin menu key is covered by the catalog.
- Entitlements are cached by tenant. Model events invalidate the cache after tenant override create/update/delete, plan feature create/update/delete for tenants on the affected plan, and tenant plan assignment changes.
- Tenant feature override editing now exposes delete so a platform administrator can remove an override and resume plan inheritance. New tenant provisioning no longer creates override rows from plan defaults. Rows created for existing tenants by earlier provisioning remain explicit overrides until removed; no live tenant DB cleanup was performed.
- Isolated tests verify override precedence, plan/config fallback, unknown/no-tenant behavior, dependency cascades including enabled child overrides, 403 middleware behavior, full menu coverage, route specificity, cache invalidation, and provisioning without copied overrides.
- Validation before the catalog follow-up passed: focused entitlement/provisioning tests (84 assertions), full Pest suite (226 assertions, zero failures), Pint, route registration, and `git diff --check`. The follow-up expands menu coverage and dependency tests on the same PHASE 6 branch; final follow-up/full-suite results are recorded in the changelog and progress before commit. Existing PHP 8.5 PDO SSL-CA deprecations are unrelated. No migration or live DB mutation has been run.

## PHASE 4 implementation and verification

- `TenantDatabaseManager` restricts database operations to MAMP MySQL at `127.0.0.1:8889`, derives only validated `tenant_{slug}` names, protects `zaristech_saas` and `tenant_bootstrap`, refuses reuse, and drops only a database created by the same manager instance. Connection/privilege failures include actionable context without exposing credentials.
- `TenantProvisioningService` validates slug/domain/credentials and rejects duplicate landlord records, database names, and storage paths. It creates pending landlord records, a tenant database, migrations, guarded CRM baseline data, explicit-credential Bouncer Administrator, trial subscription, and storage before activation. Current provisioning inherits plan features rather than copying them into tenant overrides. Its compensation removes only artifacts created by that attempt.
- `TenantCrmSeeder` only calls the selected Krayin baseline seeders when every table in that baseline is empty. It never invokes the installer root seeder or destructive `RoleSeeder` / `UserSeeder`.
- CLI provisioning is local/testing-only. `TENANT_ADMIN_PASSWORD` remains in ignored local `.env`; do not print or document it.
- Reconfirmed `tenant_demo`, `demo.krayin.localhost`, and `storage/tenants/demo` were unused; ran the requested `tenants:provision` command successfully.
- Historical verification confirmed active tenant/domain records, eight copied feature defaults from the original provisioning implementation, trial subscription, 60 tenant tables, 93 migrations, an active Administrator user with a password hash matching the local configured credential, and tenant database switching to `tenant_demo`. Those pre-existing feature rows now act as tenant overrides; remove them in Super Admin to restore plan inheritance. The credential/hash were not printed or documented.
- Smoke-tested `tenants:migrate demo` (`Nothing to migrate`) and `tenants:seed demo` (safe baseline seeding succeeded).
- Exercised rollback with an intentional migration failure for `demo-rollback`. Only the just-created `tenant_demo_rollback` database was dropped; its landlord tenant/domain rows and storage path were removed. The successful `demo` tenant stayed active.
- `zaristech_saas` remained at nine tables and `tenant_bootstrap` at 60. Existing schema names were unchanged except for the intended new `tenant_demo`; the rollback database is absent.
- Tests cover success, tenant migrations/seeding and admin setup, reserved/malformed identifiers, duplicates, MAMP configuration, connection failures, and cleanup after migration and later activation failures.

## Documentation fallback

Context7 was preferred but could not be resolved for Laravel lookups; the official `laravel.com` docs endpoint also failed DNS. The Spatie docs site was reachable, though the requested targeted page redirected to its index. Work continued without blocking using installed Laravel 12, Spatie Multitenancy, and Krayin source, plus reachable Spatie documentation. No dependency was added.

## Validation notes

- Focused Pest suite: 36 cases marked deprecated, 99 assertions, zero failures. Full `php artisan test --compact`: 1 pass, 77 cases marked deprecated, 196 assertions, zero failures.
- PHASE 5 focused Super Admin suite: 6 cases marked deprecated, 14 assertions, zero failures. Full `php artisan test --compact`: 1 pass, 83 cases marked deprecated, 210 assertions, zero failures. Pint, Super Admin route registration, and `git diff --check` passed.
- `./vendor/bin/pint` and `php -l` passed for changed PHP files; command registration and final database inventory checks passed; `git diff --check` passed.
- The deprecation cases are the existing PHP 8.5.7 `PDO::MYSQL_ATTR_SSL_CA` notice from upstream `config/database.php`, not new Phase 4 failures.
- Tenant migrate/seed wrappers are `php artisan tenants:migrate {slug|id}` / `php artisan tenants:seed {slug|id}`; use `--all` only when intentionally operating on every tenant.

## Next concrete step

Begin PHASE 7 — storage and queues, following its acceptance criteria and working on a dedicated `phase/07-storage-queues` branch. Retain `tenant_demo` as the local verification tenant; do not remove it without a separately scoped, explicit cleanup action. Keep all credentials out of source control.

## Files changed for PHASE 4 completion

- `app/Services/TenantDatabaseManager.php`
- `tests/Unit/TenantProvisioningServiceTest.php`
- `docs/PROGRESS.md`
- `docs/SAAS_HANDOFF.md`
- `docs/CHECKLIST.md`
- `docs/CHANGELOG_SAAS.md`
- `docs/ENV_NOTES.md`

The existing staged `.gitignore` changes and `.vscode/mcp.json` were not modified; exclude them from the PHASE 4 commit.

## Files changed for PHASE 5 completion

- `composer.json` / `composer.lock`
- `app/Console/Commands/CreatePlatformAdminCommand.php`
- `app/Filament/Resources/`
- `app/Http/Middleware/AuthenticateSuperAdmin.php`
- `app/Models/PlatformUser.php`
- `app/Providers/Filament/SuperAdminPanelProvider.php`
- `bootstrap/providers.php`
- `config/auth.php`
- `tests/Feature/SuperAdminAccessTest.php`
- `docs/PROGRESS.md`, `docs/SAAS_HANDOFF.md`, `docs/CHECKLIST.md`, `docs/CHANGELOG_SAAS.md`

Do not stage or commit the pre-existing `.gitignore` changes, `.vscode/` content, or generated `.phpunit.cache/`.

## Documentation sources

- Context7 is OPTIONAL. Prefer it when available; otherwise use official docs + `vendor/` and continue. Never block for Context7 alone.

## Domain / URL process

- Canonical: `docs/DOMAIN_AND_URLS.md` (D17/D18).
- Platform Super Admin preferred: `platform.krayinsaas.localhost/super-admin`.
- Tenant CRM: `{slug}.krayinsaas.localhost/admin`.
- Base vhost: `krayinsaas.localhost` (not `krayin.localhost`).
- MAMP vhost and landlord base-domain routing were verified 2026-10-09; preferred per-tenant `demo.krayinsaas.localhost` routing requires a matching active landlord Domain record before using that hostname for CRM.

## Do NOT

- Use `krayin-crm:install`, `migrate:fresh`, or destructive installer `RoleSeeder` / `UserSeeder` during tenant provisioning.
- Provision, drop, or truncate `zaristech_saas`, `tenant_bootstrap`, or any pre-existing database.
- Store or print `TENANT_ADMIN_PASSWORD`, `APP_KEY`, or `DB_PASSWORD`.
- Use Stancl or shared-database `tenant_id` tenancy; put CRM business tables on the landlord; replace Krayin Bouncer; or trust a request-provided tenant ID.
- Add a co-author trailer to the requested PHASE 4 commit.
