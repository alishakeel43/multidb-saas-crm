<?php

use App\Filament\Pages\SuperAdminDashboard;
use App\Filament\Resources\TenantResource\Pages\CreateTenant;
use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\RecentTenantsTable;
use App\Filament\Widgets\TenantGrowthChart;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Webkul\User\Models\User as CrmUser;

it('uses a dedicated active landlord platform account for panel access', function () {
    $panel = Filament::getPanel('super-admin');

    $activeUser = new PlatformUser([
        'name' => 'Platform Admin',
        'email' => 'platform@example.test',
        'is_active' => true,
    ]);
    $inactiveUser = new PlatformUser([
        'name' => 'Disabled Admin',
        'email' => 'disabled@example.test',
        'is_active' => false,
    ]);

    expect(config('auth.guards.platform.provider'))->toBe('platform_users')
        ->and(config('auth.providers.platform_users.model'))->toBe(PlatformUser::class)
        ->and($activeUser->canAccessPanel($panel))->toBeTrue()
        ->and($inactiveUser->canAccessPanel($panel))->toBeFalse();
});

it('does not accept a Krayin CRM user as Super Admin authentication', function () {
    $this->actingAs(new CrmUser, 'user')
        ->get('/super-admin')
        ->assertRedirect('/super-admin/login');
});

it('allows an active platform user to reach the Super Admin panel', function () {
    $activeUser = new PlatformUser([
        'name' => 'Platform Admin',
        'email' => 'platform@example.test',
        'is_active' => true,
    ]);

    $this->actingAs($activeUser, 'platform')
        ->get('/super-admin')
        ->assertOk();
});

it('shows a platform overview instead of the default framework information card', function () {
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
    ]);
    DB::purge('landlord');
    DB::purge('tenant');

    $migration = require database_path('migrations/landlord/2026_10_09_000000_create_saas_landlord_tables.php');
    $migration->up();

    $activeUser = new PlatformUser([
        'name' => 'Platform Admin',
        'email' => 'platform@example.test',
        'is_active' => true,
    ]);

    $response = $this->actingAs($activeUser, 'platform')
        ->get('/super-admin')
        ->assertOk()
        ->assertSee('Platform overview')
        ->assertDontSee('filament v');

    $panel = Filament::getPanel('super-admin');

    expect($panel->getPages())->toContain(SuperAdminDashboard::class)
        ->and($panel->getWidgets())
        ->toContain(PlatformStatsOverview::class, TenantGrowthChart::class, RecentTenantsTable::class);
});

it('renders tenant provisioning credentials without database connection fields', function () {
    $activeUser = new PlatformUser([
        'name' => 'Platform Admin',
        'email' => 'platform@example.test',
        'is_active' => true,
    ]);

    $response = $this->actingAs($activeUser, 'platform')
        ->get('/super-admin/tenants/create')
        ->assertOk();

    $response->assertSee('Initial tenant administrator password');
    $this->assertStringNotContainsString('database', strtolower($response->getContent()));
});

it('blocks inactive platform users from the Super Admin panel', function () {
    $inactiveUser = new PlatformUser([
        'name' => 'Disabled Admin',
        'email' => 'disabled@example.test',
        'is_active' => false,
    ]);

    $this->actingAs($inactiveUser, 'platform')
        ->get('/super-admin')
        ->assertForbidden();
});

it('provisions tenants through the provisioning service and clears the one-time password', function () {
    $tenant = new Tenant;
    $data = [
        'name' => 'Example Tenant',
        'slug' => 'example',
        'domain' => 'example.krayin.localhost',
        'admin_name' => 'Example Admin',
        'admin_email' => 'admin@example.test',
        'admin_password' => 'a-secure-one-time-password',
    ];

    $provisioning = Mockery::mock(TenantProvisioningService::class);
    $provisioning->shouldReceive('provision')
        ->once()
        ->with(
            'Example Tenant',
            'example',
            'example.krayin.localhost',
            'Example Admin',
            'admin@example.test',
            'a-secure-one-time-password',
        )
        ->andReturn($tenant);

    app()->instance(TenantProvisioningService::class, $provisioning);

    $page = new CreateTenant;
    $page->data = $data;
    $handler = new ReflectionMethod(CreateTenant::class, 'handleRecordCreation');

    expect($handler->invoke($page, $data))->toBe($tenant)
        ->and($page->data)->toBe([]);
});
