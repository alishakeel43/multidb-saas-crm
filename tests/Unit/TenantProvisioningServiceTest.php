<?php

use App\Models\Domain;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Services\TenantDatabaseManager;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

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
        'multitenancy.switch_tenant_tasks' => [],
    ]);

    DB::purge('landlord');
    DB::purge('tenant');
    DB::setDefaultConnection('tenant');

    $migration = require database_path('migrations/landlord/2026_10_09_000000_create_saas_landlord_tables.php');
    $migration->up();

    Schema::connection('tenant')->create('roles', function ($table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('permission_type');
        $table->text('permissions')->nullable();
        $table->timestamps();
    });

    Schema::connection('tenant')->create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->boolean('status');
        $table->unsignedBigInteger('role_id');
        $table->string('view_permission');
        $table->timestamps();
    });

    $plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'is_active' => true,
    ]);

    $plan->features()->createMany([
        ['feature_key' => 'leads', 'is_enabled' => true],
        ['feature_key' => 'quotes', 'is_enabled' => false],
    ]);

    $this->databaseManager = Mockery::mock(TenantDatabaseManager::class);
    $this->app->instance(TenantDatabaseManager::class, $this->databaseManager);
});

afterEach(function () {
    Tenant::forgetCurrent();
    DB::purge('landlord');
    DB::purge('tenant');
});

function provisionTenantForTest(
    string $name = 'Demo',
    string $slug = 'demo',
    string $domain = 'demo.krayin.localhost',
    string $adminName = 'Demo Admin',
    string $adminEmail = 'admin@demo.krayin.localhost',
    string $password = 'a-secure-test-password',
): Tenant {
    return app(TenantProvisioningService::class)->provision(
        $name,
        $slug,
        $domain,
        $adminName,
        $adminEmail,
        $password,
    );
}

it('provisions an isolated tenant, migration/seed work, feature defaults, and explicit administrator', function () {
    $slug = 'demo';
    $this->databaseManager
        ->shouldReceive('databaseExists')->once()->with($slug)->andReturnFalse();
    $this->databaseManager
        ->shouldReceive('createDatabase')->once()->with($slug)->andReturn('tenant_demo');

    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', Mockery::on(fn (array $arguments) => $arguments['--database'] === 'tenant' && $arguments['--force']))
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->once()
        ->with('db:seed', Mockery::on(fn (array $arguments) => $arguments['--database'] === 'tenant' && $arguments['--force']))
        ->andReturn(0);

    $path = storage_path('tenants/demo');
    File::shouldReceive('exists')->once()->with($path)->andReturnFalse();
    File::shouldReceive('ensureDirectoryExists')->once()->with($path);

    $tenant = provisionTenantForTest();

    expect($tenant->status)->toBe('active')
        ->and(Domain::query()->where('tenant_id', $tenant->id)->value('domain'))->toBe('demo.krayin.localhost')
        ->and(TenantFeature::query()->where('tenant_id', $tenant->id)->orderBy('feature_key')->pluck('is_enabled', 'feature_key')->all())
        ->toBe(['leads' => true, 'quotes' => false])
        ->and(DB::connection('landlord')->table('subscriptions')->where('tenant_id', $tenant->id)->value('status'))->toBe('trial')
        ->and(DB::connection('tenant')->table('users')->where('email', 'admin@demo.krayin.localhost')->exists())->toBeTrue()
        ->and(Hash::check('a-secure-test-password', DB::connection('tenant')->table('users')->where('email', 'admin@demo.krayin.localhost')->value('password')))->toBeTrue()
        ->and(Tenant::current())->toBeNull();
});

it('rejects reserved or malformed slugs and domains before creating tenant records', function (string $slug, string $domain) {
    expect(fn () => provisionTenantForTest(slug: $slug, domain: $domain))
        ->toThrow(InvalidArgumentException::class);

    expect(Tenant::query()->count())->toBe(0);
})->with([
    ['admin', 'admin.krayin.localhost'],
    ['valid', 'www.krayin.localhost'],
    ['invalid_slug', 'valid.krayin.localhost'],
    ['valid', 'bad..krayin.localhost'],
]);

