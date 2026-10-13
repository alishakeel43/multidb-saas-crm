# AI Knowledge Center audit

Audit date: 2026-10-13

Audited tree: branch `bk-1-26-friday` at `c6df02d` (`docs: update SaaS handoff and environment notes`).

This document records what the source actually does. A class, route, button, or migration is not treated as a completed feature. External services that are not running here (Ollama, Qdrant, WhatsApp, MAMP MySQL) are not marked successful.

## 1. Current architecture

- Product baseline is official Krayin CRM **2.2.6** (`Webkul\Core\Core::KRAYIN_VERSION`).
- PHP requirement is `^8.3` (`composer.json`). This audit environment ran **PHP 8.3.6**. Lockfile resolves **Laravel Framework v12.66.0**, **Spatie Multitenancy 4.2.1**, **Filament v3.3.56**, **Pest v3.8.6**, **Pint v1.29.1**, **smalot/pdfparser v2.12.5**.
- Modules live under `packages/Webkul/`. On this branch they are the stock Krayin packages (Activity, Admin, Attribute, Automation, Contact, Core, DataGrid, DataTransfer, Email, EmailTemplate, GoogleContact, Installer, Lead, Marketing, Product, Quote, Tag, User, Warehouse, WebForm). There is **no** `AiKnowledge`, `UnifiedInbox`, or `Telephony` package in the working tree.
- Tenancy is database-per-tenant via Spatie. `config/multitenancy.php` uses `App\Multitenancy\TenantFinder\DomainTenantFinder` and tasks `SwitchTenantStorageTask` then `SwitchTenantDatabaseTask`. `queues_are_tenant_aware_by_default` is true. Tenant connection name is `tenant`; landlord connection name is `landlord`.
- `DomainTenantFinder::findForRequest()` loads `Domain` by `$request->getHost()` where `is_active` is true and the related tenant `status` is `active`. It does not read a tenant id from the request.
- Default DB connection is `tenant` (`config/database.php`). Landlord models use `UsesLandlordConnection`. Tenant CRM tables stay on the switched tenant connection.
- Files for the current tenant are rooted at `storage/tenants/{slug}/app` (local) and `storage/tenants/{slug}/app/public` (public) by `App\Multitenancy\Tasks\SwitchTenantStorageTask`.
- Queue jobs and failed jobs are configured on the **landlord** connection (`config/queue.php` `DB_QUEUE_CONNECTION` / `QUEUE_FAILED_DATABASE`, default `landlord`) so a worker can see jobs for every tenant. `.env.example` still defaults `QUEUE_CONNECTION=sync`.
- Scheduler (`routes/console.php`) runs only `inbound-emails:process` every five minutes. There is no AI, embedding, or WhatsApp schedule.
- Auth has two guards in `config/auth.php`: `user` (Krayin `Webkul\User\Models\User`, tenant DB, Bouncer ACL) and `platform` (`App\Models\PlatformUser`, landlord DB, Filament `/super-admin`). Feature entitlements are enforced by `App\Http\Middleware\FeatureEntitlementMiddleware` for named `admin.*` routes when a tenant is current.
- Locked decision D3 / the V1 exclusion list in `docs/DECISIONS.md` still say Unified Inbox, WhatsApp, and AI ports are out of V1. This audit does not reopen tenancy, Bouncer, or billing decisions.

### Only AI code on this branch

Stock Krayin **Magic AI** extracts lead fields from an uploaded file and sends text to OpenRouter. It is not a knowledge center.

