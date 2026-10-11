<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Models\Activity;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Collection;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('description'),
            TextEntry::make('event'),
            TextEntry::make('causer.email')->label('Actor')->placeholder('system'),
            TextEntry::make('tenant_id')
                ->label('Tenant ID')
                ->placeholder('Platform event — no tenant')
                ->getStateUsing(fn (Activity $record): ?string => ActivityLogResource::tenantIdFromProperties($record)),
            TextEntry::make('created_at')->dateTime(),
            KeyValueEntry::make('properties')
                ->label('Properties')
                ->columnSpanFull()
                ->getStateUsing(fn (Activity $record): array => $this->flattenProperties($record->properties)),
        ]);
    }

    /**
     * KeyValueEntry only accepts scalar string values; nested arrays (e.g. settings payloads) must be encoded.
     *
     * @param  array<string, mixed>|Collection|null  $properties
     * @return array<string, string>
     */
    private function flattenProperties(array|Collection|null $properties): array
    {
        if ($properties instanceof Collection) {
            $properties = $properties->toArray();
        }

        if (! is_array($properties) || $properties === []) {
            return [];
        }

        $flat = [];

        foreach ($properties as $key => $value) {
            $flat[(string) $key] = match (true) {
                is_array($value), is_object($value) => (string) json_encode(
                    $value,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
                ),
                is_bool($value) => $value ? 'true' : 'false',
                $value === null => 'null',
                default => (string) $value,
            };
        }

        return $flat;
    }
}
