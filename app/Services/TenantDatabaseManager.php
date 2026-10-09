<?php

namespace App\Services;

use App\Support\TenantIdentifier;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class TenantDatabaseManager
{
    private array $createdDatabases = [];

    private const PROTECTED_DATABASES = [
        'zaristech_saas',
        'tenant_bootstrap',
    ];

    public function databaseExists(string $slug): bool
    {
        $database = TenantIdentifier::databaseName($slug);

        $this->assertMampConnection();

        try {
            return DB::connection('landlord')
                ->table('information_schema.schemata')
                ->where('schema_name', $database)
                ->exists();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to check tenant database [{$database}]. Verify MAMP MySQL connectivity and landlord database privileges: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }

    public function isProtectedSlug(string $slug): bool
    {
        return in_array(TenantIdentifier::databaseName($slug), self::PROTECTED_DATABASES, true);
    }

    public function createDatabase(string $slug): string
    {
        $database = TenantIdentifier::databaseName($slug);
        $this->assertMampConnection();

        if ($this->isProtectedSlug($slug)) {
            throw new RuntimeException("Refusing to create protected database [{$database}].");
        }

        if ($this->databaseExists($slug)) {
            throw new RuntimeException("Tenant database [{$database}] already exists; refusing to modify it.");
        }

        try {
            DB::connection('landlord')->statement(
                "CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to create tenant database [{$database}]. Verify MAMP MySQL connectivity and CREATE DATABASE privileges: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        $this->createdDatabases[$slug] = true;

        return $database;
    }

    public function dropDatabase(string $slug): void
    {
        $database = TenantIdentifier::databaseName($slug);
        $this->assertMampConnection();

        if ($this->isProtectedSlug($slug)) {
            throw new RuntimeException("Refusing to drop protected database [{$database}].");
        }

        if (! isset($this->createdDatabases[$slug])) {
            throw new RuntimeException("Refusing to drop database [{$database}] not created by this manager instance.");
        }

        if ($this->databaseExists($slug)) {
            try {
                DB::connection('landlord')->statement("DROP DATABASE `{$database}`");
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    "Unable to remove tenant database [{$database}] during rollback. Verify MAMP MySQL connectivity and DROP DATABASE privileges: {$exception->getMessage()}",
                    previous: $exception,
                );
            }
        }

        unset($this->createdDatabases[$slug]);
    }

    public function assertMampConnection(): void
    {
        foreach (['landlord', 'tenant'] as $connectionName) {
            $connection = config("database.connections.{$connectionName}");

            if (
                ! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)
                || ($connection['host'] ?? null) !== '127.0.0.1'
                || (string) ($connection['port'] ?? '') !== '8889'
            ) {
                throw new RuntimeException('Tenant database management is restricted to MAMP MySQL at 127.0.0.1:8889.');
            }
        }

        if (config('database.connections.landlord.database') !== 'zaristech_saas') {
            throw new RuntimeException('Tenant database management requires the configured zaristech_saas landlord connection.');
        }
    }
}