it('rejects duplicate tenant slugs and duplicate domains without creating a database', function () {
    $plan = Plan::query()->firstOrFail();
    $existing = Tenant::query()->create([
        'name' => 'Existing',
        'slug' => 'existing',
        'database' => 'tenant_existing',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $existing->domains()->create([
        'domain' => 'existing.krayin.localhost',
        'is_primary' => true,
        'is_active' => true,
    ]);

    expect(fn () => provisionTenantForTest(slug: 'existing'))
        ->toThrow(RuntimeException::class, 'already uses slug or database')
        ->and(fn () => provisionTenantForTest(slug: 'another', domain: 'existing.krayin.localhost'))
        ->toThrow(RuntimeException::class, 'already in use');

    $this->databaseManager->shouldNotReceive('createDatabase');
    expect(Tenant::query()->count())->toBe(1);
});

it('rejects an existing tenant database or storage path without reusing either', function () {
    $this->databaseManager
        ->shouldReceive('databaseExists')->once()->with('existingdb')->andReturnTrue();

    expect(fn () => provisionTenantForTest(slug: 'existingdb'))
        ->toThrow(RuntimeException::class, 'already exists; refusing to reuse it');

    $this->databaseManager
        ->shouldReceive('databaseExists')->once()->with('storagecollision')->andReturnFalse();
    File::shouldReceive('exists')
        ->once()
        ->with(storage_path('tenants/storagecollision'))
        ->andReturnTrue();

    expect(fn () => provisionTenantForTest(slug: 'storagecollision', domain: 'storagecollision.krayin.localhost'))
        ->toThrow(RuntimeException::class, 'already exists; refusing to reuse it');
});

it('compensates a failed migration by dropping only its newly created database and deleting its landlord rows', function () {
    $slug = 'failurecase';
    $this->databaseManager
        ->shouldReceive('databaseExists')->once()->with($slug)->andReturnFalse();
    $this->databaseManager
        ->shouldReceive('createDatabase')->once()->with($slug)->andReturn('tenant_failurecase');
    $this->databaseManager
        ->shouldReceive('dropDatabase')->once()->with($slug);

    Artisan::shouldReceive('call')->once()->andReturn(1);
    Artisan::shouldReceive('output')->once()->andReturn('Tenant database connection failed.');
    File::shouldReceive('exists')->once()->andReturnFalse();

    expect(fn () => provisionTenantForTest(slug: $slug, domain: 'failurecase.krayin.localhost'))
        ->toThrow(RuntimeException::class, 'Tenant migrations failed: Tenant database connection failed.')
        ->and(Tenant::query()->where('slug', $slug)->exists())->toBeFalse()
        ->and(Domain::query()->where('domain', 'failurecase.krayin.localhost')->exists())->toBeFalse()
        ->and(Tenant::current())->toBeNull();
});

it('removes newly created storage and landlord records when activation fails after tenant setup', function () {
    $slug = 'activationfailure';
    $path = storage_path('tenants/activationfailure');

    DB::connection('landlord')->statement(
        "CREATE TRIGGER fail_subscription BEFORE INSERT ON subscriptions BEGIN SELECT RAISE(ABORT, 'intentional activation failure'); END"
    );

    $this->databaseManager
        ->shouldReceive('databaseExists')->once()->with($slug)->andReturnFalse();
    $this->databaseManager
        ->shouldReceive('createDatabase')->once()->with($slug)->andReturn('tenant_activationfailure');
    $this->databaseManager
        ->shouldReceive('dropDatabase')->once()->with($slug);

    Artisan::shouldReceive('call')->twice()->andReturn(0);
    File::shouldReceive('exists')->once()->with($path)->andReturnFalse();
    File::shouldReceive('ensureDirectoryExists')->once()->with($path);
    File::shouldReceive('isDirectory')->once()->with($path)->andReturnTrue();
    File::shouldReceive('deleteDirectory')->once()->with($path)->andReturnTrue();

    expect(fn () => provisionTenantForTest(slug: $slug, domain: 'activationfailure.krayin.localhost'))
        ->toThrow(RuntimeException::class, 'intentional activation failure')
        ->and(Tenant::query()->where('slug', $slug)->exists())->toBeFalse()
        ->and(Domain::query()->where('domain', 'activationfailure.krayin.localhost')->exists())->toBeFalse()
        ->and(TenantFeature::query()->exists())->toBeFalse()
        ->and(Tenant::current())->toBeNull();
});

it('reports landlord connection failures with an actionable database check message', function () {
    $this->databaseManager
        ->shouldReceive('databaseExists')
        ->once()
        ->with('demo')
        ->andThrow(new RuntimeException('Unable to check tenant database [tenant_demo]. Verify MAMP MySQL connectivity.'));

    expect(fn () => provisionTenantForTest())
        ->toThrow(RuntimeException::class, 'Verify MAMP MySQL connectivity')
        ->and(Tenant::query()->count())->toBe(0);
});

it('refuses database operations when the configured target is not the approved MAMP MySQL instance', function () {
    config([
        'database.connections.landlord.driver' => 'sqlite',
        'database.connections.tenant.driver' => 'sqlite',
    ]);

    expect(fn () => (new TenantDatabaseManager)->assertMampConnection())
        ->toThrow(RuntimeException::class, 'restricted to MAMP MySQL at 127.0.0.1:8889');
});

it('wraps database inspection failures with an actionable MAMP connectivity message', function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '8889',
            'database' => 'zaristech_saas',
        ],
        'database.connections.tenant' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '8889',
        ],
    ]);

    $databaseManager = DB::getFacadeRoot();
    DB::shouldReceive('connection')
        ->once()
        ->with('landlord')
        ->andThrow(new RuntimeException('Connection refused'));

    try {
        expect(fn () => (new TenantDatabaseManager)->databaseExists('demo'))
            ->toThrow(RuntimeException::class, 'Verify MAMP MySQL connectivity and landlord database privileges: Connection refused');
    } finally {
        DB::swap($databaseManager);
    }
});
