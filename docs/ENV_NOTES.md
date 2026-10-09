# Environment Notes

## Local database

- MySQL host: `127.0.0.1`
- MySQL port: `8889` (MAMP)
- Landlord database: `zaristech_saas`
- Tenant database pattern: `tenant_{slug}`
- Local database service availability has not yet been verified.

## Application paths

- Krayin CRM: `/admin` (or `APP_ADMIN_PATH`)
- Filament Super Admin: `/super-admin`

## Secrets and local configuration

- Keep credentials and API keys in a local, untracked `.env`; never add them to documentation or commit them.
- No local database URL, credentials, or MAMP status have been recorded.

## Tenant hostnames

- Decide and document the local tenant hostname / hosts-file setup when implementing domain-based tenant resolution in PHASE 2.

## Queue worker

- Document the tenant-aware worker command and runtime setup in PHASE 7.
