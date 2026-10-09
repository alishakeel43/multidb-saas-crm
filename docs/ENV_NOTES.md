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
- `zaristech_saas` was confirmed absent before it was created for PHASE 2. It currently has zero tables; landlord migrations are deferred to PHASE 3.
- The default connection is `tenant`; Spatie changes its database name when a tenant becomes current. The named `landlord` connection is reserved for platform data.

## Application paths

- Krayin CRM: `/admin` (or `APP_ADMIN_PATH`)
- Filament Super Admin: `/super-admin`

## Secrets and local configuration

- Keep credentials and API keys in a local, untracked `.env`; never add them to documentation or commit them.
- `APP_KEY` is generated locally and must remain in the ignored `.env`.

## Tenant hostnames

- Spatie's configured `DomainTenantFinder` matches the request hostname against a tenant's `domain` attribute. PHASE 3 must adapt it to the planned `Domain` model and document concrete local hostnames before persisted domain-routing verification.

## Queue worker

- Document the tenant-aware worker command and runtime setup in PHASE 7.

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
- Published `config/multitenancy.php` and configured the built-in domain finder, tenant database-switch task, and named `tenant` / `landlord` connections.
- The manual current-tenant smoke test switched to `tenant_bootstrap` and queried `SELECT DATABASE()` successfully; the landlord query returned `zaristech_saas`.
- `composer validate`, configuration caching, route registration, and the project test suite succeeded. The suite reported existing PHP 8.5 deprecation notices for `PDO::MYSQL_ATTR_SSL_CA`.
- Context7's direct MCP endpoint now responds without the invalid Authorization header. The integrated VS Code MCP client may require a reload to discard cached settings.

PHP 8.5.7 emits non-fatal deprecation warnings from stock `config/database.php` for `PDO::MYSQL_ATTR_SSL_CA`; the upstream file was not modified during bootstrap.
