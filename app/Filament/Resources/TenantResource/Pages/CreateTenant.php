<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Services\TenantProvisioningService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $adminPassword = $data['admin_password'];
        unset($data['admin_password']);
        $this->data = [];
        $baseDomain = config('multitenancy.tenant_base_domain', 'localhost');
        $domain = $data['domain'] ?? ($data['slug'].'.'.$baseDomain);

        try {
            return app(TenantProvisioningService::class)->provision(
                name: $data['name'],
                slug: $data['slug'],
                domain: $domain,
                adminName: $data['admin_name'],
                adminEmail: $data['admin_email'],
                adminPassword: $adminPassword,
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            throw ValidationException::withMessages([
                'data.slug' => $exception->getMessage(),
            ]);
        }
    }
}
