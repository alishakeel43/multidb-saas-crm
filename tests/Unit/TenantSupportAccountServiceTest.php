<?php

use App\Models\Tenant;
use App\Models\TenantSupportUser;
use App\Observers\RejectSupportUserOwnership;
use App\Services\TenantSupportAccountService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Lead\Models\Lead;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'tenant',
        'database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('tenant');
    DB::purge('landlord');
    DB::setDefaultConnection('tenant');

    Schema::connection('tenant')->create('roles', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('description')->nullable();
        $table->string('permission_type');
        $table->json('permissions')->nullable();
        $table->timestamps();
    });

    Schema::connection('tenant')->create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->boolean('status');
        $table->unsignedInteger('role_id');
        $table->string('view_permission');
        $table->timestamps();
    });

    $migration = require database_path('migrations/tenant/2026_10_09_000001_add_support_account_markers_to_tenant_users_and_roles.php');
    $migration->up();

    Schema::connection('landlord')->create('tenants', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->string('database');
        $table->timestamps();
    });

    Schema::connection('landlord')->create('tenant_support_users', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->unsignedInteger('tenant_user_id');
        $table->string('name');
        $table->string('email');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->unique(['tenant_id', 'tenant_user_id']);
        $table->unique(['tenant_id', 'email']);
    });

    DB::connection('landlord')->table('tenants')->insert([
        'id' => 1,
        'name' => 'Tenant One',
        'slug' => 'tenant-one',
        'database' => 'tenant_one',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

afterEach(function () {
    DB::purge('tenant');
    DB::purge('landlord');
});

function supportTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->setRawAttributes([
        'id' => 1,
        'name' => 'Tenant One',
        'database' => 'tenant_one',
    ]);
    $tenant->shouldReceive('execute')
        ->andReturnUsing(fn (callable $callback) => $callback($tenant));

    return $tenant;
}

function supportUserData(string $name, string $email, string $password = 'a-secure-support-password'): array
{
    return [
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'is_active' => true,
    ];
}

it('creates multiple separate support accounts with a shared read-only support role', function () {
    $service = app(TenantSupportAccountService::class);
    $tenant = supportTenant();
    $first = $service->create($tenant, supportUserData('Support One', 'support-one@example.test'));
    $second = $service->create($tenant, supportUserData('Support Two', 'support-two@example.test'));
    $role = DB::connection('tenant')->table('roles')->where('id', 1)->first();
    $permissions = json_decode($role->permissions, true, flags: JSON_THROW_ON_ERROR);

    expect(DB::connection('tenant')->table('users')->count())->toBe(2)
        ->and(TenantSupportUser::query()->where('tenant_id', 1)->count())->toBe(2)
        ->and($first->tenant_user_id)->not->toBe($second->tenant_user_id)
        ->and(DB::connection('tenant')->table('users')->where('email', $first->email)->value('is_support_account'))->toBe(1)
        ->and($role->is_support_role)->toBe(1)
        ->and($role->permission_type)->toBe('custom')
        ->and($permissions)->toContain('leads.view')
        ->and($permissions)->not->toContain('leads.edit', 'leads.create', 'leads.delete')
        ->and(Hash::check('a-secure-support-password', DB::connection('tenant')->table('users')->where('email', $first->email)->value('password')))->toBeTrue()
        ->and($first->getAttributes())->not->toHaveKey('password');
});

it('rejects tenant email collisions and blocks support accounts from CRM ownership', function () {
    DB::connection('tenant')->table('roles')->insert([
        'name' => 'Administrator',
        'description' => 'Tenant administrator',
        'permission_type' => 'all',
        'permissions' => null,
        'is_support_role' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::connection('tenant')->table('users')->insert([
        'name' => 'Existing CRM User',
        'email' => 'already-used@example.test',
        'password' => 'irrelevant',
        'status' => true,
        'role_id' => 1,
        'view_permission' => 'global',
        'is_support_account' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => app(TenantSupportAccountService::class)->create(
        supportTenant(),
        supportUserData('Support User', 'already-used@example.test'),
    ))->toThrow(ValidationException::class);

    $support = app(TenantSupportAccountService::class)->create(
        supportTenant(),
        supportUserData('Support User', 'real-support@example.test'),
    );
    $lead = new Lead;
    $lead->user_id = $support->tenant_user_id;

    expect(fn () => app(RejectSupportUserOwnership::class)->saving($lead))
        ->toThrow(ValidationException::class);
});

it('updates and deletes only marked support accounts in their selected tenant', function () {
    $service = app(TenantSupportAccountService::class);
    $tenant = supportTenant();
    $supportUser = $service->create(
        $tenant,
        supportUserData('Support User', 'support@example.test'),
    );
    $supportUser->setRelation('tenant', $tenant);

    $service->update($supportUser, [
        'name' => 'Updated Support',
        'email' => 'updated-support@example.test',
        'is_active' => false,
        'password' => 'a-new-secure-password',
    ]);

    $tenantAccount = DB::connection('tenant')->table('users')->where('id', $supportUser->tenant_user_id)->first();

    expect($supportUser->fresh()->name)->toBe('Updated Support')
        ->and($supportUser->fresh()->is_active)->toBeFalse()
        ->and($tenantAccount->status)->toBe(0)
        ->and(Hash::check('a-new-secure-password', $tenantAccount->password))->toBeTrue();

    $supportUser->setRelation('tenant', $tenant);
    $service->delete($supportUser);

    expect(TenantSupportUser::query()->count())->toBe(0)
        ->and(DB::connection('tenant')->table('users')->count())->toBe(0);
});

it('excludes support users from generic CRM user attribute lookups', function () {
    $support = app(TenantSupportAccountService::class)->create(
        supportTenant(),
        supportUserData('Support User', 'support@example.test'),
    );

    $normalId = DB::connection('tenant')->table('users')->insertGetId([
        'name' => 'CRM User',
        'email' => 'crm@example.test',
        'password' => 'irrelevant',
        'status' => true,
        'role_id' => 1,
        'view_permission' => 'global',
        'is_support_account' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $repository = app(AttributeRepository::class);
    $options = $repository->getLookUpOptions('users');
    $supportEntity = $repository->getLookUpEntity('users', $support->tenant_user_id);

    expect($options->pluck('id')->all())->toBe([(int) $normalId])
        ->and($supportEntity)->toBeNull();
});
