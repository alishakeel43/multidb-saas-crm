# Phase Acceptance Checklist

Check a phase only after its acceptance criteria are verified and its status is updated in `PROGRESS.md`.

## PHASE 0 — Handoff docs

- [x] All six continuity documents exist.
- [x] Locked decisions are recorded in `DECISIONS.md`.
- [x] `PROGRESS.md` marks only PHASE 0 complete and identifies PHASE 1 as next.
- [x] `SAAS_HANDOFF.md` gives the exact next phase and action.

## PHASE 1 — Bootstrap official Krayin

- [x] Official `krayin/laravel-crm` v2.2.6 is installed in the workspace.
- [x] `php artisan --version` succeeds.
- [x] The app boots and registers routes without fatal errors.
- [x] `composer.json` identifies `krayin/laravel-crm` and Laravel 12.
- [x] Install commands and non-secret environment notes are recorded.

## PHASE 2 — Spatie Multitenancy foundation

- [x] Landlord and tenant database connections are configured for MAMP MySQL.
- [x] Domain-based tenant finder and tenant database-switch task are configured.
- [x] Landlord database `zaristech_saas` exists; PHASE 3 added only SaaS metadata tables there.
- [x] A manual current-tenant smoke test queries the tenant database (`tenant_bootstrap`).

PHASE 2 registered the domain finder and database switch task. PHASE 3 integrates the finder with the landlord `Domain` model and rejects inactive domains and non-active tenants.

## PHASE 3 — Landlord schema and models

- [x] Landlord migrations and models cover Tenant, Domain, Plan, PlanFeature, TenantFeature, Subscription, PlatformUser, and PlatformSetting.
- [x] Models use the landlord connection.
- [x] Landlord migrations run cleanly.
- [x] Default plan and feature keys are seeded.

## PHASE 4 — Tenant provisioning

- [x] Tenant database manager creates tenant databases and drops only databases it created itself.
- [x] Provisioning service migrates, safely seeds, creates an explicit-credential Administrator, and compensates on failure.
- [x] New tenant provisioning inherits plan feature defaults without creating tenant override rows.
- [x] Tenant migrate/seed Artisan wrappers are available.
- [x] CLI provisioned `demo` with a tenant Administrator whose stored password hash matches the configured local credential; the credential was not displayed.
- [x] Reserved and malformed slug/domain validation is enforced; duplicate tenant/domain/database/storage artifacts are refused.
- [x] Controlled failure after creating `tenant_demo_rollback` removed that database, its landlord records, and its storage path.
- [x] Isolated provisioning tests cover success, migration/seeding invocation, admin creation, tenant DB configuration, connection errors, and cleanup.

Live verification created only the previously absent `tenant_demo`, confirmed its 60-table schema, 93 migrations, landlord tenant/domain/subscription/feature rows, Administrator, and successful tenant DB switching. `zaristech_saas` remained at nine tables and `tenant_bootstrap` at 60; all other pre-existing schema names remained unchanged. No protected or pre-existing database was dropped.

## PHASE 5 — Filament Super Admin

- [x] Filament panel is isolated at `/super-admin` and authenticates landlord platform users through its dedicated `platform` guard.
- [x] Tenant, Domain, Plan, Subscription, plan feature, and tenant feature override resources are available.
- [x] Tenant creation uses `TenantProvisioningService` and does not retain the one-time administrator password in component state.
- [x] Tenant CRM users cannot access the platform panel; inactive platform users receive 403.
- [x] Tenant database names and credentials are omitted from the Super Admin UI.
- [x] A secure interactive `platform-admin:create` command bootstraps the first platform administrator without accepting a password argument.
- [x] Authentication and tenant form tests, route registration, Pint, and the full test suite pass.

## PHASE 6 — Feature entitlements

- [x] Entitlement resolution applies tenant override before plan default, then configured default; unknown features/no tenant are disabled.
- [x] Feature middleware returns 403 when a mapped stock-module feature is disabled, including dependent features whose parent is disabled.
- [x] Every stock Krayin admin menu item maps to an entitlement; disabled features are hidden and parents point to available children.
- [x] Declared feature dependencies cascade effective disablement transitively, even when a child has an enabled override.
- [x] Entitlement cache is invalidated when overrides, plan feature defaults, or tenant plan assignments change.
- [x] Pest coverage verifies precedence, defaults, dependency cascades, all admin menu mappings, disabled access, route specificity, and cache invalidation.

