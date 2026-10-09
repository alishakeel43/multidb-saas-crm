# Environment Notes

## Local database

- MySQL host: `127.0.0.1`
- MySQL port: `8889` (MAMP)
- MySQL username: `root`
- Landlord database: `zaristech_saas`
- Tenant database pattern: `tenant_{slug}`
- Verified local MAMP MySQL server: `8.0.44`.
- PHASE 1 smoke-install database: `tenant_bootstrap` (created locally; contains the stock Krayin schema/seed data, not landlord data).
- The local `.env` points to `tenant_bootstrap` and is ignored by Git. Do not commit it or copy its credentials into docs.

## Application paths

- Krayin CRM: `/admin` (or `APP_ADMIN_PATH`)
- Filament Super Admin: `/super-admin`

## Secrets and local configuration

- Keep credentials and API keys in a local, untracked `.env`; never add them to documentation or commit them.
- `APP_KEY` is generated locally and must remain in the ignored `.env`.

## Tenant hostnames

- Decide and document the local tenant hostname / hosts-file setup when implementing domain-based tenant resolution in PHASE 2.

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

The isolated `tenant_bootstrap` database was created on local MAMP MySQL and the official installer migrated and seeded it (60 tables). Admin account creation was intentionally skipped; no default or weak credentials were installed. No schema was created on `zaristech_saas`.

PHP 8.5.7 emits non-fatal deprecation warnings from stock `config/database.php` for `PDO::MYSQL_ATTR_SSL_CA`; the upstream file was not modified during bootstrap.
