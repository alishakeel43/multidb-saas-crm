<?php

use App\Http\Middleware\EnforceTenantStorageQuota;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\StorageQuotaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
