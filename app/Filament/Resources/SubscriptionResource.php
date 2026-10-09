<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Tenants & billing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tenant_id')->relationship('tenant', 'name')->searchable()->preload()->required(),
                Select::make('plan_id')->relationship('plan', 'name')->searchable()->preload()->required(),
                Select::make('status')->options([
                    'trial' => 'Trial',
                    'active' => 'Active',
                    'past_due' => 'Past due',
                    'canceled' => 'Canceled',
                    'expired' => 'Expired',
                ])->required(),
                DateTimePicker::make('starts_at')->required(),
                DateTimePicker::make('ends_at'),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('canceled_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label('Tenant')->searchable()->sortable(),
                TextColumn::make('plan.name')->label('Plan')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->dateTime()->sortable(),
                TextColumn::make('trial_ends_at')->dateTime()->sortable(),
            ])
            ->defaultSort('starts_at', 'desc')
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
