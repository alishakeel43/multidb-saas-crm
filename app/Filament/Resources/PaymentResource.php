<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('tenant_id')->relationship('tenant', 'name')->disabled(),
            Select::make('invoice_id')->relationship('invoice', 'number')->disabled(),
            TextInput::make('provider')->disabled(),
            TextInput::make('amount')->numeric()->disabled(),
            TextInput::make('currency')->disabled(),
            TextInput::make('status')->disabled(),
            TextInput::make('provider_payment_id')->disabled(),
            DateTimePicker::make('paid_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('tenant.name')->label('Tenant')->searchable(),
                TextColumn::make('invoice.number')->label('Invoice')->searchable(),
                TextColumn::make('provider')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('amount')->money(fn (Payment $record): string => $record->currency),
                TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
