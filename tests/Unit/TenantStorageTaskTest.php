<?php

use App\Models\Domain;
use App\Models\Plan;
use App\Models\Tenant;
use App\Multitenancy\Tasks\SwitchTenantStorageTask;
use App\Multitenancy\TenantFinder\DomainTenantFinder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Tests\TestCase;

uses(TestCase::class);

class TenantIsolationQueueProbe implements ShouldQueue, TenantAware
{
    public function handle(): void
    {
        DB::connection('landlord')->table('tenant_queue_probes')->insert([
            'tenant_slug' => Tenant::current()?->slug,
        ]);
    }
}

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

    $this->tenant = Tenant::query()->create([
        'name' => 'Demo',
        'slug' => 'demo',
        'database' => ':memory:',
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $this->tenantStoragePaths = [];
    $this->tenantDatabasePaths = [];
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

it('roots local and public disks under the current tenant path, including cached disks', function () {
    $slug = 'isolation-'.strtolower(str_replace('.', '', uniqid('', true)));
    $this->tenantStoragePaths[] = storage_path('tenants/'.$slug);
    $tenant = Tenant::query()->create([
        'name' => 'Storage Isolation',
        'slug' => $slug,
        'database' => 'tenant_'.$slug,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    Storage::disk('local');
    Storage::disk('public');
    $tenant->makeCurrent();

    expect(config('filesystems.disks.local.root'))->toBe(storage_path('tenants/'.$slug.'/app'))
        ->and(config('filesystems.disks.public.root'))->toBe(storage_path('tenants/'.$slug.'/app/public'))
        ->and(Storage::disk('local')->path('probe.txt'))->toBe(storage_path('tenants/'.$slug.'/app/probe.txt'))
        ->and(Storage::disk('public')->path('probe.txt'))->toBe(storage_path('tenants/'.$slug.'/app/public/probe.txt'));
});

it('does not expose files from one tenant through another tenant storage disk', function () {
    $slugA = 'isolation-a-'.strtolower(str_replace('.', '', uniqid('', true)));
    $slugB = 'isolation-b-'.strtolower(str_replace('.', '', uniqid('', true)));
    $this->tenantStoragePaths[] = storage_path('tenants/'.$slugA);
    $this->tenantStoragePaths[] = storage_path('tenants/'.$slugB);
    $tenantA = Tenant::query()->create([
        'name' => 'Storage Tenant A',
        'slug' => $slugA,
        'database' => 'tenant_'.$slugA,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    $tenantB = Tenant::query()->create([
        'name' => 'Storage Tenant B',
        'slug' => $slugB,
        'database' => 'tenant_'.$slugB,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $tenantA->makeCurrent();
    Storage::disk('local')->put('private/report.txt', 'tenant A secret');

    $tenantB->makeCurrent();

    expect(Storage::disk('local')->exists('private/report.txt'))->toBeFalse()
        ->and(config('filesystems.disks.local.root'))->toBe(storage_path('tenants/'.$slugB.'/app'));
});

it('resolves separate domains to isolated tenant databases', function () {
    $databaseA = tempnam(sys_get_temp_dir(), 'krayin-tenant-a-');
    $databaseB = tempnam(sys_get_temp_dir(), 'krayin-tenant-b-');
    $this->tenantDatabasePaths = [$databaseA, $databaseB];

    $tenantA = Tenant::query()->create([
        'name' => 'Tenant A',
        'slug' => 'tenant-a',
        'database' => $databaseA,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    $tenantB = Tenant::query()->create([
        'name' => 'Tenant B',
        'slug' => 'tenant-b',
        'database' => $databaseB,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    Domain::query()->create([
        'tenant_id' => $tenantA->id,
        'domain' => 'tenant-a.example.test',
        'is_primary' => true,
        'is_active' => true,
    ]);
    Domain::query()->create([
        'tenant_id' => $tenantB->id,
        'domain' => 'tenant-b.example.test',
        'is_primary' => true,
        'is_active' => true,
    ]);

    foreach ([[$databaseA, 'tenant-a'], [$databaseB, 'tenant-b']] as [$database, $owner]) {
        config(['database.connections.tenant.database' => $database]);
        DB::purge('tenant');
        Schema::connection('tenant')->create('isolation_records', function (Blueprint $table) {
            $table->string('owner');
        });
        DB::connection('tenant')->table('isolation_records')->insert(['owner' => $owner]);
    }

    $finder = app(DomainTenantFinder::class);
    $resolvedA = $finder->findForRequest(Request::create('http://tenant-a.example.test/'));
    $resolvedB = $finder->findForRequest(Request::create('http://tenant-b.example.test/'));

    expect($resolvedA?->getKey())->toBe($tenantA->getKey())
        ->and($resolvedB?->getKey())->toBe($tenantB->getKey());

    $resolvedA->makeCurrent();
    expect(DB::connection('tenant')->table('isolation_records')->value('owner'))->toBe('tenant-a');

    $resolvedB->makeCurrent();
    expect(DB::connection('tenant')->table('isolation_records')->value('owner'))->toBe('tenant-b');
});

it('preserves tenant context when a queued job runs on the database worker', function () {
    Schema::connection('landlord')->create('jobs', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });
    Schema::connection('landlord')->create('tenant_queue_probes', function (Blueprint $table) {
        $table->string('tenant_slug')->nullable();
    });

    config([
        'queue.default' => 'database',
        'queue.connections.database' => [
            'driver' => 'database',
            'connection' => 'landlord',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
    ]);
    $tenant = Tenant::query()->create([
        'name' => 'Queue Tenant',
        'slug' => 'queue-tenant',
        'database' => 'tenant_queue-tenant',
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    $this->tenantStoragePaths[] = storage_path('tenants/queue-tenant');
    $tenant->makeCurrent();

    dispatch(new TenantIsolationQueueProbe);

    Tenant::forgetCurrent();
    $this->artisan('queue:work', ['connection' => 'database', '--once' => true])
        ->assertExitCode(0);

    expect(DB::connection('landlord')->table('tenant_queue_probes')->value('tenant_slug'))
        ->toBe('queue-tenant')
        ->and(Tenant::current())->toBeNull();
});
