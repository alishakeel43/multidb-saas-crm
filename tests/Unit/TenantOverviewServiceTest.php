<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Multitenancy\Tasks\SwitchTenantStorageTask;
use App\Services\TenantOverviewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
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

    $plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'is_active' => true,
    ]);

    $this->tenant = Tenant::query()->create([
        'name' => 'Overview Tenant',
        'slug' => 'overview-tenant',
        'database' => ':memory:',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);
});

afterEach(function () {
    Tenant::forgetCurrent();
    DB::purge('landlord');
    DB::purge('tenant');
});

it('loads administrator contact and CRM usage from that tenant database', function () {
    $this->tenant->makeCurrent();

    Schema::connection('tenant')->create('roles', function (Blueprint $table) {
        $table->increments('id');
        $table->string('permission_type');
    });
    Schema::connection('tenant')->create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
        $table->boolean('status');
        $table->unsignedInteger('role_id');
        $table->boolean('is_support_account')->default(false);
    });

    DB::connection('tenant')->table('roles')->insert([
        ['id' => 1, 'permission_type' => 'all'],
        ['id' => 2, 'permission_type' => 'custom'],
    ]);
    DB::connection('tenant')->table('users')->insert([
        ['name' => 'Tenant Owner', 'email' => 'owner@example.test', 'status' => true, 'role_id' => 1, 'is_support_account' => false],
        ['name' => 'Sales User', 'email' => 'sales@example.test', 'status' => false, 'role_id' => 2, 'is_support_account' => false],
        ['name' => 'Krayin Support', 'email' => 'support@example.test', 'status' => true, 'role_id' => 2, 'is_support_account' => true],
    ]);

    foreach (['leads', 'persons', 'organizations'] as $tableName) {
        Schema::connection('tenant')->create($tableName, function (Blueprint $table) {
            $table->increments('id');
        });
    }
    foreach (['leads' => 2, 'persons' => 3] as $tableName => $count) {
        for ($id = 1; $id <= $count; $id++) {
            DB::connection('tenant')->table($tableName)->insert(['id' => $id]);
        }
    }

    $details = app(TenantOverviewService::class)->detailsFor($this->tenant);

    expect($details['database_status'])->toBe('Connected')
        ->and($details['owner_name'])->toBe('Tenant Owner')
        ->and($details['owner_email'])->toBe('owner@example.test')
        ->and($details['support_users'])->toBe([
            ['name' => 'Krayin Support', 'email' => 'support@example.test'],
        ])
        ->and($details['total_users'])->toBe(2)
        ->and($details['active_users'])->toBe(1)
        ->and($details['leads'])->toBe(2)
        ->and($details['contacts'])->toBe(3)
        ->and($details['organizations'])->toBe(0);
});