- Config UI: `packages/Webkul/Admin/src/Config/core_config.php` keys `general.magic_ai.settings` (enable, API key, model select, other model) and `general.magic_ai.doc_generation.enabled`.
- Route: `POST admin/leads/create-by-ai` named `admin.leads.create_by_ai` in `packages/Webkul/Admin/src/Routes/Admin/leads-routes.php`.
- `LeadController::createByAI()` / `processFile()` (`packages/Webkul/Admin/src/Http/Controllers/Lead/LeadController.php`).
- `Webkul\Lead\Services\MagicAIService` (PDF text via `Smalot\PdfParser\Parser`, HTTP POST to `https://openrouter.ai/api/v1/chat/completions`).
- `Webkul\Lead\Helpers\MagicAI::mapAIDataToLead()`.
- Lead index shows the control only when `general.magic_ai.doc_generation.enabled` is true (`packages/Webkul/Admin/src/Resources/views/leads/index.blade.php`). The controller does not repeat that check.

### Related code that is not on this branch

`origin/feat/unified-inbox-meta` (tip `47d7326`, 2026-10-13) contains `packages/Webkul/UnifiedInbox`, including `AiProviderFactory`, `OpenAiCompatibleProvider`, `ReplySuggestionService`, and `WebhookController`. That provider can target an OpenAI-compatible base URL (the factory comment names Ollama). It suggests agent replies from the recent transcript. It does not upload knowledge documents, chunk, embed, or search a vector index. Those files were read with `git show` only. They were not booted, migrated, or called. Do not rebuild that inbox on this branch.

## 2. Existing implementations and reusable components

| Piece | Where | Reuse for a knowledge center |
| --- | --- | --- |
| Domain tenant resolution | `DomainTenantFinder::findForRequest()` | Keep. Do not accept a client-supplied tenant id. |
| Tenant DB switch | `SwitchTenantDatabaseTask` | Knowledge rows must be written on the `tenant` connection after `makeCurrent()`. |
| Tenant file root | `SwitchTenantStorageTask::makeCurrent()` | Store uploads on the `local` disk so they follow `storage/tenants/{slug}/app`. |
| Tenant-aware jobs | `config/multitenancy.php` `queues_are_tenant_aware_by_default` | Future extraction jobs can be normal queued jobs dispatched while a tenant is current. |
| Landlord queue storage | `config/queue.php` database + failed connections | Workers stay centralized. |
| Entitlements | `FeatureEntitlementService`, `config/features.php` | A future admin UI needs its own feature key. Unmapped routes are not blocked by this middleware. |
| Tenant ACL | `Webkul\Admin\Http\Middleware\Bouncer` | Fails closed. Unmapped `admin.*` routes inherit the nearest mapped feature. Support accounts are denied non-GET writes. |
| PDF text | `MagicAIService::extractTextFromBase64File()` | Useful later for PDF bytes. It is static, OpenRouter-specific, and not a chunker. Do not call it for knowledge indexing. |
| Repository + Concord | `Webkul\Core\Eloquent\Repository`, `BaseModuleServiceProvider` | New package should follow Tag: contract, model, repository, module provider. |
| Platform admin | Filament panel `/super-admin`, guard `platform` | Knowledge-center settings are tenant CRM data, not landlord data. |

Not present anywhere on this branch: Ollama client, llama.cpp, embedding client, Qdrant or any other vector store, document chunk tables, FAQ/Q&A tables, RAG prompts, retrieval filters, WhatsApp Cloud API, fine-tune dataset export, LoRA/QLoRA.

Composer `require` has no AI SDK. HTTP to OpenRouter uses Laravel's HTTP client inside `MagicAIService` only.

## 3. Status matrix

Statuses: **COMPLETE AND VERIFIED**, **IMPLEMENTED BUT UNVERIFIED**, **PARTIALLY COMPLETE**, **BROKEN**, **NOT IMPLEMENTED**, **BLOCKED**.

### A. UI

