<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantBackupService;
use Illuminate\Support\Facades\File;
use Tests\Support\LandlordSchema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();

    config([
        'saas.tenant_backups.path' => storage_path('framework/testing/tenant-backups'),
    ]);

    File::deleteDirectory(config('saas.tenant_backups.path'));

    $plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'is_active' => true,
    ]);

    $this->tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme-backup',
        'database' => 'tenant_acme_backup',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    File::ensureDirectoryExists(storage_path('tenants/'.$this->tenant->slug.'/app'));
    File::put(storage_path('tenants/'.$this->tenant->slug.'/app/note.txt'), 'hello');
});

afterEach(function () {
    File::deleteDirectory(storage_path('tenants/'.$this->tenant->slug));
    File::deleteDirectory(config('saas.tenant_backups.path'));
});

it('creates a tenant backup manifest', function () {
    $manifest = app(TenantBackupService::class)->backup($this->tenant);

    expect($manifest)->toHaveKeys(['tenant_slug', 'timestamp', 'paths'])
        ->and(File::exists($manifest['paths']['database']))->toBeTrue()
        ->and(File::exists(config('saas.tenant_backups.path').'/'.$this->tenant->slug.'/'.$manifest['timestamp'].'/manifest.json'))->toBeTrue()
        ->and(app(TenantBackupService::class)->listBackups($this->tenant))->not->toBeEmpty();
});
