<?php

namespace App\Console\Commands;

class TenantsMigrateCommand extends TenantMaintenanceCommand
{
    protected $signature = 'tenants:migrate {tenant? : Tenant slug or ID} {--all : Run migrations for every tenant}';

    protected $description = 'Run safe pending migrations for one tenant or all tenants';

    protected function tenantCommand(): string
    {
        return 'migrate';
    }
}