## PHASE 7 — Storage and queues

- [x] Tenant files are rooted under `storage/tenants/{slug}`.
- [x] Tenant switching updates the filesystem root, including disks already resolved by Laravel.
- [x] Queue jobs preserve the correct tenant context on an asynchronous database worker.
- [x] Tenant A files cannot be read through tenant B path helpers.
- [x] Worker command and setup are documented in `ENV_NOTES.md`.

## PHASE 8 — Isolation tests and hardening

- [x] Domain A resolves to tenant A's database and Domain B to tenant B's database.
- [x] Disabled feature requests return 403.
- [x] Tenant users cannot access `/super-admin`.
- [x] Queue jobs preserve tenant context.
- [x] Cross-tenant data reads fail.
- [x] Relevant Pest suite passes.
- [x] Handoff records `V1_COMPLETE` and clearly lists remaining known issues.

## PHASE 9 — Port playbook + continuity docs

- [x] `docs/agents/PLAN_PORT_FEATURES_COPILOT.md` exists with paste prompt, scope, adaptation checklist, P9–P16 criteria.
- [x] `docs/agents/README.md` points Wave-1 work at the port playbook.
- [x] `docs/agents/COORDINATOR_MESSAGE.md` has Message B for Wave 1.
- [x] `docs/DECISIONS.md` records D19–D22.
- [x] `docs/PROGRESS.md` / `docs/SAAS_HANDOFF.md` list P9–P16 and next action PHASE 9/10.
- [x] No product/package code required for PHASE 9 completion.

## PHASE 10 — Task / To-Do (multi-DB)

- [x] `packages/Webkul/Task` registered (concord, Composer, provider).
- [x] Migrations have no `tenant_id` / no FK to `tenants`.
- [x] No `HasTenant` / TenantManagement imports.
- [x] `tasks` feature key in `config/features.php`; ACL + menu wired.
- [x] Reminder command fans out via landlord tenants + `makeCurrent()`.
- [x] Two-database isolation tests pass; CRUD/kanban tests pass without shared-DB assumptions.
- [x] `tenants:migrate` applies Task schema to existing tenants.

## PHASE 11 — Activity reminders

- [x] Reminder schema on tenant DB without `tenant_id`.
- [x] Dispatch fans out over active landlord tenants.
- [x] Jobs are tenant-aware; no cross-tenant notification leakage.
- [x] Focused reminder tests pass.

## PHASE 12 — Lead kanban + Product Entrusted

- [x] Kanban assignee / activity / quick-view (or equivalent source UX) works on tenant host.
- [x] Product Entrusted migrations/label/text storage applied safely on tenant DBs.
- [x] Focused tests cover label/storage; no UnifiedInbox files introduced.

## PHASE 13 — Workspace timezone / locale

- [x] App-level clock/locale reads `core_config` from current tenant connection.
- [x] No TenantManagement dependency; no session `active_tenant_id`.
- [x] Sequential `makeCurrent()` for two tenants does not permanently clobber process timezone incorrectly.
- [x] Focused timezone/locale tests pass.

## PHASE 14 — Dashboard correctness

- [x] Win/lost share, empty previous-period, null-pipeline (and related) fixes ported.
- [x] No cross-tenant analytics queries.
- [x] Focused dashboard tests pass.

## PHASE 15 — Zaristech branding

- [x] Logos / primary color `#2846A0` / lang keys applied where appropriate.
- [x] Filament Super Admin branding remains platform-consistent.
- [x] No TenantManagement platform Blade layouts reintroduced.

## PHASE 16 — Wave 1 regression + handoff

- [x] Two-tenant isolation matrix covers Task, reminders, kanban/entrusted, timezone.
- [x] Task feature disabled → 403 + menu hidden.
- [x] Full `php artisan test --compact` passes (upstream deprecations OK).
- [x] Pint and `git diff --check` pass.
- [x] HANDOFF records Wave 1 DONE; deferred modules listed PENDING only.
