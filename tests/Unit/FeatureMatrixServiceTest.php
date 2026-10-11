<?php

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Services\FeatureMatrixService;
use Illuminate\Support\Facades\DB;
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
        'cache.default' => 'array',
    ]);
    DB::purge('landlord');

    $migration = require database_path('migrations/landlord/2026_10_09_000000_create_saas_landlord_tables.php');
    $migration->up();
});

it('groups features into configured categories', function () {
    $grouped = app(FeatureMatrixService::class)->groupedFeatures();

    expect($grouped)->toHaveKey('crm')
        ->and($grouped['crm']['features'])->toHaveKey('leads')
        ->and($grouped['crm']['features'])->toHaveKey('contacts')
        ->and($grouped['crm']['features'])->not->toHaveKey('organizations')
        ->and($grouped['sales']['features'])->toHaveKey('products')
        ->and($grouped['automation']['features'])->toHaveKey('workflows');
});

it('toggles an entire category of modules at once', function () {
    $matrix = app(FeatureMatrixService::class);
    $plan = Plan::query()->create([
        'slug' => 'pro',
        'name' => 'Pro',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $enabled = $matrix->enabledMapForPlan($plan);
    $enabled = $matrix->applyCategoryState($enabled, 'marketing', false);

    expect($matrix->categoryIsFullyEnabled($enabled, 'marketing'))->toBeFalse()
        ->and($enabled['marketing'])->toBeFalse()
        ->and($enabled['marketing_events'])->toBeFalse()
        ->and($enabled['marketing_campaigns'])->toBeFalse();
});

it('syncs plan feature checkboxes into landlord plan_features rows', function () {
    $plan = Plan::query()->create([
        'slug' => 'growth',
        'name' => 'Growth',
        'monthly_price' => 49,
        'currency' => 'USD',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $matrix = app(FeatureMatrixService::class);
    $enabled = $matrix->enabledMapForPlan($plan);
    $enabled['products'] = false;
    $enabled['quotes'] = false;

    $matrix->syncPlan($plan, $enabled);

    expect(PlanFeature::query()->where('plan_id', $plan->id)->where('feature_key', 'products')->value('is_enabled'))->toBeFalsy()
        ->and(PlanFeature::query()->where('plan_id', $plan->id)->where('feature_key', 'leads')->value('is_enabled'))->toBeTruthy()
        ->and(PlanFeature::query()->where('plan_id', $plan->id)->count())->toBe(count(config('features')));
});

it('rejects saving when an enabled module is missing a required dependency', function () {
    $matrix = app(FeatureMatrixService::class);
    $plan = Plan::query()->create([
        'slug' => 'deps',
        'name' => 'Deps',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'sort_order' => 3,
    ]);

    $enabled = $matrix->enabledMapForPlan($plan);
    $enabled['leads'] = false;
    $enabled['quotes'] = true;
    $enabled['products'] = true;

    $errors = $matrix->dependencyErrors($enabled);

    expect($errors)->not->toBeEmpty()
        ->and(implode(' ', $errors))->toContain('Quotes')
        ->and(implode(' ', $errors))->toContain('Leads');

    expect(fn () => $matrix->syncPlan($plan, $enabled))
        ->toThrow(InvalidArgumentException::class);

    expect(PlanFeature::query()->where('plan_id', $plan->id)->count())->toBe(0);
});

it('syncs tenant overrides from the checkbox matrix', function () {
    $plan = Plan::query()->create([
        'slug' => 'basic',
        'name' => 'Basic',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme',
        'database' => 'tenant_acme_matrix',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $matrix = app(FeatureMatrixService::class);
    $enabled = $matrix->enabledMapForTenant($tenant);
    $enabled['mail'] = false;

    $matrix->syncTenant($tenant, $enabled);

    expect(TenantFeature::query()->where('tenant_id', $tenant->id)->where('feature_key', 'mail')->value('is_enabled'))->toBeFalsy()
        ->and(TenantFeature::query()->where('tenant_id', $tenant->id)->count())->toBe(count(config('features')));
});

it('keeps the legacy organizations alias locked to contacts when syncing', function () {
    $plan = Plan::query()->create([
        'slug' => 'alias',
        'name' => 'Alias',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $matrix = app(FeatureMatrixService::class);
    $enabled = $matrix->enabledMapForPlan($plan);
    $enabled['contacts'] = false;
    $enabled['organizations'] = true;

    $matrix->syncPlan($plan, $enabled);

    expect(PlanFeature::query()->where('plan_id', $plan->id)->where('feature_key', 'contacts')->value('is_enabled'))->toBeFalsy()
        ->and(PlanFeature::query()->where('plan_id', $plan->id)->where('feature_key', 'organizations')->value('is_enabled'))->toBeFalsy();
});
