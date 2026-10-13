<?php

use App\Http\Middleware\EnforceTenantStorageQuota;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\StorageQuotaService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\LandlordSchema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();
});

afterEach(function () {
    Tenant::forgetCurrent();
});

it('passes through when the request has no uploaded files', function () {
    $plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'storage_quota_mb' => 1,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme-quota-mw',
        'database' => 'tenant_acme_quota_mw',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $tenant->makeCurrent();

    $middleware = new EnforceTenantStorageQuota(app(StorageQuotaService::class));
    $request = Request::create('/admin/settings', 'POST');

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getContent())->toBe('ok');
});

it('accepts a nested configuration logo upload under the quota', function () {
    $plan = Plan::query()->create([
        'slug' => 'starter-logo',
        'name' => 'Starter',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'storage_quota_mb' => 1,
    ]);

    $slug = 'logo-quota-'.strtolower(str_replace('.', '', uniqid('', true)));
    $tenant = Tenant::query()->create([
        'name' => 'Logo',
        'slug' => $slug,
        'database' => 'tenant_'.$slug,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $tenant->makeCurrent();

    $middleware = new EnforceTenantStorageQuota(app(StorageQuotaService::class));
    $request = Request::create('/admin/configuration/general/general', 'POST', [], [], [
        'general' => [
            'general' => [
                'admin_logo' => [
                    'logo_image' => UploadedFile::fake()->create('logo.png', 20, 'image/png'),
                ],
            ],
        ],
    ]);

    try {
        $response = $middleware->handle($request, fn () => response('saved'));
    } finally {
        Tenant::forgetCurrent();
        File::deleteDirectory(storage_path('tenants/'.$slug));
    }

    expect($response->getContent())->toBe('saved');
});

it('rejects a nested configuration upload that exceeds the quota', function () {
    $plan = Plan::query()->create([
        'slug' => 'starter-logo-full',
        'name' => 'Starter',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'storage_quota_mb' => 1,
    ]);

    $slug = 'logo-full-'.strtolower(str_replace('.', '', uniqid('', true)));
    $tenant = Tenant::query()->create([
        'name' => 'Logo Full',
        'slug' => $slug,
        'database' => 'tenant_'.$slug,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $tenant->makeCurrent();

    $middleware = new EnforceTenantStorageQuota(app(StorageQuotaService::class));
    $request = Request::create('/admin/configuration/general/general', 'POST', [], [], [
        'general' => [
            'general' => [
                'admin_logo' => [
                    'logo_image' => UploadedFile::fake()->create('logo.png', 2048, 'image/png'),
                ],
            ],
        ],
    ]);

    try {
        expect(fn () => $middleware->handle($request, fn () => response('saved')))
            ->toThrow(HttpException::class);
    } finally {
        Tenant::forgetCurrent();
        File::deleteDirectory(storage_path('tenants/'.$slug));
    }
});
