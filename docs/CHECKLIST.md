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
- [x] Tenant migrate/seed Artisan wrappers are available.
- [x] CLI provisioned `demo` with a tenant Administrator whose stored password hash matches the configured local credential; the credential was not displayed.
- [x] Reserved and malformed slug/domain validation is enforced; duplicate tenant/domain/database/storage artifacts are refused.
- [x] Controlled failure after creating `tenant_demo_rollback` removed that database, its landlord records, and its storage path.
- [x] Isolated provisioning tests cover success, migration/seeding invocation, admin creation, tenant DB configuration, connection errors, and cleanup.

Live verification created only the previously absent `tenant_demo`, confirmed its 60-table schema, 93 migrations, landlord tenant/domain/subscription/feature rows, Administrator, and successful tenant DB switching. `zaristech_saas` remained at nine tables and `tenant_bootstrap` at 60; all other pre-existing schema names remained unchanged. No protected or pre-existing database was dropped.

## PHASE 5 — Filament Super Admin

- [ ] Filament panel is isolated at `/super-admin` and authenticates landlord platform users.
- [ ] Tenant, Domain, Plan, Subscription, and feature override resources are available.
- [ ] Tenant creation uses `TenantProvisioningService`.
- [ ] Tenant CRM users cannot access the platform panel.
- [ ] Tenant database passwords are not shown in the UI.

## PHASE 6 — Feature entitlements

- [ ] Entitlement resolution applies tenant override before plan default.
- [ ] Feature middleware returns 403 when a feature is disabled.
- [ ] Disabled features are hidden from the relevant admin menu.
- [ ] Entitlement cache is invalidated when a platform admin changes an override.
- [ ] Tests or a manual verification checklist cover these behaviors.

## PHASE 7 — Storage and queues

- [ ] Tenant files are rooted under `storage/tenants/{slug}`.
- [ ] Tenant switching updates the filesystem root.
- [ ] Queue jobs preserve the correct tenant context.
- [ ] Tenant A files cannot be read through tenant B path helpers.
- [ ] Worker command and setup are documented in `ENV_NOTES.md`.

## PHASE 8 — Isolation tests and hardening

- [ ] Domain A resolves to tenant A's database and Domain B to tenant B's database.
- [ ] Disabled feature requests return 403.
- [ ] Tenant users cannot access `/super-admin`.
- [ ] Queue jobs preserve tenant context.
- [ ] Cross-tenant data reads fail.
- [ ] Relevant Pest suite passes.
- [ ] Handoff records `V1_COMPLETE` or clearly lists remaining known issues.
