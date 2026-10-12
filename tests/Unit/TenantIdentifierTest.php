<?php

use App\Services\TenantDatabaseManager;
use App\Support\TenantIdentifier;
use Tests\TestCase;

uses(TestCase::class);

it('rejects reserved tenant slugs', function (string $slug) {
    expect(fn () => TenantIdentifier::validateSlug($slug))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'www',
    'admin',
    'super-admin',
    'api',
    'mail',
    'static',
    'assets',
    'landlord',
    'platform',
]);

it('rejects domains with a reserved first label', function (string $domain) {
    expect(fn () => TenantIdentifier::validateDomain($domain))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'www.example.test',
    'admin.example.test',
    'super-admin.example.test',
    'api.example.test',
    'mail.example.test',
    'static.example.test',
    'assets.example.test',
    'landlord.example.test',
    'platform.example.test',
]);

it('normalizes valid local tenant domains and derives safe database names', function () {
    expect(TenantIdentifier::validateDomain('Demo.Krayin.Localhost.'))
        ->toBe('demo.krayin.localhost')
        ->and(TenantIdentifier::databaseName('demo'))
        ->toBe('tenant_demo');
});

it('builds flat tenant domains when a suffix is configured', function () {
    config(['multitenancy.tenant_domain_suffix' => '-saas.zaristech.com']);

    expect(TenantIdentifier::domainForSlug('zdemo'))
        ->toBe('zdemo-saas.zaristech.com');
});

it('falls back to the tenant base domain when no suffix is configured', function () {
    config([
        'multitenancy.tenant_domain_suffix' => '',
        'multitenancy.tenant_base_domain' => 'saas.zaristech.com',
    ]);

    expect(TenantIdentifier::domainForSlug('zdemo'))
        ->toBe('zdemo.saas.zaristech.com');
});

it('rejects malformed and oversized tenant identifiers', function (string $slug) {
    expect(fn () => TenantIdentifier::databaseName($slug))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'Demo',
    'a_b',
    'a--b',
    str_repeat('a', 58),
]);

it('refuses database operations for the protected bootstrap database', function () {
    $host = config('tenant-provisioning.database_host_allowlist')[0];
    $port = (string) config('tenant-provisioning.database_port_allowlist')[0];

    config([
        'database.connections.landlord.driver' => 'mysql',
        'database.connections.landlord.host' => $host,
        'database.connections.landlord.port' => $port,
        'database.connections.landlord.database' => 'zaristech_saas',
        'database.connections.tenant.driver' => 'mysql',
        'database.connections.tenant.host' => $host,
        'database.connections.tenant.port' => $port,
    ]);

    $manager = app(TenantDatabaseManager::class);

    expect($manager->isProtectedSlug('bootstrap'))->toBeTrue()
        ->and(fn () => $manager->createDatabase('bootstrap'))
        ->toThrow(RuntimeException::class, 'Refusing to create protected database')
        ->and(fn () => $manager->dropDatabase('bootstrap'))
        ->toThrow(RuntimeException::class, 'Refusing to drop protected database')
        ->and(fn () => $manager->dropDatabase('unowned'))
        ->toThrow(RuntimeException::class, 'not created by this manager instance');
});
