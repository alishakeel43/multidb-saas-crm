<?php

namespace App\Filament\Resources\TenantSupportUserResource\Pages;

use App\Filament\Resources\TenantSupportUserResource;
use App\Models\Tenant;
use App\Services\TenantSupportAccountService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenantSupportUser extends CreateRecord
{
    protected static string $resource = TenantSupportUserResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $password = $data['password'];
        unset($data['password']);
        $this->data = [];

        return app(TenantSupportAccountService::class)->create(
            Tenant::query()->findOrFail($data['tenant_id']),
            [...$data, 'password' => $password],
        );
    }
}
