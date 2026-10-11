<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantFeatureResource\Pages;
use App\Filament\Support\RelatedRecordSelect;
use App\Models\TenantFeature;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantFeatureResource extends Resource
{
    protected static ?string $model = TenantFeature::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?string $navigationLabel = 'Feature overrides (legacy)';

    protected static ?string $slug = 'tenant-feature-records';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                RelatedRecordSelect::tenant(),
                Select::make('feature_key')
                    ->options(collect(config('features', []))->mapWithKeys(fn (array $feature, string $key): array => [
                        $key => $feature['label'] ?? $key,
                    ]))
                    ->searchable()
                    ->required()
                    ->helperText('This override cannot enable a feature while one of its requirements is disabled.'),
                Toggle::make('is_enabled')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label('Tenant')->searchable()->sortable(),
                TextColumn::make('feature_key')->searchable()->sortable(),
                IconColumn::make('is_enabled')->boolean()->label('Enabled'),
            ])
            ->defaultSort('tenant_id')
            ->actions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantFeatures::route('/'),
            'create' => Pages\CreateTenantFeature::route('/create'),
            'edit' => Pages\EditTenantFeature::route('/{record}/edit'),
        ];
    }
}
