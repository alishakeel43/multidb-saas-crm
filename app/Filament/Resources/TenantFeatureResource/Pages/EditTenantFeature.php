<?php

namespace App\Filament\Resources\TenantFeatureResource\Pages;

use App\Filament\Resources\TenantFeatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantFeature extends EditRecord
{
    protected static string $resource = TenantFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
