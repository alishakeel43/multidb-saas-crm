# SaaS Handoff — read me first

## Snapshot

- Date: 2026-10-09 08:32 +05:00
- Working branch: `phase/04-tenant-provisioning`
- Current phase: PHASE 5 — Filament `/super-admin`
- PHASE 4 status: COMPLETE

## Completed phases

- PHASE 0 created the continuity documents and recorded architecture decisions.
- PHASE 1 installed official Krayin v2.2.6 into the workspace and created the isolated `tenant_bootstrap` schema (60 tables). Installer admin creation was skipped.
- PHASE 2 configured Spatie Multitenancy with separate `tenant` and `landlord` connections and domain-based tenant selection. Implementation commit: `f5c1a65`.
- PHASE 3 added landlord models and migrations for plans, features, tenants, domains, subscriptions, platform users, and settings. `zaristech_saas` has eight SaaS tables plus Laravel's migrations table. Implementation commit: `749e600`.
- PHASE 4 implements guarded tenant DB management, safe Krayin baseline seeding, tenant provision/migrate/seed commands, explicit tenant administrator creation, and compensating rollback.

## PHASE 4 implementation and verification

- `TenantDatabaseManager` restricts database operations to MAMP MySQL at `127.0.0.1:8889`, derives only validated `tenant_{slug}` names, protects `zaristech_saas` and `tenant_bootstrap`, refuses reuse, and drops only a database created by the same manager instance. Connection/privilege failures include actionable context without exposing credentials.
- `TenantProvisioningService` validates slug/domain/credentials and rejects duplicate landlord records, database names, and storage paths. It creates pending landlord records, a tenant database, migrations, guarded CRM baseline data, explicit-credential Bouncer Administrator, copied plan features, trial subscription, and storage before activation. Its compensation removes only artifacts created by that attempt.
- `TenantCrmSeeder` only calls the selected Krayin baseline seeders when every table in that baseline is empty. It never invokes the installer root seeder or destructive `RoleSeeder` / `UserSeeder`.
- CLI provisioning is local/testing-only. `TENANT_ADMIN_PASSWORD` remains in ignored local `.env`; do not print or document it.
- Reconfirmed `tenant_demo`, `demo.krayin.localhost`, and `storage/tenants/demo` were unused; ran the requested `tenants:provision` command successfully.
- Verified active tenant/domain records, eight copied feature defaults, trial subscription, 60 tenant tables, 93 migrations, an active Administrator user with a password hash matching the local configured credential, and tenant database switching to `tenant_demo`. The credential/hash were not printed or documented.
- Smoke-tested `tenants:migrate demo` (`Nothing to migrate`) and `tenants:seed demo` (safe baseline seeding succeeded).
- Exercised rollback with an intentional migration failure for `demo-rollback`. Only the just-created `tenant_demo_rollback` database was dropped; its landlord tenant/domain rows and storage path were removed. The successful `demo` tenant stayed active.
- `zaristech_saas` remained at nine tables and `tenant_bootstrap` at 60. Existing schema names were unchanged except for the intended new `tenant_demo`; the rollback database is absent.
- Tests cover success, tenant migrations/seeding and admin setup, reserved/malformed identifiers, duplicates, MAMP configuration, connection failures, and cleanup after migration and later activation failures.

## Documentation fallback

Context7 was preferred but could not be resolved for Laravel lookups; the official `laravel.com` docs endpoint also failed DNS. The Spatie docs site was reachable, though the requested targeted page redirected to its index. Work continued without blocking using installed Laravel 12, Spatie Multitenancy, and Krayin source, plus reachable Spatie documentation. No dependency was added.

## Validation notes

- Focused Pest suite: 36 cases marked deprecated, 99 assertions, zero failures. Full `php artisan test --compact`: 1 pass, 77 cases marked deprecated, 196 assertions, zero failures.
- `./vendor/bin/pint` and `php -l` passed for changed PHP files; command registration and final database inventory checks passed; `git diff --check` passed.
- The deprecation cases are the existing PHP 8.5.7 `PDO::MYSQL_ATTR_SSL_CA` notice from upstream `config/database.php`, not new Phase 4 failures.
- Tenant migrate/seed wrappers are `php artisan tenants:migrate {slug|id}` / `php artisan tenants:seed {slug|id}`; use `--all` only when intentionally operating on every tenant.

## Next concrete step

Begin PHASE 5 on a new phase branch. Retain `tenant_demo` as the local verification tenant; do not remove it without a separately scoped, explicit cleanup action. Keep all credentials in ignored local `.env`.

## Files changed for PHASE 4 completion

- `app/Services/TenantDatabaseManager.php`
- `tests/Unit/TenantProvisioningServiceTest.php`
- `docs/PROGRESS.md`
- `docs/SAAS_HANDOFF.md`
- `docs/CHECKLIST.md`
- `docs/CHANGELOG_SAAS.md`
- `docs/ENV_NOTES.md`

The existing staged `.gitignore` changes and `.vscode/mcp.json` were not modified; exclude them from the PHASE 4 commit.

## Do NOT

- Use `krayin-crm:install`, `migrate:fresh`, or destructive installer `RoleSeeder` / `UserSeeder` during tenant provisioning.
- Provision, drop, or truncate `zaristech_saas`, `tenant_bootstrap`, or any pre-existing database.
- Store or print `TENANT_ADMIN_PASSWORD`, `APP_KEY`, or `DB_PASSWORD`.
- Use Stancl or shared-database `tenant_id` tenancy; put CRM business tables on the landlord; replace Krayin Bouncer; or trust a request-provided tenant ID.
- Add a co-author trailer to the requested PHASE 4 commit.
