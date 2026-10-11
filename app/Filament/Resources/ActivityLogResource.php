<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\Activity;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Audit log';

    protected static ?string $modelLabel = 'Audit event';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('description')->searchable()->wrap(),
                TextColumn::make('event')->badge()->sortable(),
                TextColumn::make('causer.email')->label('Actor')->placeholder('system'),
                TextColumn::make('tenant_id')
                    ->label('Tenant ID')
                    ->placeholder('—')
                    ->getStateUsing(fn (Activity $record): ?string => static::tenantIdFromProperties($record))
                    ->tooltip(fn (?string $state): ?string => $state === null
                        ? 'Platform-level event (not tied to a tenant workspace)'
                        : null),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('event')->options([
                    'created' => 'Created',
                    'paid' => 'Paid',
                    'voided' => 'Voided',
                    'trial' => 'Trial',
                    'activated' => 'Activated',
                    'canceled' => 'Canceled',
                    'backup' => 'Backup',
                    'mfa_enabled' => 'MFA enabled',
                ]),
            ])
            ->actions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }

    public static function tenantIdFromProperties(Activity $record): ?string
    {
        $properties = $record->properties;

        if ($properties instanceof Collection) {
            $tenantId = $properties->get('tenant_id');
        } elseif (is_array($properties)) {
            $tenantId = $properties['tenant_id'] ?? null;
        } else {
            $tenantId = null;
        }

        if ($tenantId === null || $tenantId === '') {
            return null;
        }

        return (string) $tenantId;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
