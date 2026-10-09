<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Domain;
use App\Models\Tenant;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->visibleOn('create'),
                TextInput::make('domain')
                    ->label('Primary domain')
                    ->required()
                    ->maxLength(255)
                    ->unique(Domain::class, 'domain')
                    ->visibleOn('create'),
                TextInput::make('admin_name')
                    ->label('Tenant administrator name')
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                TextInput::make('admin_email')
                    ->label('Tenant administrator email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                TextInput::make('admin_password')
                    ->label('Initial tenant administrator password')
                    ->password()
                    ->revealable(false)
                    ->required()
                    ->minLength(12)
                    ->visibleOn('create'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'canceled' => 'Canceled',
                    ])
                    ->required()
                    ->visibleOn('edit'),
                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload()
                    ->visibleOn('edit'),
                DateTimePicker::make('trial_ends_at')
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('plan.name')->label('Plan')->placeholder('None'),
                TextColumn::make('trial_ends_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
