<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanFeatureResource\Pages;
use App\Models\PlanFeature;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanFeatureResource extends Resource
{
    protected static ?string $model = PlanFeature::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?string $navigationLabel = 'Plan features';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('plan_id')->relationship('plan', 'name')->searchable()->preload()->required(),
                Select::make('feature_key')
                    ->options(collect(config('features', []))->mapWithKeys(fn (array $feature, string $key): array => [
                        $key => $feature['label'] ?? $key,
                    ]))
                    ->searchable()
                    ->required()
                    ->helperText('Dependent features remain disabled whenever a required feature is disabled.'),
                Toggle::make('is_enabled')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')->label('Plan')->searchable()->sortable(),
                TextColumn::make('feature_key')->searchable()->sortable(),
                IconColumn::make('is_enabled')->boolean()->label('Enabled'),
            ])
            ->defaultSort('plan_id')
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
            'index' => Pages\ListPlanFeatures::route('/'),
            'create' => Pages\CreatePlanFeature::route('/create'),
            'edit' => Pages\EditPlanFeature::route('/{record}/edit'),
        ];
    }
}
