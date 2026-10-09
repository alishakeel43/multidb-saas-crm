<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Multitenancy\Tasks\SwitchTenantStorageTask;
use App\Services\FeatureEntitlementService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Tests\TestCase;
use Webkul\Task\Models\Task;
use Webkul\User\Repositories\UserRepository;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'tenant',
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

    $this->tenantDatabasePaths = [];
});

afterEach(function () {
    Tenant::forgetCurrent();
    DB::purge('landlord');
    DB::purge('tenant');

    foreach ($this->tenantDatabasePaths as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

function createTaskTenantSchema(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->string('email')->nullable();
        $table->boolean('status')->default(true);
    });

    $tasksMigration = require base_path('packages/Webkul/Task/src/Database/Migrations/2026_10_07_140000_create_tasks_table.php');
    $tasksMigration->up();

    $stagesMigration = require base_path('packages/Webkul/Task/src/Database/Migrations/2026_10_07_150000_create_task_stages_table.php');
    $stagesMigration->up();
}

it('keeps tasks isolated across separate tenant databases', function () {
    $databaseA = tempnam(sys_get_temp_dir(), 'krayin-task-a-');
    $databaseB = tempnam(sys_get_temp_dir(), 'krayin-task-b-');
    $this->tenantDatabasePaths = [$databaseA, $databaseB];

    $tenantA = Tenant::query()->create([
        'name' => 'Task Tenant A',
        'slug' => 'task-a',
        'database' => $databaseA,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    $tenantB = Tenant::query()->create([
        'name' => 'Task Tenant B',
        'slug' => 'task-b',
        'database' => $databaseB,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $tenantA->makeCurrent();
    createTaskTenantSchema();
    DB::table('users')->insert([
        'id' => 1,
        'name' => 'Owner A',
        'email' => 'a@example.test',
        'status' => true,
    ]);
    $tenantUsersA = app(UserRepository::class)->scopeToWorkspace()->all();
    $taskA = Task::query()->create([
        'title' => 'Tenant A Task',
        'status' => Task::STATUS_PENDING,
        'priority' => Task::PRIORITY_MEDIUM,
        'assigned_to' => 1,
        'created_by' => 1,
    ]);

    expect(Task::query()->count())->toBe(1)
        ->and($tenantUsersA->pluck('name')->all())->toBe(['Owner A'])
        ->and(Schema::hasColumn('tasks', 'tenant_id'))->toBeFalse();

    $tenantB->makeCurrent();
    createTaskTenantSchema();
    DB::table('users')->insert([
        'id' => 1,
        'name' => 'Owner B',
        'email' => 'b@example.test',
        'status' => true,
    ]);
    $tenantUsersB = app(UserRepository::class)->scopeToWorkspace()->all();

    expect(Task::query()->count())->toBe(0)
        ->and($tenantUsersB->pluck('name')->all())->toBe(['Owner B'])
        ->and(Task::query()->find($taskA->id))->toBeNull();

    Task::query()->create([
        'title' => 'Tenant B Task',
        'status' => Task::STATUS_PENDING,
        'priority' => Task::PRIORITY_HIGH,
        'assigned_to' => 1,
        'created_by' => 1,
    ]);

    expect(Task::query()->count())->toBe(1)
        ->and(Task::query()->value('title'))->toBe('Tenant B Task');

    $tenantA->makeCurrent();

    expect(Task::query()->count())->toBe(1)
        ->and(Task::query()->value('title'))->toBe('Tenant A Task');
});

it('maps tasks routes to the tasks entitlement', function () {
    $entitlements = app(FeatureEntitlementService::class);

    expect($entitlements->featureForRoute('admin.tasks.index'))->toBe('tasks')
        ->and($entitlements->featureForRoute('admin.tasks.create'))->toBe('tasks')
        ->and(config('features.tasks.menu_keys'))->toBe(['tasks']);
});

it('checks task reminders only in active tenant databases', function () {
    $databaseA = tempnam(sys_get_temp_dir(), 'krayin-task-reminder-a-');
    $databaseB = tempnam(sys_get_temp_dir(), 'krayin-task-reminder-b-');
    $databaseInactive = tempnam(sys_get_temp_dir(), 'krayin-task-reminder-inactive-');
    $this->tenantDatabasePaths = [$databaseA, $databaseB, $databaseInactive];

    $tenants = [
        Tenant::query()->create([
            'name' => 'Task Reminder A',
            'slug' => 'task-reminder-a',
            'database' => $databaseA,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]),
        Tenant::query()->create([
            'name' => 'Task Reminder B',
            'slug' => 'task-reminder-b',
            'database' => $databaseB,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]),
        Tenant::query()->create([
            'name' => 'Task Reminder Inactive',
            'slug' => 'task-reminder-inactive',
            'database' => $databaseInactive,
            'plan_id' => $this->plan->id,
            'status' => 'suspended',
        ]),
    ];

    foreach ($tenants as $index => $tenant) {
        $tenant->makeCurrent();
        createTaskTenantSchema();

        if ($index === 0) {
            Task::query()->create([
                'title' => 'Due today',
                'due_date' => Carbon::today()->toDateString(),
            ]);
            Task::query()->create([
                'title' => 'Overdue',
                'due_date' => Carbon::yesterday()->toDateString(),
            ]);
        } else {
            Task::query()->create([
                'title' => 'Due today',
                'due_date' => Carbon::today()->toDateString(),
            ]);
        }
    }
    Tenant::forgetCurrent();

    $this->artisan('tasks:check-reminders')
        ->expectsOutputToContain("Tenant {$tenants[0]->slug}: 1 due today, 1 overdue.")
        ->expectsOutputToContain("Tenant {$tenants[1]->slug}: 1 due today, 0 overdue.")
        ->expectsOutput('Found 2 tasks due today and 1 overdue tasks across active tenants.')
        ->assertSuccessful();
});
