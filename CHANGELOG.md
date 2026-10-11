# Changelog

## Unreleased

- Add Sales reports CRM page (metrics snapshot + CSV export) gated by `sales_reports` entitlement.
- Add appointment reminder emails (`bookings:send-reminders --all`, scheduled every 15 minutes) with `reminder_sent_at` tracking.
- Harden Super Admin Playwright operator-flow selectors for Filament “Label *” spacing and Audit log heading variants.
- Document Stripe go-live environment checklist in `docs/ENV_NOTES.md` (keys, webhook, health check).
- Polish Appointments and Booking Settings UI to match Krayin Admin patterns (sticky header, structured form grid, status badges, clearer list rows).
- Fix appointment booking end-to-end: require staff, soft Mon–Fri 09:00–17:00 defaults when no custom availability exists, clearer overlap/outside-hours errors, settings quick-add for default week hours, and empty-state guidance on the Bookings page.
- Add `{%` typeahead for email-template placeholders (Subject + TinyMCE Content): typing `{%l` suggests entities like `leads`, then `{%leads.t` suggests matching Attributes (e.g. title) so templates can be built without memorizing codes.
- Document the Attributes-vs-package-schema decision rule for agents (`crm-attributes-vs-schema` skill + `AGENTS.md`): features use migrations/tables; CRM form fields use Attributes/EAV.
- Add client P0 CRM packages following Krayin Concord package flow (Contract / Model / Proxy / Repository / `ModuleServiceProvider` + `config/concord.php`): lead assignment (round-robin / workload / first-available via `Webkul/Assignment`), appointment booking with Spatie model-states (`Webkul/Booking`), person consent/suppression (`Webkul/Consent`), and quote approval workflow (`Webkul/QuoteApproval`), plus lead-duplicate checks and a `SalesReportService` metrics snapshot; gate them through SaaS feature entitlements.
- Fix Quotes sidebar after Quote Approvals: keep a Quotes list child (Contacts-style) so Create Quote stays reachable when Approvals nests under Quotes.
- Fix empty Settings cards for Assignment/Booking/Consent by nesting them under Other Settings (Krayin Settings index only renders grandchildren as clickable tiles).
- Exclude tenant support/debug users (`is_support_account`) from lead-assignment assignees and booking staff pickers.

- Harden P0 lead/quote create hooks so assignment/approval failures cannot block core CRM create flows; add menu/route regression tests so nested menus cannot silently hide Create pages again.
- Add composite/lookup indexes on P0 CRM package tables for hot paths (assignment rule resolution, booking overlap/availability, consent channel lookups, quote approval queues).
- Add idempotent tenant migrations to backfill P0 CRM performance indexes on existing tenant databases (`php artisan tenants:migrate --all`).
- Add `spatie/laravel-model-states` for professional booking status transitions (pending → confirmed/cancelled/completed/no-show).
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
