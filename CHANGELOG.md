# Changelog

## Unreleased

- Add idempotent `BillingDemoSeeder` with three sample invoices (open / paid / void) so Super Admin billing UI can be exercised before Stripe go-live.
- Fix tenant CRM baseline seeding skipping AttributeSeeder when Installer migrations already inserted a few attribute rows (e.g. job_title), which left persons.organization_id missing and crashed lead contact forms.
- Align SaaS feature entitlements with Krayin Contacts: one Contacts module now gates both Persons and Organizations menus (legacy `organizations` key remains as an alias) so tenant sidebars cannot show one without the other.
- Fix tenant storage-quota middleware calling `Request::hasFile()` with no key (Laravel 12 requires a file field name); skip the check when the request has no uploads via `allFiles()`.
- Fix Audit log View crashing on nested activity properties by flattening array values for Filament’s KeyValueEntry.
- Clarify empty Audit log Tenant ID for platform-level events (settings/MFA) with an explicit “no tenant” placeholder.
- Always show a “Create new tenant/plan” action on related dropdowns (domains, subscriptions, invoices, support users, feature matrices)—not only when the list is empty—so operators can open create in a new tab without leaving the form.
- Show tenant provisioning validation errors on the correct Create Tenant fields (for example administrator email under the email input, not under slug).
- Replace Super Admin plan/tenant feature CRUD lists with a category checkbox matrix (Krayin role-permissions style), including one-click enable/disable for an entire module category.
- Block plan/tenant module saves that disable a required dependency (for example Leads while Quotes stays enabled) and show a clear error listing the missing requirements.
- Fix Super Admin listing pagination so “Showing X to Y of Z results” and page-number links remain visible with the sidebar layout (Filament’s 56rem container breakpoint was hiding them).
- Fix Super Admin overview widgets rendering in the initial page HTML (eager load) so stats/charts stay visible when Livewire deferred updates fail in production.
- Add SaaS P0 platform foundations: admin invoices + Stripe Checkout webhooks, subscription lifecycle tick, super-admin TOTP MFA, Spatie activitylog audits, Spatie backup + per-tenant backup/restore, Spatie health checks, platform settings, and storage quotas.
- Polish the ZARIS Super Admin console branding, navigation groups, dashboard stats, and custom operations pages for a more professional operator experience.
- Add Playwright coverage for ZARIS Super Admin SaaS login, protected routes, health checks, and platform settings.
- Finalize Super Admin Playwright operator flows from a codegen session (tenant create validation, feature-matrix dependency errors, invoices/audit navigation) and keep raw recordings under `recorded/scratch`.
- Add an idempotent tenant CRM demo seeder and document how to run it for one or all tenants.
- Ensure every demo contact has a linked lead so org → person → lead coverage is complete.
