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

- [ ] Tenant database manager creates and drops tenant databases.
- [ ] Provisioning service migrates, seeds, creates an admin, and compensates on failure.
- [ ] Tenant migrate/seed Artisan wrappers are available.
- [ ] CLI can provision a test tenant with a login-capable admin.
- [ ] Reserved domain validation is enforced.

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
