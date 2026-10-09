<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Services\FeatureEntitlementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

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
        'multitenancy.switch_tenant_tasks' => [],
        'cache.default' => 'array',
    ]);

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
        'database' => 'tenant_demo',
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    Cache::clear();
});

afterEach(function () {
    Tenant::forgetCurrent();
    DB::purge('landlord');
    DB::purge('tenant');
});

it('resolves tenant override before plan default and uses configured defaults as fallback', function () {
    $this->plan->features()->createMany([
        ['feature_key' => 'leads', 'is_enabled' => false],
        ['feature_key' => 'quotes', 'is_enabled' => true],
    ]);

    TenantFeature::query()->create([
        'tenant_id' => $this->tenant->id,
        'feature_key' => 'leads',
        'is_enabled' => true,
    ]);

    TenantFeature::query()->create([
        'tenant_id' => $this->tenant->id,
        'feature_key' => 'quotes',
        'is_enabled' => false,
    ]);

    $entitlements = app(FeatureEntitlementService::class);

    expect($entitlements->isEnabled('leads', $this->tenant))->toBeTrue()
        ->and($entitlements->isEnabled('quotes', $this->tenant))->toBeFalse()
        ->and($entitlements->isEnabled('contacts', $this->tenant))->toBeTrue()
        ->and($entitlements->isEnabled('not-a-feature', $this->tenant))->toBeFalse();
});

it('returns disabled when a known feature has no current tenant', function () {
    expect(app(FeatureEntitlementService::class)->isEnabled('leads'))->toBeFalse();
});

it('invalidates cached entitlements when a plan feature or tenant override changes', function () {
    $feature = $this->plan->features()->create([
        'feature_key' => 'leads',
        'is_enabled' => true,
    ]);

    $entitlements = app(FeatureEntitlementService::class);

    expect($entitlements->isEnabled('leads', $this->tenant))->toBeTrue();

    $feature->update(['is_enabled' => false]);
    expect($entitlements->isEnabled('leads', $this->tenant))->toBeFalse();

    $override = TenantFeature::query()->create([
        'tenant_id' => $this->tenant->id,
        'feature_key' => 'leads',
        'is_enabled' => true,
    ]);
    expect($entitlements->isEnabled('leads', $this->tenant))->toBeTrue();

    $override->delete();
    expect($entitlements->isEnabled('leads', $this->tenant))->toBeFalse();
});

it('invalidates cached entitlements when a tenant is assigned to another plan', function () {
    $this->plan->features()->create([
        'feature_key' => 'leads',
        'is_enabled' => false,
    ]);

    $newPlan = Plan::query()->create([
        'slug' => 'pro',
        'name' => 'Pro',
        'is_active' => true,
    ]);
    $newPlan->features()->create([
        'feature_key' => 'leads',
        'is_enabled' => true,
    ]);

    $entitlements = app(FeatureEntitlementService::class);

    expect($entitlements->isEnabled('leads', $this->tenant))->toBeFalse();

    $this->tenant->update(['plan_id' => $newPlan->id]);

    expect($entitlements->isEnabled('leads', $this->tenant))->toBeTrue();
});

it('prefers the most specific route prefix and filters menu items independently', function () {
    $entitlements = app(FeatureEntitlementService::class);

    expect($entitlements->featureForRoute('admin.leads.quotes.index'))->toBe('quotes')
        ->and($entitlements->featureForRoute('admin.leads.index'))->toBe('leads');

    $this->plan->features()->createMany([
        ['feature_key' => 'contacts', 'is_enabled' => false],
        ['feature_key' => 'organizations', 'is_enabled' => true],
    ]);

    $menu = [
        ['key' => 'contacts', 'route' => 'admin.contacts.persons.index'],
        ['key' => 'contacts.persons', 'route' => 'admin.contacts.persons.index'],
        ['key' => 'contacts.organizations', 'route' => 'admin.contacts.organizations.index'],
    ];

    expect($entitlements->filterMenuItems($menu, $this->tenant))
        ->toBe([
            ['key' => 'contacts', 'route' => 'admin.contacts.organizations.index'],
            ['key' => 'contacts.organizations', 'route' => 'admin.contacts.organizations.index'],
        ]);
});

it('returns HTTP 403 for a disabled feature route', function () {
    $this->plan->features()->create([
        'feature_key' => 'leads',
        'is_enabled' => false,
    ]);

    $this->tenant->makeCurrent();

    Route::middleware('web')
        ->get('/_tests/disabled-leads', fn () => response('allowed'))
        ->name('admin.leads.index');

    $this->get('/_tests/disabled-leads')->assertForbidden();
});