| Feature | Status | Evidence |
| --- | --- | --- |
| Dashboard and AI status | NOT IMPLEMENTED | Krayin dashboard and Filament `SuperAdminDashboard` exist. No AI health, index, or provider status page. |
| Company profile / info for answers | NOT IMPLEMENTED | No company-knowledge model or screen. |
| Knowledge document management | NOT IMPLEMENTED | No document routes, views, or menu entry. |
| FAQ / approved Q&A | NOT IMPLEMENTED | No FAQ model, migration, or screen. |
| AI behavior settings | NOT IMPLEMENTED | No tone, fallback, or citation settings. Magic AI's enable flag is lead-extraction only. |
| Model configuration | PARTIALLY COMPLETE | `core_config.php` `general.magic_ai.settings.model` lists OpenRouter model ids (GPT-4o, Gemini, DeepSeek, Llama 3.2, Grok) plus `other_model`. No local endpoint, no health check, no test covering save/load. |
| Conversation testing playground | NOT IMPLEMENTED | No playground route or controller. |
| Publish and enable/disable | NOT IMPLEMENTED | No knowledge publish state. `doc_generation.enabled` only toggles the lead-index control. |
| Usage analytics and error reporting | NOT IMPLEMENTED | No AI usage table, dashboard, or dedicated log channel. |

### B. Knowledge processing

| Feature | Status | Evidence |
| --- | --- | --- |
| Secure uploads | COMPLETE AND VERIFIED | `KnowledgeDocumentIntake::store()` refuses to run without a current tenant, caps size, and writes `ai-knowledge/{uuid}.{ext}` on the tenant `local` disk. Verified by `tests/Unit/KnowledgeDocumentIntakeTest.php`. Stock lead AI upload in `LeadController::processFile()` is still extension-only and was not changed. |
| File-format validation | COMPLETE AND VERIFIED | Intake allow-lists pdf, txt, md, csv, html, htm, and docx, checks MIME/content, and rejects executables, PHP payloads, double extensions such as `notes.php.txt`, and path segments. Same Pest file. Lead AI validation was not changed. |
| Text extraction | PARTIALLY COMPLETE | PDF text via `Parser::parseFile` inside `MagicAIService`. Non-PDF bytes are treated as images and not decoded to text. No tests. |
| Chunking with overlap | NOT IMPLEMENTED | No chunker. |
| Embedding generation | NOT IMPLEMENTED | No embedding client or column. |
| Vector indexing / search | NOT IMPLEMENTED | No vector client. `composer.json` has no Qdrant package. |
| Re-index on update | NOT IMPLEMENTED | No source update flow. |
| Source deletion and vector cleanup | NOT IMPLEMENTED | No knowledge source delete path. |
| Background processing, retries, status | PARTIALLY COMPLETE | `ai_knowledge_documents.status` is stored as `pending` by `KnowledgeDocumentIntake::store()`. There is no queue job, retry, or worker. Magic AI still runs inline. |
| Website ingestion | NOT IMPLEMENTED | No crawler. |

### C. LLM

| Feature | Status | Evidence |
| --- | --- | --- |
| Local Ollama / llama.cpp connectivity | NOT IMPLEMENTED | No host, port, or client on this branch. Llama appears only as an OpenRouter model id `meta-llama/llama-3.2-3b-instruct`. |
| Model config and health checks | PARTIALLY COMPLETE | Model id is stored in Krayin core config. No health-check method. |
| Chat completion and response handling | PARTIALLY COMPLETE | `MagicAIService::ask()` posts chat completions and reads `choices`. Used only to fill lead JSON. `MagicAI::mapAIDataToLead()` strips tags and extracts the first `{...}` blob. |
| Context / output limits | PARTIALLY COMPLETE | `MAX_TOKENS = 100000` is compared with `strlen` and sliced with `mb_substr` in `truncatePrompt()`. The OpenRouter payload sets no `max_tokens`. |
| Error handling, timeouts, concurrency | BROKEN | `Http::post` in `ask()` has no timeout. Provider failures are replaced with `admin::app.leads.file.insufficient-info`. `private static $isExtracting` is process-global. No test covers this. |
| Embedding model integration | NOT IMPLEMENTED | None. |
| Local model setup / deployment docs | NOT IMPLEMENTED | `docs/ENV_NOTES.md` has no Ollama, llama.cpp, or model pull steps. |

