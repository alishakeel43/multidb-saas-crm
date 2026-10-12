<?php

namespace App\Services;

use App\Support\TenantIdentifier;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class TenantDatabaseManager
{
    private array $createdDatabases = [];

    public function databaseExists(string $slug): bool
    {
        $database = TenantIdentifier::databaseName($slug);

        $this->assertProvisioningConnection();

        try {
            return DB::connection('landlord')
                ->table('information_schema.schemata')
                ->where('schema_name', $database)
                ->exists();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to check tenant database [{$database}]. Verify database connectivity and landlord database privileges: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }

    public function isProtectedSlug(string $slug): bool
    {
        $protectedDatabases = array_merge(
            [
                config('database.connections.landlord.database'),
                config('database.connections.tenant.database'),
            ],
            config('tenant-provisioning.protected_databases', []),
        );

        return in_array(TenantIdentifier::databaseName($slug), array_filter($protectedDatabases), true);
    }

    public function createDatabase(string $slug): string
    {
        $database = TenantIdentifier::databaseName($slug);
        $this->assertProvisioningConnection();

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
                "Unable to create tenant database [{$database}]. Verify database connectivity and CREATE DATABASE privileges: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        $this->createdDatabases[$slug] = true;

        return $database;
    }

    public function dropDatabase(string $slug): void
    {
        $database = TenantIdentifier::databaseName($slug);
        $this->assertProvisioningConnection();

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
                    "Unable to remove tenant database [{$database}] during rollback. Verify database connectivity and DROP DATABASE privileges: {$exception->getMessage()}",
                    previous: $exception,
                );
            }
        }

        unset($this->createdDatabases[$slug]);
    }

    public function assertProvisioningConnection(): void
    {
        $allowedHosts = config('tenant-provisioning.database_host_allowlist', []);
        $allowedPorts = array_map('strval', config('tenant-provisioning.database_port_allowlist', []));
        $targetHost = null;
        $targetPort = null;

        foreach (['landlord', 'tenant'] as $connectionName) {
            $connection = config("database.connections.{$connectionName}");

            if (! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
                throw new RuntimeException('Tenant database management requires MySQL or MariaDB connections.');
            }

            $host = $connection['host'] ?? null;
            $port = (string) ($connection['port'] ?? '');

            if (! in_array($host, $allowedHosts, true) || ! in_array($port, $allowedPorts, true)) {
                throw new RuntimeException('Tenant database management target is not in the configured host/port allowlist.');
            }

            $targetHost ??= $host;
            $targetPort ??= $port;

            if ($host !== $targetHost || $port !== $targetPort) {
                throw new RuntimeException('Tenant and landlord database connections must use the same host and port.');
            }
        }
    }
}
