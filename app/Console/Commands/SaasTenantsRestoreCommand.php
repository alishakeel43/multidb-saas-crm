<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantBackupService;
use Illuminate\Console\Command;
use Throwable;

class SaasTenantsRestoreCommand extends Command
{
    protected $signature = 'saas:tenants:restore {tenant : Tenant slug or ID} {timestamp : Backup timestamp folder} {--force : Confirm destructive restore}';

    protected $description = 'Restore a tenant database and storage from a backup timestamp';

    public function handle(TenantBackupService $backups): int
    {
        $token = $this->argument('tenant');
        $tenant = Tenant::query()
            ->where('slug', $token)
            ->orWhere('id', $token)
            ->first();

        if (! $tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Restore tenant [{$tenant->slug}] from {$this->argument('timestamp')}? This overwrites data.")) {
            $this->warn('Restore cancelled.');

            return self::SUCCESS;
        }

        try {
            $backups->restore($tenant, (string) $this->argument('timestamp'), confirm: true);
            $this->info("Restored [{$tenant->slug}] from {$this->argument('timestamp')}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
