<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantBackupService;
use Illuminate\Console\Command;
use Throwable;

class SaasTenantsBackupCommand extends Command
{
    protected $signature = 'saas:tenants:backup {tenant? : Tenant slug or ID} {--all : Backup every tenant}';

    protected $description = 'Create a per-tenant database and storage backup';

    public function handle(TenantBackupService $backups): int
    {
        $tenants = $this->resolveTenants();

        if ($tenants->isEmpty()) {
            $this->error('No tenants matched.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $manifest = $backups->backup($tenant);
                $this->info("Backed up [{$tenant->slug}] at {$manifest['timestamp']}");
            } catch (Throwable $exception) {
                $this->error("Backup failed for [{$tenant->slug}]: {$exception->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    private function resolveTenants()
    {
        if ($this->option('all')) {
            return Tenant::query()->orderBy('id')->get();
        }

        $token = $this->argument('tenant');

        if (! $token) {
            $this->error('Provide a tenant slug/ID or use --all.');

            return collect();
        }

        $tenant = Tenant::query()
            ->where('slug', $token)
            ->orWhere('id', $token)
            ->first();

        return $tenant ? collect([$tenant]) : collect();
    }
}
