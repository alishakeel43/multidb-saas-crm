# Environment Notes

## Local database

- MySQL host: `127.0.0.1`
- MySQL port: `8889` (MAMP)
- MySQL username: `root`
- Landlord database: `zaristech_saas`
- Tenant database pattern: `tenant_{slug}`
- Verified local MAMP MySQL server: `8.0.44`.
- PHASE 1 smoke-install database: `tenant_bootstrap` (created locally; contains the stock Krayin schema/seed data, not landlord data).
- The local ignored `.env` uses `DB_CONNECTION=tenant`, `TENANT_DB_DATABASE=tenant_bootstrap`, and `LANDLORD_DB_DATABASE=zaristech_saas`. Connection credentials are read from the local environment only; do not copy them into docs.
- `zaristech_saas` was confirmed absent before PHASE 2 and was provisioned as the landlord database. PHASE 3 added eight SaaS tables; with Laravel's migration repository it currently contains nine tables.
- The default connection is `tenant`; Spatie changes its database name when a tenant becomes current. The named `landlord` connection is reserved for platform data.

## Application paths & hosts

Full correct process: [`docs/DOMAIN_AND_URLS.md`](./DOMAIN_AND_URLS.md)

- Local SaaS base: `krayinsaas.localhost:8888` + wildcard `*.krayinsaas.localhost` → `krayin-saas-crm/public`
- **Do not** map this app to `krayin.localhost` (old `Krayin_laravel_CRM_local` project).
- Tenant CRM (correct): `http://{slug}.krayinsaas.localhost:8888/admin/login`
- Platform Super Admin (correct): `http://platform.krayinsaas.localhost:8888/super-admin/login` (landlord + `platform` guard)
- v1 transitional: apex `http://krayinsaas.localhost:8888/admin/login` and `.../super-admin/login` both work on the shared vhost; Super Admin remains platform-auth/landlord-only even if the hostname also maps a demo tenant.
- MAMP config: `/Applications/MAMP/conf/apache/extra/httpd-vhosts.conf`
- Local ignored `.env` sets `APP_URL=http://krayinsaas.localhost:8888`
- Keep MAMP Apache running; `php artisan serve` is not required.
- After Apache config changes, validate/reload:
  `/Applications/MAMP/Library/bin/httpd -t -f /Applications/MAMP/conf/apache/httpd.conf`
  `/Applications/MAMP/Library/bin/httpd -k graceful -f /Applications/MAMP/conf/apache/httpd.conf`

## Secrets and local configuration

- Keep credentials and API keys in a local, untracked `.env`; never add them to documentation or commit them.
- `APP_KEY` is generated locally and must remain in the ignored `.env`.

## Tenant hostnames

- `App\Multitenancy\TenantFinder\DomainTenantFinder` matches the request hostname against `domains.domain`, returning only active domains whose tenant status is `active`. It does not read tenant IDs from request data.
- Current demo tenant domain (landlord): `krayinsaas.localhost` (apex convenience). Preferred future CRM host: `demo.krayinsaas.localhost`.
- Which tenant? Read the hostname (`{slug}.krayinsaas.localhost`).

## Queue worker

- The local `.env.example` defaults to `QUEUE_CONNECTION=sync`; this executes queued jobs inline and does not require a worker.
- For a persistent database-backed queue, set these values in the ignored local `.env` (do not commit `.env`):

  ```dotenv
  QUEUE_CONNECTION=database
  DB_QUEUE_CONNECTION=landlord
  QUEUE_FAILED_DATABASE=landlord
  ```

- Queue and failed-job infrastructure tables are created on the landlord connection, not in tenant CRM databases. Create them there:

  ```bash
  php artisan migrate --database=landlord --path=database/migrations/2024_09_09_094042_create_jobs_table.php --no-interaction
  php artisan migrate --database=landlord --path=database/migrations/2019_08_19_000000_create_failed_jobs_table.php --no-interaction
  ```

- Start a worker from the project root:

  ```bash
  php artisan queue:work database --queue=default --tries=3 --timeout=90
  ```

- Keep the worker running under a process supervisor in production. After deployment or worker-affecting configuration changes, run `php artisan queue:restart` and let the supervisor restart it. Tenant-aware payloads restore the landlord tenant record before handling each job and return to the prior context afterward.
- Verified automated coverage executes a queued tenant-aware job with Laravel's database worker against isolated SQLite landlord/tenant databases. Local MAMP MySQL worker startup was not separately exercised.

## PHASE 1 bootstrap record

Official source: `krayin/laravel-crm` v2.2.6 (`laravel/framework` v12.66.0; package requires Laravel `^12.0`).

