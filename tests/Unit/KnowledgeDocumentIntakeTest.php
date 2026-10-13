<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Multitenancy\Tasks\SwitchTenantStorageTask;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Tests\TestCase;
use Webkul\AiKnowledge\Exceptions\KnowledgeDocumentIntakeException;
use Webkul\AiKnowledge\Models\KnowledgeDocument;
use Webkul\AiKnowledge\Services\KnowledgeDocumentIntake;

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

it('stores a text file on the current tenant disk and records a pending row', function () {
    $tenant = makeKnowledgeTenant('intake-text');
    $tenant->makeCurrent();
    migrateKnowledgeDocuments();

    $document = $this->intake->store(knowledgeUpload('handbook.txt', "Hello knowledge base\n"), 15);

    expect($document->original_filename)->toBe('handbook.txt')
        ->and($document->extension)->toBe('txt')
        ->and($document->mime_type)->toBe('text/plain')
        ->and($document->status)->toBe(KnowledgeDocument::STATUS_PENDING)
        ->and($document->uploaded_by)->toBe(15)
        ->and($document->sha256)->toBe(hash('sha256', "Hello knowledge base\n"))
        ->and($document->getConnectionName())->toBe('tenant')
        ->and($document->storage_path)->toMatch('/^ai-knowledge\/[0-9a-f-]{36}\.txt$/')
        ->and(Storage::disk('local')->exists($document->storage_path))->toBeTrue()
        ->and(Storage::disk('local')->path($document->storage_path))->toStartWith(storage_path('tenants/intake-text/app/ai-knowledge/'));

    expect(DB::connection('tenant')->table('ai_knowledge_documents')->count())->toBe(1);
});

it('accepts pdf, markdown, csv, and html content', function (string $name, string $contents) {
    makeKnowledgeTenant('intake-types')->makeCurrent();
    migrateKnowledgeDocuments();

    $document = $this->intake->store(knowledgeUpload($name, $contents));

    expect($document->status)->toBe('pending')
        ->and(Storage::disk('local')->get($document->storage_path))->toBe($contents);
})->with([
    'pdf' => ['guide.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n"],
    'markdown' => ['guide.md', "# Title\n\nBody\n"],
    'csv' => ['guide.csv', "a,b\n1,2\n"],
    'html' => ['guide.html', "<p>Hello</p>\n"],
]);

it('accepts a docx package and rejects a renamed text file', function () {
    makeKnowledgeTenant('intake-docx')->makeCurrent();
    migrateKnowledgeDocuments();

    $document = $this->intake->store(knowledgeDocxUpload());

    expect($document->extension)->toBe('docx')
        ->and(Storage::disk('local')->exists($document->storage_path))->toBeTrue();

    expect(fn () => $this->intake->store(knowledgeUpload('guide.docx', "not a zip\n")))
        ->toThrow(function (KnowledgeDocumentIntakeException $exception) {
            expect($exception->reason)->toBe('invalid_content');
        });
});

it('rejects files that fail the allow list', function (string $name, string $contents) {
    makeKnowledgeTenant('intake-reject')->makeCurrent();
    migrateKnowledgeDocuments();

    $upload = str_contains($name, '/') || str_contains($name, '\\')
        ? knowledgeUploadNamed($name, $contents)
        : knowledgeUpload($name, $contents);

    expect(fn () => $this->intake->store($upload))
        ->toThrow(KnowledgeDocumentIntakeException::class);

    expect(Storage::disk('local')->allFiles('ai-knowledge'))->toBe([])
        ->and(DB::connection('tenant')->table('ai_knowledge_documents')->count())->toBe(0);
})->with([
    'executable' => ['run.exe', 'not a program'],
    'double extension' => ['notes.php.txt', "Hello\n"],
    'path traversal' => ['../../etc/passwd.txt', "secret\n"],
    'dot segments' => ['notes..txt', "secret\n"],
    'php payload' => ['notes.txt', "<?php echo 1;\n"],
    'empty' => ['notes.txt', ''],
    'fake pdf' => ['notes.pdf', "this is not a pdf\n"],
]);

it('rejects files over the size limit', function () {
    config(['ai-knowledge.max_bytes' => 8]);
    makeKnowledgeTenant('intake-size')->makeCurrent();
    migrateKnowledgeDocuments();

    expect(fn () => $this->intake->store(knowledgeUpload('notes.txt', "123456789\n")))
        ->toThrow(function (KnowledgeDocumentIntakeException $exception) {
            expect($exception->reason)->toBe('too_large');
        });
});

it('does not write a file when no tenant is current', function () {
    expect(fn () => $this->intake->store(knowledgeUpload('notes.txt', "Hello\n")))
        ->toThrow(function (KnowledgeDocumentIntakeException $exception) {
            expect($exception->reason)->toBe('no_tenant');
        });

    expect(File::isDirectory(storage_path('app/ai-knowledge')))->toBeFalse();
});

it('keeps tenant files and rows isolated and deletes only the current tenant copy', function () {
    $tenantA = makeKnowledgeTenant('intake-a');
    $tenantB = makeKnowledgeTenant('intake-b');

    $tenantA->makeCurrent();
    migrateKnowledgeDocuments();
    $document = $this->intake->store(knowledgeUpload('secret.txt', "tenant A secret\n"));
    $storagePath = $document->storage_path;

    $tenantB->makeCurrent();
    migrateKnowledgeDocuments();

    expect(KnowledgeDocument::query()->count())->toBe(0)
        ->and(Storage::disk('local')->exists($storagePath))->toBeFalse();

    expect(fn () => $this->intake->delete($document))
        ->toThrow(function (KnowledgeDocumentIntakeException $exception) {
            expect($exception->reason)->toBe('not_found');
        });

    $tenantA->makeCurrent();

    expect(KnowledgeDocument::query()->count())->toBe(1)
        ->and(Storage::disk('local')->exists($storagePath))->toBeTrue();

    $document->storage_path = '../secret.txt';

    expect(fn () => $this->intake->delete($document))
        ->toThrow(function (KnowledgeDocumentIntakeException $exception) {
            expect($exception->reason)->toBe('unsafe_path');
        });

    expect(Storage::disk('local')->exists($storagePath))->toBeTrue();

    $document->storage_path = $storagePath;
    $this->intake->delete($document);

    expect(KnowledgeDocument::query()->count())->toBe(0)
        ->and(Storage::disk('local')->exists($storagePath))->toBeFalse();
});

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

function migrateKnowledgeDocuments(): void
{
    $migration = require base_path('packages/Webkul/AiKnowledge/src/Database/Migrations/2026_10_13_120000_create_ai_knowledge_documents_table.php');
    $migration->up();
}

function knowledgeUpload(string $name, string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'aik');
    file_put_contents($path, $contents);

    return new UploadedFile($path, $name, null, null, true);
}

function knowledgeUploadNamed(string $name, string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'aik');
    file_put_contents($path, $contents);

    return new class($path, $name) extends UploadedFile
    {
        public function __construct(string $path, private readonly string $shownName)
        {
            parent::__construct($path, 'ignored.txt', null, null, true);
        }

        public function getClientOriginalName(): string
        {
            return $this->shownName;
        }
    };
}

function knowledgeDocxUpload(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'aik');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>');
    $zip->addFromString('word/document.xml', '<document/>');
    $zip->close();

    return new UploadedFile($path, 'guide.docx', null, null, true);
}
