<?php

namespace App\Filament\Resources\TenantFeatureResource\Pages;

use App\Filament\Resources\TenantFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenantFeatures extends ListRecords
{
    protected static string $resource = TenantFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
