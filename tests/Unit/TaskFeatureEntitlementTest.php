<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\FeatureEntitlementService;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Tasks\TasksCollection;
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
        'multitenancy.switch_tenant_tasks' => [],
        'cache.default' => 'array',
    ]);
    app()->forgetInstance(TasksCollection::class);

    $migration = require database_path('migrations/landlord/2026_10_09_000000_create_saas_landlord_tables.php');
    $migration->up();

    $this->plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'is_active' => true,
    ]);

    $this->tenant = Tenant::query()->create([
        'name' => 'Tasks Tenant',
        'slug' => 'tasks-tenant',
        'database' => ':memory:',
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
});

afterEach(function () {
    Tenant::forgetCurrent();
});

it('returns HTTP 403 when the tasks feature is disabled', function () {
    $this->plan->features()->create([
        'feature_key' => 'tasks',
        'is_enabled' => false,
    ]);

    $this->tenant->makeCurrent();

    Route::middleware('web')
        ->get('/_tests/disabled-tasks', fn () => response('allowed'))
        ->name('admin.tasks.index');

    $this->get('/_tests/disabled-tasks')->assertForbidden();

    $menu = [
        ['key' => 'tasks', 'route' => 'admin.tasks.index'],
        ['key' => 'dashboard', 'route' => 'admin.dashboard.index'],
    ];

    expect(app(FeatureEntitlementService::class)->filterMenuItems($menu, $this->tenant))
        ->toBe([
            ['key' => 'dashboard', 'route' => 'admin.dashboard.index'],
        ]);
});
