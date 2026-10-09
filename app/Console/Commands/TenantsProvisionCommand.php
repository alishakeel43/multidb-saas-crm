<?php

namespace App\Console\Commands;

use App\Services\TenantProvisioningService;
use Illuminate\Console\Command;
use Throwable;

class TenantsProvisionCommand extends Command
{
    protected $signature = 'tenants:provision
        {slug : New tenant slug}
        {domain : New tenant primary domain}
        {--name= : Tenant display name}
        {--admin-name= : Tenant administrator name}
        {--admin-email= : Tenant administrator email}
        {--admin-password= : Explicit administrator password; prefer TENANT_ADMIN_PASSWORD in .env}';

    protected $description = 'Provision one tenant database and administrator in a local/testing environment';

    public function handle(TenantProvisioningService $provisioning): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Tenant provisioning via CLI is restricted to local/testing environments.');

            return self::FAILURE;
        }

        $email = $this->option('admin-email');
        $password = $this->option('admin-password') ?: config('tenant-provisioning.admin_password');

        if (! $email || ! $password) {
            $this->error('Provide --admin-email and --admin-password or set TENANT_ADMIN_PASSWORD in the local .env.');

            return self::FAILURE;
        }

        try {
            $tenant = $provisioning->provision(
                name: $this->option('name') ?: str($this->argument('slug'))->headline()->toString(),
                slug: $this->argument('slug'),
                domain: $this->argument('domain'),
                adminName: $this->option('admin-name') ?: 'Tenant Administrator',
                adminEmail: $email,
                adminPassword: $password,
            );
        } catch (Throwable $exception) {
            $this->error('Tenant provisioning failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Provisioned [{$tenant->slug}] at [{$tenant->domains()->where('is_primary', true)->value('domain')}] using [{$tenant->database}].");
        $this->comment("Administrator: {$email}");

        return self::SUCCESS;
    }
}
