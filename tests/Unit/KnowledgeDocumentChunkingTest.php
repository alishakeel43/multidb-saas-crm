<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Multitenancy\Tasks\SwitchTenantStorageTask;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Tests\TestCase;
use Webkul\AiKnowledge\Models\KnowledgeDocument;
use Webkul\AiKnowledge\Services\KnowledgeChunkingService;
use Webkul\AiKnowledge\Services\KnowledgeDocumentIntake;
use Webkul\AiKnowledge\Services\KnowledgeRetrievalService;
use Webkul\UnifiedInbox\Contracts\AiReplyProvider;
use Webkul\UnifiedInbox\Services\AI\ReplySuggestionService;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'landlord',
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [
            SwitchTenantStorageTask::class,
            SwitchTenantDatabaseTask::class,
        ],
        'cache.default' => 'array',
    ]);
    app()->forgetInstance(TasksCollection::class);

    DB::purge('landlord');
    DB::purge('tenant');

    $migration = require database_path('migrations/landlord/2026_10_09_000000_create_saas_landlord_tables.php');
    $migration->up();

    $this->plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'is_active' => true,
    ]);

    $this->tenantStoragePaths = [];
    $this->tenantDatabasePaths = [];
    $this->intake = app(KnowledgeDocumentIntake::class);
});

afterEach(function () {
    Tenant::forgetCurrent();
    DB::purge('landlord');
    DB::purge('tenant');

    foreach ($this->tenantStoragePaths as $path) {
        File::deleteDirectory($path);
    }

    foreach ($this->tenantDatabasePaths as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('splits a text document into overlapping chunks and marks it ready', function () {
    $tenant = makeKnowledgeTenant('chunk-index');
    $tenant->makeCurrent();
    migrateKnowledgeDocuments();
    migrateKnowledgeChunks();

    $content = implode("\n\n", [
        'Shipping policy: all orders ship within 48 hours.',
        'Returns are accepted within 30 days for unused items.',
        'Invoices are sent to the email address on file after checkout.',
        'Customer support replies within one business day.',
    ]);

    $document = $this->intake->store(knowledgeUpload('policy.txt', $content), 21);
    $count = app(KnowledgeChunkingService::class)->indexDocument($document);

    expect($count)->toBeGreaterThan(1)
        ->and($document->fresh()->status)->toBe(KnowledgeDocument::STATUS_READY)
        ->and(DB::connection('tenant')->table('ai_knowledge_chunks')->count())->toBe($count);
});

it('retrieves the most relevant chunks for a tenant question', function () {
    $tenant = makeKnowledgeTenant('chunk-search');
    $tenant->makeCurrent();
    migrateKnowledgeDocuments();
    migrateKnowledgeChunks();

    $document = $this->intake->store(knowledgeUpload('returns.txt', 'Returns are accepted within 30 days. Refunds are processed in 5 to 7 business days. Our support team replies quickly. '), 42);
    app(KnowledgeChunkingService::class)->indexDocument($document);

    $results = app(KnowledgeRetrievalService::class)->search('refund return window', 3);

    expect($results)->not->toBeEmpty()
        ->and($results[0]['score'])->toBeGreaterThan(0)
        ->and(strtolower((string) $results[0]['content']))->toContain('refund');
});

it('adds relevant tenant knowledge to AI reply suggestions', function () {
    $tenant = makeKnowledgeTenant('suggestion-context');
    $tenant->makeCurrent();
    migrateKnowledgeDocuments();
    migrateKnowledgeChunks();

    $document = $this->intake->store(knowledgeUpload('refunds.txt', 'Refunds are processed within 5 business days.'), 42);
    app(KnowledgeChunkingService::class)->indexDocument($document);

    $query = new class((object) ['direction' => 'inbound', 'body' => 'How long do refunds take?'])
    {
        public function __construct(private object $message) {}

        public function whereNotNull(string $column): self
        {
            return $this;
        }

        public function where(string $column, string $operator, string $value): self
        {
            return $this;
        }

        public function orderByDesc(string $column): self
        {
            return $this;
        }

        public function limit(int $limit): self
        {
            return $this;
        }

        public function get(): Collection
        {
            return collect([$this->message]);
        }
    };

    $conversation = new class($query)
    {
        public string $channel = 'whatsapp';

        public string $customer_name = 'Customer';

        public function __construct(private object $query) {}

        public function messages(): object
        {
            return $this->query;
        }
    };

    $provider = new class implements AiReplyProvider
    {
        public array $messages = [];

        public function isConfigured(): bool
        {
            return true;
        }

        public function complete(array $messages): string
        {
            $this->messages = $messages;

            return '["Refunds are processed within 5 business days."]';
        }
    };

    $service = new ReplySuggestionService($provider, app(KnowledgeRetrievalService::class));
    $service->suggest($conversation);

    expect($provider->messages[1]['content'])
        ->toContain('Tenant knowledge')
        ->toContain('Refunds are processed within 5 business days.')
        ->toContain('How long do refunds take?');
});

function migrateKnowledgeDocuments(): void
{
    $migration = require base_path('packages/Webkul/AiKnowledge/src/Database/Migrations/2026_10_13_120000_create_ai_knowledge_documents_table.php');
    $migration->up();
}

function makeKnowledgeTenant(string $slug): Tenant
{
    $database = tempnam(sys_get_temp_dir(), 'krayin-knowledge-');
    $databases = test()->tenantDatabasePaths;
    $storagePaths = test()->tenantStoragePaths;
    $databases[] = $database;
    $storagePaths[] = storage_path('tenants/'.$slug);
    test()->tenantDatabasePaths = $databases;
    test()->tenantStoragePaths = $storagePaths;

    return Tenant::query()->create([
        'name' => $slug,
        'slug' => $slug,
        'database' => $database,
        'plan_id' => test()->plan->id,
        'status' => 'active',
    ]);
}

function migrateKnowledgeChunks(): void
{
    $migration = require base_path('packages/Webkul/AiKnowledge/src/Database/Migrations/2026_10_13_130000_create_ai_knowledge_chunks_table.php');
    $migration->up();
}

function knowledgeUpload(string $name, string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'aik');
    file_put_contents($path, $contents);

    return new UploadedFile($path, $name, null, null, true);
}
