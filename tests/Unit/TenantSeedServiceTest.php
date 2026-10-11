<?php

use App\Models\Tenant;
use App\Services\TenantSeedService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
    ]);

    DB::purge('landlord');
    DB::reconnect('landlord');

    Schema::connection('landlord')->create('tenants', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('database')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });
});

it('calls tenants:seed for a single tenant with optional demo flag', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Demo Co',
        'slug' => 'demo',
        'database' => 'tenant_demo',
        'status' => 'active',
    ]);

    Artisan::shouldReceive('call')
        ->once()
        ->with('tenants:seed', [
            'tenant' => 'demo',
            '--no-interaction' => true,
            '--demo' => true,
        ])
        ->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('');

    app(TenantSeedService::class)->seed($tenant, true);
});

it('aggregates successes and failures when seeding all tenants', function () {
    Tenant::query()->create([
        'name' => 'Ok Co',
        'slug' => 'ok',
        'database' => 'tenant_ok',
        'status' => 'active',
    ]);
    Tenant::query()->create([
        'name' => 'Bad Co',
        'slug' => 'bad',
        'database' => 'tenant_bad',
        'status' => 'active',
    ]);

    Artisan::shouldReceive('call')
        ->twice()
        ->andReturnUsing(function (string $command, array $arguments) {
            return ($arguments['tenant'] ?? null) === 'bad' ? 1 : 0;
        });
    Artisan::shouldReceive('output')->andReturn('boom');

    $result = app(TenantSeedService::class)->seedAll(false);

    expect($result['succeeded'])->toBe(1)
        ->and($result['failed'])->toHaveCount(1)
        ->and($result['failed'][0]['slug'])->toBe('bad');
});