### D. RAG

| Feature | Status | Evidence |
| --- | --- | --- |
| Tenant-specific retrieval | NOT IMPLEMENTED | No retriever. |
| Only approved / active sources | NOT IMPLEMENTED | No approval flag. |
| Relevance filtering | NOT IMPLEMENTED | No score threshold. |
| Grounded answers | NOT IMPLEMENTED | Lead prompt does not ground on tenant documents. |
| Fallback when info is missing | NOT IMPLEMENTED | No knowledge fallback. Magic AI returns a generic error or a lead JSON template. |
| Source references and debug metadata | NOT IMPLEMENTED | Responses are not cited. |
| Prompt-injection and unauthorized-retrieval protection | NOT IMPLEMENTED | Uploaded file text is sent as the user message. No retrieval boundary exists to protect. |

### E. WhatsApp

| Feature | Status | Evidence |
| --- | --- | --- |
| Webhook | NOT IMPLEMENTED | No WhatsApp route on this branch. Krayin webhooks are outbound workflow hooks (`admin.settings.webhooks`). |
| Tenant and inbox resolution | NOT IMPLEMENTED | No inbox model. |
| Inbound persistence | NOT IMPLEMENTED | None. |
| Duplicate webhook prevention | NOT IMPLEMENTED | None. |
| Queued AI processing | NOT IMPLEMENTED | None. |
| AI reply delivery | NOT IMPLEMENTED | None. |
| Outbound persistence and delivery status | NOT IMPLEMENTED | None. |
| Human handoff and AI pause/resume | NOT IMPLEMENTED | None. |
| 24h window and template compliance | NOT IMPLEMENTED | None. |

The unmerged Unified Inbox branch has a webhook and reply **suggestions** for an agent. It was not run. It is not marked complete.

### F. Live CRM tools

| Feature | Status | Evidence |
| --- | --- | --- |
| Authorized access to Contacts, Leads, Quotes | NOT IMPLEMENTED | No tool/function layer. `MagicAI::prepareLeadData()` builds a lead payload; `LeadController::createLeads()` writes through lead and person repositories. That is a fixed lead import, not a general tool. |
| Tenant authorization on every tool call | NOT IMPLEMENTED | No tool calls. |
| Argument validation | PARTIALLY COMPLETE | `MagicAI::validateLeadData()` requires title, lead value, person name, email, and phone before create. |
| No arbitrary SQL | COMPLETE AND VERIFIED | Magic AI does not build SQL. Repository creates use Eloquent. Confirmed by reading `MagicAI.php` and `LeadController::createLeads()`. |

### G. Multi-tenant security

| Feature | Status | Evidence |
| --- | --- | --- |
| Tenant isolation in CRM queries | COMPLETE AND VERIFIED | `tests/Unit/TenantStorageTaskTest.php` (`resolves separate domains to isolated tenant databases`) passed in this environment. Finder + sqlite files returned each tenant's own row. |
| Tenant context in queue jobs | COMPLETE AND VERIFIED | Same file, `preserves tenant context when a queued job runs on the database worker`, passed. Probe row recorded `queue-tenant`; worker ended with no current tenant. |
| Tenant-specific files | COMPLETE AND VERIFIED | Same file: local disk root follows the tenant, and tenant B cannot see tenant A's `private/report.txt`. |
| Tenant-specific vector records | NOT IMPLEMENTED | No vector records. |
| Cross-tenant AI retrieval protection | NOT IMPLEMENTED | No retriever. |
| Tenant-specific AI settings | PARTIALLY COMPLETE | Magic AI config is Krayin `core_config` on the tenant database, so it is tenant-scoped once the tenant connection is current. There is no separate knowledge-center settings record. Platform vs tenant admin is a separate guard (`platform` vs `user`). `SuperAdminAccessTest` guard expectations passed; authenticated HTTP panel checks did not run against MySQL here (see section 6). |
| Safe knowledge cleanup | COMPLETE AND VERIFIED | `KnowledgeDocumentIntake::delete()` removes the managed file and the current tenant row. It rejects paths outside `ai-knowledge/{uuid}.{ext}` and refuses a row that is not in the current tenant database. No vector records exist to clean up. |
| Platform vs tenant admin permissions | IMPLEMENTED BUT UNVERIFIED | Code: `AuthenticateSuperAdmin`, `PlatformUser::canAccessPanel`, Bouncer on `user`. HTTP Super Admin cases returned 302 in this environment because MySQL `127.0.0.1:8889` is down and the installer gate is closed. Not re-marked verified. |

