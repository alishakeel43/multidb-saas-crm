<?php

namespace App\Console\Commands;

use Database\Seeders\TenantCrmSeeder;

class TenantsSeedCommand extends TenantMaintenanceCommand
{
    protected $signature = 'tenants:seed {tenant? : Tenant slug or ID} {--all : Seed every tenant}';

    protected $description = 'Seed safe Krayin CRM baseline data for one tenant or all tenants';

    protected function tenantCommand(): string
    {
        return 'db:seed';
    }

    protected function tenantCommandArguments(): array
    {
        return ['--class' => TenantCrmSeeder::class];
    }
}
