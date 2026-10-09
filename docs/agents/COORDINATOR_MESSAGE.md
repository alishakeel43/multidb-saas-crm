# Coordinator message — copy & send to any agent

Neeche wala block **poora copy** karke Copilot / Claude / Codex / Cursor ko bhejo.
Iske alawa pehli baar kuch explain karne ki zaroorat nahi — agent files padh kar kaam start karega.

---

## Message (English — best for all agents)

```text
You are continuing an existing project. Do not redesign the architecture.

PROJECT PATH:
/Users/ali/Desktop/krayin-saas-crm

STARTUP (mandatory, in this order):
1. Read docs/SAAS_HANDOFF.md
2. Read docs/PROGRESS.md
3. Read docs/DECISIONS.md
4. Read docs/CHECKLIST.md
5. Read the plan copy for your tool:
   - GitHub Copilot → docs/agents/PLAN_GITHUB_COPILOT.md
   - OpenAI Codex → docs/agents/PLAN_OPENAI_CODEX.md
   - Claude → docs/agents/PLAN_CLAUDE.md
   - Cursor → docs/agents/PLAN_CLAUDE.md (same plan) OR ~/.cursor/plans/krayin_multi-tenant_saas_3c0b1cff.plan.md

IF docs/SAAS_HANDOFF.md or docs/PROGRESS.md is missing:
→ Start at PHASE 0 only (create handoff docs). Do not skip ahead.

THEN:
→ Do ONLY the first phase in docs/PROGRESS.md that is not DONE.
→ Do not start later phases in the same session unless PHASE is fully DONE and checklisted.

HARD RULES:
- MANDATORY: Use the Context7 MCP for Laravel (and related packages: Spatie Multitenancy, Filament, Livewire) before writing or changing framework/API code. Do not rely on memory or guesswork for Laravel 12 APIs — look up docs via Context7 MCP first.
- One shared Krayin CRM codebase (official Krayin 2.2).
- Separate MySQL database per tenant (Spatie laravel-multitenancy).
- Central/landlord DB: zaristech_saas.
- MAMP MySQL: 127.0.0.1 port 8889.
- Filament Super Admin at /super-admin only.
- Krayin CRM stays at /admin.
- Keep Krayin Bouncer/ACL for tenant permissions (NO Spatie Permission in v1).
- NO Stancl Tenancy.
- NO shared-DB tenant_id multi-tenancy.
- NO UnifiedInbox / custom module port in v1.
- Never trust tenant_id from the request; tenant comes from domain.
- Do not put CRM business tables on the landlord DB.
- Do not commit secrets (.env passwords, API keys).
- Do not implement directly on `main`. Follow §0.5 of the selected plan: use the correct phase/task branch, commit each coherent, verified milestone, and do not push, open PRs, or merge into `main` without user authorization.
- IMPORTANT: Do not add a `Co-authored-by` trailer or assistant co-author attribution to commit messages unless the user explicitly requests it.

WHEN YOU STOP (end of session / limit / pause):
1. Update docs/PROGRESS.md
2. Update docs/SAAS_HANDOFF.md with exact next step
3. Append docs/CHANGELOG_SAAS.md
So the next agent can continue without this chat.

Begin now with the startup reads, then execute the next incomplete phase.
```

---

## Short Roman Urdu note (optional, user → coordinator)

Agar aap khud human coordinator ho aur kisi ko assign kar rahe ho:

```text
Is folder mein kaam karo: /Users/ali/Desktop/krayin-saas-crm
Pehle docs/SAAS_HANDOFF.md aur docs/PROGRESS.md padho.
Phir docs/agents/ mein apne tool ki PLAN_*.md file follow karo.
Sirf agla incomplete PHASE karo.
Khatam par SAAS_HANDOFF + PROGRESS update karna zaroori hai.
Context7 MCP (Laravel) lazmi use karna hai.
Neeche wala English block agent ko paste kar do.
```

---

## Coordinator checklist (aapke liye)

1. MAMP MySQL chal raha ho (port **8889**)
2. Agent ko project folder open / attach ho
3. Upar wala **Message** block paste karo
4. Agent se confirm lo: usne `SAAS_HANDOFF` + `PROGRESS` padha, aur kaunsa PHASE start kiya
5. Session khatam hone se pehle handoff docs update hue ya nahi — check karo
6. Agent Context7 MCP (Laravel) use kar raha ho — lazmi
7. Agent ne sahi phase/task branch use ki aur har coherent, verified milestone ke liye commit banaya — verify karo