### H. Fine-tuning

| Feature | Status | Evidence |
| --- | --- | --- |
| Approved training-example management | NOT IMPLEMENTED | No table or UI. |
| Dataset export and privacy controls | NOT IMPLEMENTED | None. |
| LoRA / QLoRA workflow | NOT IMPLEMENTED | None. Not required for a first release. |
| Evaluation, versioning, rollback | NOT IMPLEMENTED | None. |
| RAG vs fine-tuning distinction | NOT IMPLEMENTED | Nothing in the product explains the difference because neither path exists. |

## 4. Confirmed bugs and security risks

1. **Lead AI create is not mapped to `leads.create`.** `packages/Webkul/Admin/src/Config/acl.php` maps `admin.leads.create` and `admin.leads.store` to `leads.create`. `admin.leads.create_by_ai` is absent. `Bouncer::isAuthorizedByFeature()` then allows the route when the user holds **any** `admin.leads.*` permission, including `leads.view`. A view-only tenant user can POST lead creation. Support accounts are still blocked because the route is not GET (`Bouncer::handle()`). This is inside one tenant, not a cross-tenant leak. Core ACL file; not changed in this pass.
2. **Magic AI HTTP has no timeout and hides provider errors.** `MagicAIService::ask()` can hang on OpenRouter. `catch` returns a generic insufficient-info message, including when the API key or model is missing (`processPromptWithAI` returns that error, then `ask` is not the only path). Callers cannot tell configuration failure from provider failure.
3. **Lead AI upload validation is extension-only.** `processFile()` does not limit size or check content. PDF parser and `file_get_contents` run in the request.
4. **`truncatePrompt()` does not enforce a token limit.** It compares byte length to `100000` and passes a float to `mb_substr`.
5. **Enable flag is UI-only.** The blade checks `general.magic_ai.doc_generation.enabled`. `createByAI()` does not.
6. **Composer reports 8 advisories in 5 packages** (`composer audit`, 2026-10-13): `enshrined/svg-sanitize` (3 medium), `laravel/framework` v12.66.0 (low, debug-page XSS, fixed in 12.69.0), `league/commonmark` (medium and high), `league/flysystem` (low), `maatwebsite/excel` (high, export path escape, CVE-2026-84374). Not upgraded here. Knowledge intake must not pass caller paths to Excel or Flysystem writes.
7. **No cross-tenant isolation bug was found** in `DomainTenantFinder`, `SwitchTenantStorageTask`, or the queue probe. Those tests passed.

Residual product risk: there is no knowledge index yet, so there is also nothing yet that could leak vectors across tenants. The first intake implementation must refuse to store files when no tenant is current.

## 5. Missing integrations and dependencies

- No Ollama, llama.cpp, OpenAI, or embedding package on this branch.
- No Qdrant (or other vector) package, collection, or payload schema.
- No WhatsApp Cloud API credentials, webhook route, or template sender. `.env.example` has no AI or WhatsApp keys. Do not add secrets.
- PDF parsing library `smalot/pdfparser` is already installed and is used only by Magic AI.
- This container has no MAMP MySQL on `127.0.0.1:8889` and no `storage/installed` marker, so installer middleware redirects uninstalled HTTP requests.
- Ollama, Qdrant, and WhatsApp were not reachable and were not simulated.
- Unmerged inbox/AI suggestion code is on `origin/feat/unified-inbox-meta` only.

