<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Services\TenantProvisioningService;
use App\Support\TenantIdentifier;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $adminPassword = $data['admin_password'];
        $domain = $data['domain'] ?? TenantIdentifier::domainForSlug($data['slug']);

        try {
            $tenant = app(TenantProvisioningService::class)->provision(
                name: $data['name'],
                slug: $data['slug'],
                domain: $domain,
                adminName: $data['admin_name'],
                adminEmail: $data['admin_email'],
                adminPassword: $adminPassword,
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            throw ValidationException::withMessages([
                $this->fieldForProvisioningException($exception) => $exception->getMessage(),
            ]);
        }

        // Drop the one-time password from Livewire state only after a successful provision.
        $this->data = [];

        return $tenant;
    }

    private function fieldForProvisioningException(Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());

        return match (true) {
            str_contains($message, 'administrator email') => 'data.admin_email',
            str_contains($message, 'administrator name') => 'data.admin_name',
            str_contains($message, 'administrator password') => 'data.admin_password',
            str_contains($message, 'tenant name') => 'data.name',
            str_contains($message, 'domain') => 'data.slug',
            str_contains($message, 'slug'),
            str_contains($message, 'database'),
            str_contains($message, 'storage') => 'data.slug',
            default => 'data.name',
        };
    }
}
