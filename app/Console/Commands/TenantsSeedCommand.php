<?php

namespace App\Console\Commands;

use Database\Seeders\TenantAllFeaturesDemoSeeder;
use Database\Seeders\TenantCrmSeeder;
use Database\Seeders\TenantDemoSeeder;

class TenantsSeedCommand extends TenantMaintenanceCommand
{
    protected $signature = 'tenants:seed {tenant? : Tenant slug or ID} {--all : Seed every tenant} {--demo : Also add repeatable demo CRM records} {--features : Add linked dummy data for every feature, including unified channels}';

    protected $description = 'Seed Krayin CRM baseline data, optional demo records, or all-feature dummy data for tenant(s)';

    protected function tenantCommand(): string
    {
        return 'db:seed';
    }

    protected function tenantCommandArguments(): array
    {
        $class = TenantCrmSeeder::class;

        if ($this->option('features')) {
            $class = TenantAllFeaturesDemoSeeder::class;
        } elseif ($this->option('demo')) {
            $class = TenantDemoSeeder::class;
        }

        return [
            '--class' => $class,
        ];
    }
}