## 6. Tests executed and actual results

Environment: PHP 8.3.6, Composer 2.10.3, `composer install` from the lockfile. A local `.env` was created from `.env.example` and a key was generated. The `.env` is gitignored and is not committed. No database was created, dropped, or migrated. `storage/installed` was not left in place.

| Command | Result |
| --- | --- |
| `php artisan test --compact` (before the intake change) | **83 passed, 19 failed** (247 assertions). Duration 2.89s. |
| `php artisan test --compact --testsuite=Unit` (before the intake change) | **53 passed, 1 failed** (183 assertions). |
| `php artisan test --compact tests/Unit/KnowledgeDocumentIntakeTest.php` | **16 passed** (60 assertions). Covers tenant storage, pdf/md/csv/html/docx, rejected types, size, no-tenant, cross-tenant read/delete, and unsafe delete paths. |
| `./vendor/bin/pint --dirty` | Formatted `tests/Unit/KnowledgeDocumentIntakeTest.php` and import order in `bootstrap/providers.php`. |
| `php -l` on the intake service, migration, and test | No syntax errors. |
| `php artisan test --compact --testsuite=Unit` (after the intake tests were added) | **69 passed, 1 failed** (243 assertions). The failure is the same installer 302 as below. |
| `php artisan test --compact --filter='returns HTTP 403'` | **Failed**: expected 403, got 302. |
| Same filter after a temporary `storage/installed` marker, marker removed afterward | **Passed** (2 assertions). Disabled lead and dependent quote routes returned 403. |
| `composer audit --format=plain` | **8 advisories, 5 packages.** |

Failure causes (not product regressions introduced here):

- Feature tests that query `users` throw `SQLSTATE[HY000] [2002] Connection refused` for connection `tenant` at `127.0.0.1:8889` (MAMP is not running).
- `FeatureEntitlementServiceTest` HTTP case and several Super Admin HTTP cases receive **302** from `Webkul\Installer\Http\Middleware\CanInstall` because the app is not installed. With the installer marker present, the entitlement 403 behavior matches the test. Super Admin authenticated pages were not re-run against a landlord database.

Passed and relevant to this audit: tenant storage isolation, two-database domain isolation, and the tenant-aware queue worker test in `tests/Unit/TenantStorageTaskTest.php`.

No Magic AI test exists under `tests/`. OpenRouter was not called.

## 7. Completed vs pending

**Verified complete (SaaS foundation):** domain tenant resolution, tenant database switching, tenant file roots, tenant-aware queue jobs.

**Done in this change:** tenant-scoped knowledge document intake (`Webkul\AiKnowledge\Services\KnowledgeDocumentIntake`). Files and `pending` rows stay on the current tenant. Deletion removes the file and row. No chunking or embeddings.

**Present but not a knowledge center:** Magic AI lead extraction (config, route, PDF text, OpenRouter chat, lead mapping). Unverified against a live model. Upload checks and timeouts are incomplete. ACL mapping for the route is wrong (bug 1).

**Not started:** FAQ, text extraction, chunking, embeddings, vector search, RAG, playground, analytics, WhatsApp on this branch, fine-tuning, knowledge admin UI.

**Do not build for the first release:** LoRA/QLoRA. RAG over approved tenant documents is the path that matches the current gaps. Fine-tuning needs a separate privacy review and is not required to answer from uploaded documents.

## 8. Recommended implementation sequence

