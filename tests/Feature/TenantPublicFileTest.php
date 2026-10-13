<?php

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\LandlordSchema;

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();

    $this->paths = [];
    $this->databases = [];

    $this->plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'storage_quota_mb' => 5,
    ]);
});

afterEach(function () {
    Tenant::forgetCurrent();

    foreach ($this->paths ?? [] as $path) {
        File::deleteDirectory($path);
    }

    foreach ($this->databases ?? [] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('serves a public file stored for the current tenant', function () {
    $slug = 'public-file-'.strtolower(str_replace('.', '', uniqid('', true)));
    $this->paths = [storage_path('tenants/'.$slug)];

    $tenant = Tenant::query()->create([
        'name' => 'Public File',
        'slug' => $slug,
        'database' => ($this->databases[] = tempnam(sys_get_temp_dir(), 'tenant-public-')),
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $tenant->makeCurrent();
    Storage::disk('public')->put('configuration/logo.png', 'png-bytes');

    $response = $this->get('/storage/configuration/logo.png');

    $response->assertOk();
    expect($response->streamedContent())->toBe('png-bytes');
});

it('does not serve another tenant public file', function () {
    $slugA = 'public-a-'.strtolower(str_replace('.', '', uniqid('', true)));
    $slugB = 'public-b-'.strtolower(str_replace('.', '', uniqid('', true)));
    $this->paths = [
        storage_path('tenants/'.$slugA),
        storage_path('tenants/'.$slugB),
    ];

    $tenantA = Tenant::query()->create([
        'name' => 'Public A',
        'slug' => $slugA,
        'database' => ($this->databases[] = tempnam(sys_get_temp_dir(), 'tenant-public-')),
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    $tenantB = Tenant::query()->create([
        'name' => 'Public B',
        'slug' => $slugB,
        'database' => ($this->databases[] = tempnam(sys_get_temp_dir(), 'tenant-public-')),
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $tenantA->makeCurrent();
    Storage::disk('public')->put('configuration/logo.png', 'tenant-a');

    $tenantB->makeCurrent();

    $this->withoutExceptionHandling();

    expect(fn () => $this->get('/storage/configuration/logo.png'))
        ->toThrow(NotFoundHttpException::class);
});

it('rejects a traversal path on the tenant public disk', function () {
    $slug = 'public-safe-'.strtolower(str_replace('.', '', uniqid('', true)));
    $this->paths = [storage_path('tenants/'.$slug)];

    $tenant = Tenant::query()->create([
        'name' => 'Public Safe',
        'slug' => $slug,
        'database' => ($this->databases[] = tempnam(sys_get_temp_dir(), 'tenant-public-')),
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $tenant->makeCurrent();

    $this->withoutExceptionHandling();

    expect(fn () => $this->get('/storage/'.rawurlencode('../.env')))
        ->toThrow(NotFoundHttpException::class);
});
