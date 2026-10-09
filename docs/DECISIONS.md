# Locked Architecture Decisions

These decisions are the source of truth for v1. Do not change them silently; ask the user before reopening an architecture decision.

| ID | Decision |
|----|----------|
| D1 | Workspace: `/Users/ali/Desktop/krayin-saas-crm`. |
| D2 | Base: official Krayin CRM 2.2 (`krayin/laravel-crm`). |
| D3 | V1 scope: stock Krayin plus the SaaS layer only. |
| D4 | Tenancy: `spatie/laravel-multitenancy`, with one MySQL database per tenant. |
| D5 | Do not use Stancl, multiple tenancy frameworks, shared-database `tenant_id` tenancy, or copy the `Krayin_laravel_CRM_local` TenantManagement implementation. |
| D6 | MAMP MySQL host and port: `127.0.0.1:8889`. Never commit database credentials. |
| D7 | Landlord database name: `zaristech_saas`. |
| D8 | Tenant database name pattern: `tenant_{slug}`. |
| D9 | Filament Super Admin runs at `/super-admin` against the landlord database. |
| D10 | Krayin CRM remains at `/admin` (or the configured `APP_ADMIN_PATH`) against the tenant database. |
| D11 | Keep Krayin Bouncer/ACL for tenant authorization; do not add Spatie Permission in v1. |
| D12 | Use custom `plans`, `plan_features`, and `tenant_features` tables for feature entitlements; do not use Pennant in v1. |
| D13 | A tenant feature override takes precedence over its plan default. |
| D14 | Never trust `tenant_id` from a request; resolve the tenant from its domain. |
| D15 | Do not destroy unrelated projects or commit secrets. |
| D16 | Context7 MCP is OPTIONAL preference only — never a hard dependency. If Context7 is unavailable or incomplete, implement immediately using official docs (laravel.com, Spatie, Filament) and/or installed vendor/package source. Do not mark any phase BLOCKED solely because Context7 failed. Note the fallback in SAAS_HANDOFF and continue. |

## V1 exclusions

- UnifiedInbox, WhatsApp, AI, or HelpDesk ports from another project.
- Replacing Bouncer with Spatie Permission.
- Stripe billing.
- Migrating `zaristech_hub_portal` data into a tenant database.
- Stancl Tenancy.