1. Done. Knowledge bytes and rows are stored only for the current tenant. Intake refuses to run with no tenant.
2. Fix the `admin.leads.create_by_ai` ACL mapping (core file, separate small change) so view-only users cannot create leads.
3. Done. Knowledge document intake: allow-listed types, size cap, private tenant disk, status `pending`.
4. Next. Background text extraction and overlap chunking, with retries and visible status. Still no embeddings.
5. Embedding client plus a vector store that filters on tenant id and document id. Block live verification until Ollama and Qdrant exist.
6. Retrieval that reads only approved/active chunks, cites them, and falls back when nothing qualifies.
7. Tenant admin UI (documents, FAQ, behavior, playground, publish) behind Bouncer and a feature entitlement.
8. WhatsApp only by extending the existing Unified Inbox branch, not a second webhook on this older tree.
9. Optional fine-tuning after RAG is in use.

## 9. The single next task

The intake task selected above is implemented and covered by `tests/Unit/KnowledgeDocumentIntakeTest.php` (16 passed, 60 assertions). It is not started again here.

**Next task, not started: extract text from pending documents and store overlapping chunks.**

Add a tenant-aware queued job that reads a `pending` `ai_knowledge_documents` row, extracts text (PDF via the already installed `smalot/pdfparser`, plain text for txt/md/csv/html), writes chunk rows on the tenant connection with a configured overlap, and moves status through `processing`, `ready`, or `failed` with a retry limit. Do not call an embedding API and do not add a vector client. Reuse `KnowledgeDocumentIntake` records rather than a second upload path.

Still later, and not this next task: the `admin.leads.create_by_ai` ACL mapping, embeddings, RAG, admin UI, WhatsApp, and fine-tuning.

## 10. Acceptance criteria

### Completed intake task (met)

- New package `packages/Webkul/AiKnowledge` follows the existing contract / model / repository / provider layout and is registered in root autoload, `bootstrap/providers.php`, and `config/concord.php`. Core Krayin modules are not edited.
- Intake refuses to run when `Tenant::current()` is null, and it does not write a shared `storage/app/ai-knowledge` file in that case.
- Allowed types are an explicit extension and content check (pdf, txt, md, csv, html, htm, docx). Executables, double extensions such as `file.php.txt`, and names containing path segments are rejected.
- Size is capped (10 MB default). Empty files are rejected.
- Bytes are stored on the `local` disk at `ai-knowledge/{uuid}.{ext}` under the current tenant root. The client filename is kept only as a display column.
- A tenant-connection row stores original name, storage path, mime, extension, size, sha256, status `pending`, and optional uploader id.
- Deleting a document removes that file and the row, and rejects a storage path outside `ai-knowledge/{uuid}.{ext}`.
- A second tenant cannot see the first tenant's file or row.
- Pest coverage proves the cases above using sqlite tenant files, with no network calls.
- `docs/CHANGELOG_SAAS.md` records the change without a pull-request number.
- If implementation shows an audit claim was wrong, this file is corrected with the command output.

Result: those criteria are met. `php artisan test --compact tests/Unit/KnowledgeDocumentIntakeTest.php` passed with 16 tests and 60 assertions. Symfony's `UploadedFile` basename-strips `../../etc/passwd.txt` to `passwd.txt` before the service sees it; the service still rejects a client name that contains `/` or `..` when that name is preserved. No audit claim about missing Ollama, Qdrant, or WhatsApp was disproved.

### Next task (text extraction and overlapping chunks)

- A queued job, dispatched only while a tenant is current, loads a `pending` document from the tenant connection and does nothing when no tenant is current.
- PDF text uses `smalot/pdfparser`. txt, md, csv, and html are read as text. docx text can be deferred if the OOXML body is not parsed yet, but the job must mark that document `failed` with a visible reason instead of pretending it was indexed.
- Chunks are stored in a tenant-database table with document id, ordinal, text, and overlap taken from the previous chunk. No embedding column and no network call.
- Status moves `pending` → `processing` → `ready`, or `failed` after the retry limit. A failed job leaves the file in place.
- Re-running a `ready` document does not duplicate chunks.
- Deleting a document also deletes its chunks.
- Pest coverage uses sqlite tenant files and does not contact OpenRouter, Ollama, or Qdrant.
