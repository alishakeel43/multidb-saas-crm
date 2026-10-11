# Agent skills (canonical)

Canonical skill source for this repo: `.github/skills/`.

Agent runtimes symlink here (do not duplicate `SKILL.md` elsewhere):

- `.ai/skills` → `../.github/skills`
- `.claude/skills` → `../.github/skills`
- `.codex/skills` → `../.github/skills`
- `.cursor/skills` → `../.github/skills`
- `.kilocode/skills` → `../.github/skills`

Validate with:

```bash
bash bin/validate-skills.sh
```

| Skill | Use when |
|-------|----------|
| `crm-attributes-vs-schema` | Choosing Attributes (EAV) vs package migrations for new fields/features. |
| `crm-package-development` | *(planned)* New CRM package/module layout and Concord flow. |
| `pest-testing` | *(planned)* Pest unit/feature tests. |
