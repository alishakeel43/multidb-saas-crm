# Changelog

## Unreleased

- Deliver WhatsApp, Messenger, and Instagram events to this inbox by registering the CRM webhook and a WhatsApp account callback, and sync recent Messenger and Instagram threads after connect.
- Place the inbox new-contact control beside the conversation search bar, and use a compose dialog with a channel choice, recipient, and template or message fields.
- Start a conversation from the inbox: an approved WhatsApp template to any number, or a Messenger or Instagram message to someone who already contacted that account.
- Show the latest connected Meta account on the channel card. A newer Embedded Signup no longer stays hidden behind an older disconnected row for the same channel.
- Fix lead import sample download returning an empty CSV after the tenant storage disk no longer contains the shared sample file.

- Show a “No records found” message in the task form when a related lead, person, organization, or quote search has no matches.
- Save tasks when the due time includes seconds or a related record was not chosen, and return to the task list so the new task is visible.
- Add tenant-database task management: tasks and kanban stages live in each tenant database, with list and board views, lead/person/organization/quote links, and per-tenant due reminders.

- Fix Roles create/edit crashing when the Telephony ACL tree omitted intermediate parent keys (`telephony.call_logs`, `telephony.settings`).
- Fix desktop admin sidebar scrolling so every menu item stays reachable when the list is taller than the viewport, using the CRM thin custom scrollbar instead of the browser default.
- Fix admin logo and favicon uploads on tenant configuration: nested files no longer crash the storage quota check, failed image writes are no longer stored as `0`, and the saved image is served from the current tenant host.
- Add an all-features tenant demo seeder, with a Super Admin button, that inserts linked dummy data for products, inventory, mail, marketing, automation, web forms, telephony, knowledge, and unified inbox channels.
- Add tenant-scoped AI knowledge retrieval to Unified Inbox reply suggestions so relevant indexed document chunks inform WhatsApp and other channel responses.
- Integrate Marketing Automation tools under the existing Marketing campaigns and Workflows settings screens using package view hooks; keep its backend services active without a separate Marketing Automation menu or dashboard.
- Route SaaS tenant domains through single-level `*-saas.zaristech.com` hosts compatible with the existing wildcard certificate and Apache tenant vhost.
- Fix tenant maintenance commands calling the removed MAMP-only database guard.
- Allow tenant database provisioning on explicitly allowlisted production MySQL/MariaDB hosts and ports while protecting the configured landlord and base tenant databases.
- Add Telephony VoIP module scaffold (Webkul/Telephony) with Twilio SDK (`twilio/sdk`), call_logs tenant migration using Krayin-compatible foreign-key types, landlord telephony routing table (`tenant_telephony_routes`), and package skeleton.
- Add Telephony Admin UI: person click-to-call action, call-history panel, call-log index page, and Telephony settings form injected through the person view without touching core files.

- Replace channel-letter placeholders with consistent WhatsApp, Messenger, Instagram, and generic channel SVG icons across inbox filters, conversations, channel cards, and connected accounts.
- Harden Unified Inbox Meta onboarding with tenant/user-bound single-use OAuth state and verified WhatsApp signup account details; secure Meta webhooks with signature and verification-token checks, authoritative landlord channel routing that cannot be reassigned across tenants, resolved-tenant account validation, scoped delivery callbacks, and idempotent inbound message handling. Prevent cross-account replies and false sent states; add unread filtering and paginated conversation/message history.
- Add Unified Inbox package (`Webkul/UnifiedInbox`) with Meta Embedded Signup v4 (WhatsApp, Messenger, Instagram), centralized root webhook URL routing with landlord channel route resolution (guaranteeing root domain without tenant subdomains), reference `.ui-workspace` design system and component structure (`partials/styles`, `partials/header`, `partials/list`, 3-tab CRM drawer, and channel cards), prominent Connect Channels button and multi-channel status indicators on Unified Inbox UI, outbound Graph API messaging, and CRM Contact/Lead linking.
- Fix Unified Inbox blank screen by replacing invalid inline Blade `@php(...)` calls with standard blocks, adding `v-pre` to `.ui-workspace` containers to prevent Vue 3 in-DOM template compilation crashes, and adding safe null checks on conversation list.
- Add demo-tenant P0 CRM smoke coverage (Pest live inventory + admin route checks; Super Admin Playwright Tenant seeders flow).


- Expose CRM baseline / demo+P0 seeders in Super Admin (Operations → Tenant seeders, plus Tenant list/edit actions) via `TenantSeedService`.
- Add `TenantP0DemoSeeder` (via `tenants:seed {slug} --demo`) with idempotent dummy data for Assignment, Booking, Consent, Quote Approvals/fulfillment, Lead scoring, Notifications, and Sales-report-friendly lead outcomes.
- Add in-app CRM Notifications (header bell + inbox) for assignment alerts, upcoming follow-ups, overdue activities, and lead close-date deadlines (`notifications:dispatch-due --all`).
- Add rules-based Lead scoring (Settings → Lead scoring) with persisted `leads.score`.
- Add public booking page (`/book`) and admin reschedule action for appointments.
- Expand Sales reports with win rate, lost leads, converted quotes, average score, and leads-by-source breakdown.
- Add quote delivery + convert-to-order fulfillment on Quote Approvals.
- Surface possible lead duplicates on Create Lead via live duplicate-check banner.
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
