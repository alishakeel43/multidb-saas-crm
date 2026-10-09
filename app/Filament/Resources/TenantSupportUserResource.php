<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantSupportUserResource\Pages;
use App\Models\TenantSupportUser;
use App\Services\TenantSupportAccountService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantSupportUserResource extends Resource
{
    protected static ?string $model = TenantSupportUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?string $navigationLabel = 'Support users';

    protected static ?string $modelLabel = 'support user';

    protected static ?string $pluralModelLabel = 'support users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->helperText('This email must not already belong to a CRM user in the selected tenant.'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(12)
                    ->maxLength(72)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Tenant-side password is hashed. Leave blank when editing to keep the current password.'),
                Toggle::make('is_active')
                    ->label('Account active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label('Tenant')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->defaultSort('tenant_id')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(fn (TenantSupportUser $record) => app(TenantSupportAccountService::class)->delete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantSupportUsers::route('/'),
            'create' => Pages\CreateTenantSupportUser::route('/create'),
            'edit' => Pages\EditTenantSupportUser::route('/{record}/edit'),
        ];
    }
}
