<?php

use App\Http\Middleware\ApplyTenantClock;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantClock;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'tenant',
        'app.timezone' => 'UTC',
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
            SwitchTenantDatabaseTask::class,
        ],
        'cache.default' => 'array',
    ]);
    app()->forgetInstance(TasksCollection::class);
    app()->forgetInstance(TenantClock::class);

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
    date_default_timezone_set('UTC');
});

afterEach(function () {
    Tenant::forgetCurrent();
    app(TenantClock::class)->reset();
    DB::purge('landlord');
    DB::purge('tenant');
    date_default_timezone_set('UTC');

    foreach ($this->tenantDatabasePaths as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('applies and isolates tenant timezones across sequential makeCurrent calls', function () {
    $databaseA = tempnam(sys_get_temp_dir(), 'krayin-clock-a-');
    $databaseB = tempnam(sys_get_temp_dir(), 'krayin-clock-b-');
    $this->tenantDatabasePaths = [$databaseA, $databaseB];

    $tenantA = Tenant::query()->create([
        'name' => 'Clock A',
        'slug' => 'clock-a',
        'database' => $databaseA,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    $tenantB = Tenant::query()->create([
        'name' => 'Clock B',
        'slug' => 'clock-b',
        'database' => $databaseB,
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $clock = app(TenantClock::class);

    $tenantA->makeCurrent();
    Schema::create('core_config', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code');
        $table->text('value')->nullable();
    });
    DB::table('core_config')->insert([
        'code' => TenantClock::CONFIG_KEY,
        'value' => 'Asia/Karachi',
    ]);
    $clock->apply();
    expect(config('app.timezone'))->toBe('Asia/Karachi');

    $tenantB->makeCurrent();
    Schema::create('core_config', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code');
        $table->text('value')->nullable();
    });
    DB::table('core_config')->insert([
        'code' => TenantClock::CONFIG_KEY,
        'value' => 'Europe/Berlin',
    ]);
    $clock->apply();
    expect(config('app.timezone'))->toBe('Europe/Berlin');

    $tenantA->makeCurrent();
    $clock->apply();
    expect(config('app.timezone'))->toBe('Asia/Karachi');

    Tenant::forgetCurrent();
    $clock->reset();
    expect(config('app.timezone'))->toBe('UTC');
});

it('ignores invalid workspace timezone values and keeps the application baseline', function () {
    config(['app.timezone' => 'UTC']);
    $tenant = Tenant::query()->create([
        'name' => 'Invalid clock',
        'slug' => 'invalid-clock',
        'database' => ':memory:',
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    $tenant->makeCurrent();

    Schema::connection('tenant')->create('core_config', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code');
        $table->text('value')->nullable();
    });
    DB::connection('tenant')->table('core_config')->insert([
        'code' => TenantClock::CONFIG_KEY,
        'value' => 'Not/A_Timezone',
    ]);

    $clock = app(TenantClock::class);
    $clock->apply();

    expect($clock->savedTimezone())->toBeNull()
        ->and($clock->timezone())->toBe('UTC')
        ->and(config('app.timezone'))->toBe('UTC')
        ->and(date_default_timezone_get())->toBe('UTC');
});

it('restores the application timezone when tenant middleware exits exceptionally', function () {
    config(['app.timezone' => 'UTC']);
    $tenant = Tenant::query()->create([
        'name' => 'Middleware clock',
        'slug' => 'middleware-clock',
        'database' => ':memory:',
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
    $tenant->makeCurrent();

    Schema::connection('tenant')->create('core_config', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code');
        $table->text('value')->nullable();
    });
    DB::connection('tenant')->table('core_config')->insert([
        'code' => TenantClock::CONFIG_KEY,
        'value' => 'Asia/Karachi',
    ]);

    $middleware = new ApplyTenantClock(app(TenantClock::class));

    expect(fn () => $middleware->handle(
        Request::create('/test', 'GET'),
        function () {
            expect(config('app.timezone'))->toBe('Asia/Karachi')
                ->and(date_default_timezone_get())->toBe('Asia/Karachi');

            throw new RuntimeException('downstream failure');
        },
    ))->toThrow(RuntimeException::class, 'downstream failure')
        ->and(config('app.timezone'))->toBe('UTC')
        ->and(date_default_timezone_get())->toBe('UTC');
});
