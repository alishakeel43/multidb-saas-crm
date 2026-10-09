# Agent plan copies (same plan × 3)

**Domains/URLs:** [../DOMAIN_AND_URLS.md](../DOMAIN_AND_URLS.md)

**Coordinator:** kisi bhi agent ko assign karne se pehle yeh message copy-paste karo → [COORDINATOR_MESSAGE.md](./COORDINATOR_MESSAGE.md)

Ye folder **ek hi plan** ki teen copies rakhta hai taake Cursor limit ke baad bhi kaam continue ho sake.

| File | Use with |
|------|----------|
| [PLAN_GITHUB_COPILOT.md](./PLAN_GITHUB_COPILOT.md) | GitHub Copilot Chat / Copilot Workspace |
| [PLAN_OPENAI_CODEX.md](./PLAN_OPENAI_CODEX.md) | OpenAI Codex (CLI / Cloud) |
| [PLAN_CLAUDE.md](./PLAN_CLAUDE.md) | Claude.ai / Claude Code / Cloud |

## Kaise use karein

1. Pehle repo mein `docs/SAAS_HANDOFF.md` aur `docs/PROGRESS.md` check karo (agar nahi hain → PHASE 0).
2. Apne agent mein uski plan file open/attach karo.
3. Usi file ke upar wala **Paste this prompt** block copy karke session start karo.
4. Agent sirf `PROGRESS.md` ki next incomplete phase kare; khatam par handoff docs update kare.

## Important

- **Context7 OPTIONAL** — pe depend mat karo. Jo process se implement ho (official docs / vendor), wohi karo; Context7 fail = phase block NAHI.
- Details: har `PLAN_*.md` aur [COORDINATOR_MESSAGE.md](./COORDINATOR_MESSAGE.md).
- Teenon files ka **architecture content same** hona chahiye.
- Runtime status sirf `docs/SAAS_HANDOFF.md` + `docs/PROGRESS.md` mein update hota hai (teen plan copies mein phase checkboxes duplicate mat rakho as source of truth).
