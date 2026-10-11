<?php

namespace App\Console\Commands;

use Database\Seeders\TenantCrmSeeder;
use Database\Seeders\TenantDemoSeeder;

class TenantsSeedCommand extends TenantMaintenanceCommand
{
    protected $signature = 'tenants:seed {tenant? : Tenant slug or ID} {--all : Seed every tenant} {--demo : Also add repeatable demo CRM records}';

    protected $description = 'Seed Krayin CRM baseline data and optional demo records for tenant(s)';

    protected function tenantCommand(): string
    {
        return 'db:seed';
    }

    protected function tenantCommandArguments(): array
    {
        return [
            '--class' => $this->option('demo')
                ? TenantDemoSeeder::class
                : TenantCrmSeeder::class,
        ];
    }
}
