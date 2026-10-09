<?php

namespace App\Filament\Resources\TenantSupportUserResource\Pages;

use App\Filament\Resources\TenantSupportUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantSupportUsers extends ListRecords
{
    protected static string $resource = TenantSupportUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
