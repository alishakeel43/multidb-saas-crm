# Domain & URL process (correct)

One shared codebase (`krayin-saas-crm/public`). Tenant identity comes **only from the request hostname** (landlord `domains` table). Never from `?tenant_id=` or form fields.

## Local base (this machine)

| Item | Value |
|------|--------|
| SaaS Apache vhost | `krayinsaas.localhost` + `*.krayinsaas.localhost` |
| Document root | `/Users/ali/Desktop/krayin-saas-crm/public` |
| Port | `8888` (MAMP Apache) |
| Config file | `/Applications/MAMP/conf/apache/extra/httpd-vhosts.conf` |
| Old project (do not overwrite) | `krayin.localhost` → `Krayin_laravel_CRM_local` |

## Correct URL model

```text
                    krayinsaas.localhost (base)
                              |
         +--------------------+--------------------+
         |                                         |
         v                                         v
  PLATFORM HOST                              TENANT HOSTS
  platform.krayinsaas.localhost           {slug}.krayinsaas.localhost
         |                                         |
         v                                         v
  /super-admin  (Filament)                    /admin  (Krayin CRM)
  landlord DB                                 that tenant's DB
  guard: platform                             guard: user (Bouncer)
```

### Platform Super Admin (correct)

| | |
|--|--|
| **Preferred host** | `platform.krayinsaas.localhost` (local) / `platform.yoursaas.com` (prod) |
| **Path** | `/super-admin` and `/super-admin/login` |
| **Database** | Always **landlord** (`zaristech_saas`) |
| **Auth** | `platform` guard + `platform_users` only |
| **Who** | Super Admin only — never tenant CRM users |

**Rules:**

- Super Admin is a **platform** surface, not a tenant CRM feature.
- Login must succeed only with platform credentials.
- Tenant Bouncer users must not gain platform access.
- Prefer serving Super Admin on the **platform host** (not on arbitrary tenant subdomains).
- v1 transitional note: path `/super-admin` may still respond on the apex host `krayinsaas.localhost` because Filament is path-based and the wildcard vhost shares one `public/`. That does **not** make it a tenant app — auth and data stay landlord. Later hardening may **403** `/super-admin` on pure tenant hosts (`{slug}.krayinsaas.localhost`) and allow it only on `platform.*` (and optionally apex).

### Tenant CRM (correct)

| | |
|--|--|
| **Host** | `{slug}.krayinsaas.localhost` (local) / `{slug}.yoursaas.com` (prod) |
| **Path** | `/admin` and `/admin/login` (or `APP_ADMIN_PATH`) |
| **Database** | That tenant’s MySQL DB (`tenant_{slug}`) |
| **Auth** | Krayin `user` guard + Bouncer/ACL |
| **Who** | That tenant’s users only |

**Rules:**

- Hostname must exist in landlord `domains.domain` and tenant must be `active`.
- Spatie `DomainTenantFinder` switches the tenant DB before CRM runs.
- Tenant A host must never load Tenant B data.

### Current local demo tenant

For convenience during early phases, the `demo` tenant’s primary domain may be the apex:

- `http://krayinsaas.localhost:8888/admin/login` → Demo CRM
- `http://krayinsaas.localhost:8888/super-admin/login` → Platform panel (landlord; platform credentials)

**Preferred long-term demo CRM host:** `http://demo.krayinsaas.localhost:8888/admin/login`
**Preferred Super Admin host:** `http://platform.krayinsaas.localhost:8888/super-admin/login`

When provisioning new tenants, register domains as `{slug}.krayinsaas.localhost` (local) unless a custom domain is intentionally added.

## Production pattern

| Surface | Example |
|---------|---------|
| Platform | `https://saas.zaristech.com/super-admin` |
| Tenant | `https://zdemo-saas.zaristech.com/admin` |
| DNS | `*.zaristech.com` → same app server; Cloudflare proxied |
| Web server | SaaS vhost aliases `*-saas.zaristech.com` to this app's `public/` |

Set `TENANT_BASE_DOMAIN=saas.zaristech.com` and
`TENANT_DOMAIN_SUFFIX=-saas.zaristech.com`. The flat tenant hostname stays
inside one subdomain label, matching the existing `*.zaristech.com` edge and
origin certificates. Do not use `{slug}.saas.zaristech.com` unless the edge and
origin certificates explicitly cover `*.saas.zaristech.com`.

## How you know which tenant it is

Look at the **hostname** in the browser:

- `acme.krayinsaas.localhost` → tenant slug/domain `acme`
- `demo.krayinsaas.localhost` → tenant `demo`
- `platform.krayinsaas.localhost` → **no** tenant CRM; platform only

Landlord `domains` + `tenants.slug` / `tenants.database` are the source of truth.

## Do NOT

- Put Super Admin only behind tenant CRM auth.
- Resolve tenant from request body/query `tenant_id`.
- Point `krayin.localhost` at this SaaS repo (that host belongs to the old local project).
- Create a separate Laravel install per tenant.
