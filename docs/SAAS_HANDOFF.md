# SAAS HANDOFF — read me first

## Snapshot

- Date: 2026-10-09 07:26 +05:00
- Agent/tool last used: GitHub Copilot in VS Code
- Working branch: `docs/git-branch-workflow`
- Current phase: PHASE 1 — Bootstrap official Krayin
- Phase status: NOT_STARTED

## Last completed

- PHASE 0 — Created the six continuity documents. No application code or dependencies were changed.
- Added a mandatory Git branch hierarchy and milestone-commit workflow to the agent plans and coordinator prompt.

## Next concrete step

1. Start PHASE 1 by creating or resuming `phase/01-bootstrap-krayin` from `main`, preserving any unrelated work.
2. Install official `krayin/laravel-crm` 2.2 in `/Users/ali/Desktop/krayin-saas-crm`, following the GitHub Copilot plan copy.
3. Before any Laravel or related-package API work, query Context7 for the relevant current documentation.
4. Configure local MAMP MySQL settings without committing `.env` or credentials, then verify `php artisan --version` and application boot as described in the plan.

## Commands already run

- `git status` — at task start the six PHASE 0 continuity documents were staged and the branch was `main`.
- `git log -5 --oneline` — latest visible commits: `592703e`, `6898d62`, `d524924`.
- Created and checked out `docs/git-branch-workflow` for this documentation work.
- No install, build, migration, or application commands were run.

## Files touched this session

- `docs/PROGRESS.md`
- `docs/SAAS_HANDOFF.md`
- `docs/DECISIONS.md`
- `docs/CHECKLIST.md`
- `docs/CHANGELOG_SAAS.md`
- `docs/ENV_NOTES.md`
- `docs/agents/PLAN_GITHUB_COPILOT.md`
- `docs/agents/PLAN_OPENAI_CODEX.md`
- `docs/agents/PLAN_CLAUDE.md`
- `docs/agents/COORDINATOR_MESSAGE.md`
- `~/.cursor/plans/krayin_multi-tenant_saas_3c0b1cff.plan.md`

## Blockers / environment notes

- MAMP MySQL on `127.0.0.1:8889` has not been checked.
- The current workspace did not contain the application bootstrap files at handoff creation.
- Do not store credentials in repository documentation; use a local untracked `.env`.
- Context7 is mandatory before Laravel, Spatie Multitenancy, Filament, or Livewire framework/API work.
- Do not push or merge `docs/git-branch-workflow` into `main` unless the user requests it.

## Do NOT

- Use Stancl or shared-database `tenant_id` tenancy.
- Put CRM business tables on the landlord database.
- Replace Krayin Bouncer/ACL with Spatie Permission in v1.
- Port UnifiedInbox or other out-of-scope custom modules in v1.
- Trust a request-provided tenant ID; resolve tenants from their domain.
