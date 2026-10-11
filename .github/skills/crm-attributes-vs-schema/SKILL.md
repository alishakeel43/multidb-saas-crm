---
name: crm-attributes-vs-schema
description: >-
  Prefers existing Attributes/EAV when a CRM field can work without new schema,
  and uses package migrations only when relationships, constraints, or feature
  tables are required. Use when adding fields, columns, seeders, Settings→Attributes
  work, or designing Webkul package schema for Leads/Persons/Products/Quotes.
---

# Attributes vs package schema

**Priority rule (product intent):**

1. Agar kaam **bina naye schema/migration ke** existing Attributes (`attributes` +
   `attribute_values`) se ho sakta hai → **Attributes prefer karo** (extra entity
   columns / tables mat banao).
2. Agar **relationship, constraint, workflow table, heavy index, security flag,**
   ya aisa domain hai jo EAV se sahi nahi chalta → **migration / package schema
   lazmi**.

Benefit of (1): client Settings → Attributes se label/name/type tweaks kar sakta
hai **bina code change** ke. Yeh “sab kuch attributes” nahi hai — sirf
**unnecessary schema avoid** karna hai.

## Decision checklist (run before coding)

Ask in order:

1. **Schema lazmi hai?** FK / unique / status machine / overlap queries / new
   feature tables (assignment, booking, consent, approvals, billing) / ACL /
   jobs / platform flags (`is_support_account`, …)
   → **Package migration + tables**. Attributes galat tool hain.
2. **Warna** — Lead / Person / Organization / Product / Quote pe form field /
   metadata jo display + light filter ke liye hai, label baad mein change ho
   sakti hai
   → **Attribute** pe extend karo. `leads.xyz` / `persons.xyz` column mat add karo.

## Attributes (prefer when schema not required)

- Definition: `attributes` (`code`, `name`, `type`, `entity_type`, …).
- Values: `attribute_values` (EAV).
- Product defaults: seed `is_user_defined = 0` (Installer `AttributeSeeder`).
- Tenant-owned: Settings → Attributes (`is_user_defined = 1`).
- Rename/label: `attributes.name` / UI — no column-rename migration.
- Entities with `CustomAttribute` (e.g. Lead) already load values.

## Package schema (when Attributes cannot do the job)

- Assignment rules + assignees, booking slots, consent rows, quote approvals,
  invoices, quotas, etc.
- Joins, uniqueness, overlap checks, reliable reporting indexes.
- Code-hard dependencies that must not be tenant-renamed into breaking shape.

## Anti-patterns

- Workflows (assignment/booking/approval) ko Lead attributes mat banao.
- Jab Attribute kaafi ho, entity pe extra column mat banao “for convenience”.
- High-traffic join/filter keys sirf `attribute_values` mein mat rakho.

## Quick examples

| Need | Choice |
|------|--------|
| Lead “Industry” / “Preferred channel” | Attribute (prefer) |
| Client label rename without deploy | Attribute `name` |
| Assignment rule + assignees | Package migration |
| Booking start/end + staff | Package migration |
| Quote approval state | Package tables / model states |
| Person consent per channel | Package migration |
