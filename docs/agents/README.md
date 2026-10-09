# Agent plan copies

**Domains/URLs:** [../DOMAIN_AND_URLS.md](../DOMAIN_AND_URLS.md)

**Coordinator:** kisi bhi agent ko assign karne se pehle yeh message copy-paste karo → [COORDINATOR_MESSAGE.md](./COORDINATOR_MESSAGE.md)

## Which plan to use

| Program | File | Use when |
|---------|------|----------|
| **Wave 1 feature port (P9–P16)** | [PLAN_PORT_FEATURES_COPILOT.md](./PLAN_PORT_FEATURES_COPILOT.md) | Post-V1: Task, reminders, kanban, Product Entrusted, timezone, dashboard, branding |
| V1 SaaS foundation (P0–P8) | [PLAN_GITHUB_COPILOT.md](./PLAN_GITHUB_COPILOT.md) | Historical / reference only (V1 is complete) |
| V1 (Codex copy) | [PLAN_OPENAI_CODEX.md](./PLAN_OPENAI_CODEX.md) | Same V1 content |
| V1 (Claude copy) | [PLAN_CLAUDE.md](./PLAN_CLAUDE.md) | Same V1 content |

**Current work:** If `docs/PROGRESS.md` shows incomplete PHASE 9–16 items, use **PLAN_PORT_FEATURES_COPILOT.md**. Do not restart V1 phases 0–8.

## Kaise use karein

1. Pehle repo mein `docs/SAAS_HANDOFF.md` aur `docs/PROGRESS.md` check karo.
2. Wave 1 ke liye `PLAN_PORT_FEATURES_COPILOT.md` attach/open karo.
3. Usi file ke upar wala **Paste this prompt** block copy karke session start karo.
4. Agent sirf `PROGRESS.md` ki next incomplete PHASE 9–16 kare; khatam par handoff docs update kare.

## Important

- **Context7 OPTIONAL** — pe depend mat karo. Jo process se implement ho (official docs / vendor), wohi karo; Context7 fail = phase block NAHI.
- **Never copy** shared-DB `TenantManagement` / `tenant_id` CRM isolation from `Krayin_laravel_CRM_local`.
- Runtime status sirf `docs/SAAS_HANDOFF.md` + `docs/PROGRESS.md` mein update hota hai.
