# Scratch codegen recordings

Do not commit raw codegen as the suite of record. Finalized coverage is in:

- `../super-admin.spec.ts` — login, smoke pages, health, settings
- `../operator-flows.spec.ts` — tenant/feature/invoice/audit flows cleaned from a codegen session

Capture a throwaway recording:

```bash
./scripts/codegen-super-admin.sh tests/saas/recorded/scratch.spec.ts
```
