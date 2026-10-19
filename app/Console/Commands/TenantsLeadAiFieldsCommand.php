<?php

namespace App\Console\Commands;

class TenantsLeadAiFieldsCommand extends TenantMaintenanceCommand
{
    protected $signature = 'tenants:lead-ai-fields {tenant? : Tenant slug or ID} {--all : Run for every tenant} {--remove : Delete the AI fields instead of adding them}';

    protected $description = 'Add or remove the AI-sales extra lead fields for one tenant or all tenants';

    protected function tenantCommand(): string
    {
        return 'crm:lead-ai-fields';
    }

    protected function tenantCommandArguments(): array
    {
        return $this->option('remove') ? ['--remove' => true] : [];
    }
}
