<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\Sqlite;
use Throwable;

class TenantBackupService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function backup(Tenant $tenant): array
    {
        $timestamp = now()->format('Ymd_His');
        $basePath = rtrim(config('saas.tenant_backups.path'), '/').'/'.$tenant->slug.'/'.$timestamp;

        File::ensureDirectoryExists($basePath);

        $databaseDump = $basePath.'/database.sql';
        $storageArchive = $basePath.'/storage';
        $manifestPath = $basePath.'/manifest.json';

        $this->dumpTenantDatabase($tenant, $databaseDump);

        $tenantStorage = storage_path('tenants/'.$tenant->slug);

        if (File::isDirectory($tenantStorage)) {
            File::copyDirectory($tenantStorage, $storageArchive);
        } else {
            File::ensureDirectoryExists($storageArchive);
        }

        $manifest = [
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'database' => $tenant->database,
            'timestamp' => $timestamp,
            'created_at' => now()->toIso8601String(),
            'paths' => [
                'database' => $databaseDump,
                'storage' => $storageArchive,
            ],
        ];

        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->auditLogger->log('tenant.backup_created', [
            'tenant_id' => $tenant->id,
            'timestamp' => $timestamp,
        ], $tenant, 'backup');

        $this->prune($tenant);

        return $manifest;
    }

    public function restore(Tenant $tenant, string $timestamp, bool $confirm = false): void
    {
        if (! $confirm) {
            throw new RuntimeException('Restore requires explicit confirmation.');
        }

        $basePath = rtrim(config('saas.tenant_backups.path'), '/').'/'.$tenant->slug.'/'.$timestamp;
        $manifestPath = $basePath.'/manifest.json';

        if (! File::exists($manifestPath)) {
            throw new RuntimeException("Backup manifest not found for [{$tenant->slug}/{$timestamp}].");
        }

        $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $databaseDump = $manifest['paths']['database'] ?? ($basePath.'/database.sql');
        $storageArchive = $manifest['paths']['storage'] ?? ($basePath.'/storage');

        if (! File::exists($databaseDump)) {
            throw new RuntimeException('Backup database dump is missing.');
        }

        $this->restoreTenantDatabase($tenant, $databaseDump);

        $tenantStorage = storage_path('tenants/'.$tenant->slug);
        File::ensureDirectoryExists($tenantStorage);

        if (File::isDirectory($storageArchive)) {
            File::deleteDirectory($tenantStorage);
            File::copyDirectory($storageArchive, $tenantStorage);
        }

        $this->auditLogger->log('tenant.backup_restored', [
            'tenant_id' => $tenant->id,
            'timestamp' => $timestamp,
        ], $tenant, 'restore');
    }

    /**
     * @return list<array{timestamp: string, path: string, created_at: ?string}>
     */
    public function listBackups(?Tenant $tenant = null): array
    {
        $root = rtrim(config('saas.tenant_backups.path'), '/');

        if (! File::isDirectory($root)) {
            return [];
        }

        $slugs = $tenant ? [$tenant->slug] : File::directories($root);
        $results = [];

        foreach ($slugs as $slugPath) {
            $slug = $tenant ? $tenant->slug : basename($slugPath);
            $tenantRoot = $tenant ? $root.'/'.$slug : $slugPath;

            if (! File::isDirectory($tenantRoot)) {
                continue;
            }

            foreach (File::directories($tenantRoot) as $backupDir) {
                $timestamp = basename($backupDir);
                $manifest = $backupDir.'/manifest.json';
                $createdAt = null;

                if (File::exists($manifest)) {
                    $data = json_decode(File::get($manifest), true);
                    $createdAt = $data['created_at'] ?? null;
                }

                $results[] = [
                    'tenant_slug' => $slug,
                    'timestamp' => $timestamp,
                    'path' => $backupDir,
                    'created_at' => $createdAt,
                ];
            }
        }

        usort($results, fn (array $a, array $b): int => strcmp($b['timestamp'], $a['timestamp']));

        return $results;
    }

    public function prune(Tenant $tenant): void
    {
        $retentionDays = (int) config('saas.tenant_backups.retention_days', 14);
        $cutoff = now()->subDays($retentionDays);
        $root = rtrim(config('saas.tenant_backups.path'), '/').'/'.$tenant->slug;

        if (! File::isDirectory($root)) {
            return;
        }

        foreach (File::directories($root) as $backupDir) {
            if (File::lastModified($backupDir) < $cutoff->getTimestamp()) {
                File::deleteDirectory($backupDir);
            }
        }
    }

    private function dumpTenantDatabase(Tenant $tenant, string $destination): void
    {
        $connection = config('database.connections.tenant');
        $driver = $connection['driver'] ?? null;

        try {
            if ($driver === 'sqlite') {
                $database = $connection['database'] ?? null;

                if (! is_string($database) || $database === '' || $database === ':memory:') {
                    File::put($destination, "-- sqlite dump placeholder for {$tenant->database}\n");

                    return;
                }

                Sqlite::create()
                    ->setDbName($database)
                    ->dumpToFile($destination);

                return;
            }

            MySql::create()
                ->setHost($connection['host'] ?? '127.0.0.1')
                ->setPort((string) ($connection['port'] ?? 3306))
                ->setDbName($tenant->database)
                ->setUserName($connection['username'] ?? '')
                ->setPassword($connection['password'] ?? '')
                ->dumpToFile($destination);
        } catch (Throwable $exception) {
            throw new RuntimeException('Tenant database dump failed: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function restoreTenantDatabase(Tenant $tenant, string $dumpPath): void
    {
        $connection = config('database.connections.tenant');
        $driver = $connection['driver'] ?? null;

        if ($driver === 'sqlite') {
            $database = $connection['database'] ?? null;

            if (is_string($database) && $database !== '' && $database !== ':memory:') {
                File::copy($dumpPath, $database);
            }

            return;
        }

        $host = escapeshellarg($connection['host'] ?? '127.0.0.1');
        $port = escapeshellarg((string) ($connection['port'] ?? 3306));
        $user = escapeshellarg($connection['username'] ?? '');
        $database = escapeshellarg($tenant->database);
        $file = escapeshellarg($dumpPath);
        $password = $connection['password'] ?? '';

        $command = sprintf(
            'mysql -h %s -P %s -u %s %s %s < %s',
            $host,
            $port,
            $user,
            $password !== '' ? '-p'.escapeshellarg($password) : '',
            $database,
            $file,
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('Tenant database restore failed.');
        }
    }
}
