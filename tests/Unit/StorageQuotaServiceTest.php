<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\StorageQuotaService;
use Illuminate\Support\Facades\File;
use Tests\Support\LandlordSchema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();

    $this->plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'storage_quota_mb' => 1,
    ]);

    $this->tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme-quota',
        'database' => 'tenant_acme_quota',
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $this->path = storage_path('tenants/'.$this->tenant->slug.'/app');
    File::ensureDirectoryExists($this->path);
});

afterEach(function () {
    File::deleteDirectory(storage_path('tenants/'.$this->tenant->slug));
});

it('calculates usage and enforces quota', function () {
    File::put($this->path.'/file.bin', str_repeat('a', 1024));

    $service = app(StorageQuotaService::class);
    $summary = $service->summary($this->tenant);

    expect($summary['used_bytes'])->toBe(1024)
        ->and($summary['quota_bytes'])->toBe(1024 * 1024);

    $service->assertCanStore($this->tenant, 100);

    expect(fn () => $service->assertCanStore($this->tenant, 1024 * 1024))
        ->toThrow(RuntimeException::class);
});