Commands executed:

```bash
composer create-project krayin/laravel-crm <temporary-staging-directory> 2.2.6 --no-interaction
composer install --no-interaction
cp .env.example .env
php artisan key:generate --force
php artisan krayin-crm:install --skip-env-check --skip-admin-creation
php artisan --version
php artisan route:list
```

The isolated `tenant_bootstrap` database was created on local MAMP MySQL and the official installer migrated and seeded it (60 tables). Admin account creation was intentionally skipped; no default or weak credentials were installed. PHASE 1 did not apply any migration to the landlord database; `zaristech_saas` was created empty in PHASE 2.

## PHASE 2 multitenancy foundation

- Installed `spatie/laravel-multitenancy` v4.2.1 (`^4.0`).
- Published `config/multitenancy.php` and configured named `tenant` / `landlord` connections and the tenant database-switch task.
- The manual current-tenant smoke test switched to `tenant_bootstrap` and queried `SELECT DATABASE()` successfully; the landlord query returned `zaristech_saas`.
- `composer validate`, configuration caching, route registration, and the project test suite succeeded. The suite reported existing PHP 8.5 deprecation notices for `PDO::MYSQL_ATTR_SSL_CA`.
- Context7's direct MCP endpoint now responds without the invalid Authorization header. The integrated VS Code MCP client may require a reload to discard cached settings.

## PHASE 3 landlord schema and models

- Ran `php artisan migrate --database=landlord --path=database/migrations/landlord --no-interaction`; the landlord migration repository records the migration on `zaristech_saas`.
- The migration creates `plans`, `plan_features`, `tenants`, `domains`, `tenant_features`, `subscriptions`, `platform_users`, and `platform_settings`. Each SaaS model uses the landlord connection; the tenant model extends Spatie's tenant model.
- Seeded the zero-cost `starter` plan and eight enabled stock-module feature keys using `php artisan db:seed --database=landlord --class='Database\Seeders\DefaultPlanSeeder' --no-interaction`. Re-running the seeder is idempotent.
- Smoke-tested request-host lookup through the Domain relationship, tenant status and domain active guards, and tenant DB switching back to `tenant_bootstrap`. Temporary tenant/domain records were deleted afterward.
- Verified `zaristech_saas` contains the eight SaaS tables plus Laravel's migration repository; tenant DB still contains 60 Krayin tables. No platform user or CRM data was seeded into the landlord DB.
- The full test suite passes: 1 pass and 41 existing PHP 8.5 deprecation notices from `PDO::MYSQL_ATTR_SSL_CA`. One initial logout test run returned 419 while other Artisan checks ran concurrently; it passed both alone and in the subsequent full-suite rerun.

PHP 8.5.7 emits non-fatal deprecation warnings from stock `config/database.php` for `PDO::MYSQL_ATTR_SSL_CA`; the upstream file was not modified during bootstrap.

## PHASE 4 live provisioning verification

- Confirmed `tenant_demo` did not exist before provisioning; the existing database inventory contained no tenant schema with that name. Confirmed the requested domain and storage path were also unused.
- Ran `php artisan tenants:provision demo demo.krayin.localhost --name="Demo" --admin-name="Demo Admin" --admin-email="admin@demo.krayin.localhost"` successfully using the local `.env` credential. No password or hash was printed or recorded here.
- Verified the landlord tenant is active with database `tenant_demo`, primary domain `demo.krayin.localhost`, eight plan-feature defaults, and a trial subscription.
- Switched the tenant DB connection to `tenant_demo`; verified its 60 Krayin tables, all 93 migrations, and the active `admin@demo.krayin.localhost` Administrator with `permission_type=all`. Confirmed only that its stored hash matches the local configured credential; the credential itself was not displayed.
- Exercised the service's compensating rollback using a controlled migration failure for unused `demo-rollback` / `demo-rollback.krayin.localhost`. It created and then removed only `tenant_demo_rollback`; its landlord tenant/domain rows and storage were also absent after failure.
- After both operations, `zaristech_saas` remained at nine tables and `tenant_bootstrap` at 60 tables. All pre-existing MySQL schema names were unchanged; only the intended successful `tenant_demo` was added.
- `tenant_demo` is retained as the local verification tenant. `tenant_demo_rollback` does not exist.
- Focused Phase 4 Pest tests passed (99 assertions); full suite completed with zero failures (196 assertions). Its 77 deprecated cases are the existing PHP 8.5 `PDO::MYSQL_ATTR_SSL_CA` deprecation from upstream configuration. Pint, PHP syntax checks, and command registration passed.
