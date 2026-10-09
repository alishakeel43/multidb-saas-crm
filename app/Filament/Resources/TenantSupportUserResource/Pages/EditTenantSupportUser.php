<?php

namespace App\Filament\Resources\TenantSupportUserResource\Pages;

use App\Filament\Resources\TenantSupportUserResource;
use App\Services\TenantSupportAccountService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTenantSupportUser extends EditRecord
{
    protected static string $resource = TenantSupportUserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return app(TenantSupportAccountService::class)->update(
            $record,
            [...$data, 'is_active' => (bool) ($data['is_active'] ?? false)],
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
