<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Support\RelatedRecordSelect;
use App\Models\Invoice;
use App\Services\BillingService;
use App\Services\StripeCheckoutService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Invoice')
                ->columns(2)
                ->schema([
                    RelatedRecordSelect::tenant(),
                    Select::make('subscription_id')->relationship('subscription', 'id')->searchable()->preload(),
                    TextInput::make('number')->disabled()->dehydrated(false),
                    Select::make('status')->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'paid' => 'Paid',
                        'void' => 'Void',
                        'uncollectible' => 'Uncollectible',
                    ])->required(),
                    TextInput::make('currency')->length(3)->required(),
                    TextInput::make('subtotal')->numeric()->disabled(),
                    TextInput::make('tax')->numeric()->disabled(),
                    TextInput::make('total')->numeric()->disabled(),
                    Textarea::make('notes')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('tenant.name')->label('Tenant')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'open', 'draft' => 'warning',
                        'void', 'uncollectible' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total')->money(fn (Invoice $record): string => $record->currency)->sortable(),
                TextColumn::make('due_at')->dateTime()->sortable(),
                TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                EditAction::make(),
                Action::make('markPaid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record): bool => $record->isOpen())
                    ->action(function (Invoice $record): void {
                        app(BillingService::class)->markPaidManually($record);
                        Notification::make()->title('Invoice marked paid')->success()->send();
                    }),
                Action::make('collectStripe')
                    ->label('Collect with Stripe')
                    ->icon('heroicon-o-credit-card')
                    ->visible(fn (Invoice $record): bool => $record->isOpen() && app(StripeCheckoutService::class)->isConfigured())
                    ->action(function (Invoice $record) {
                        try {
                            $result = app(StripeCheckoutService::class)->createCheckoutSession(
                                $record,
                                url('/super-admin/invoices'),
                                url('/super-admin/invoices'),
                            );

                            Notification::make()
                                ->title('Stripe Checkout session created')
                                ->body($result['url'])
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('void')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record): bool => $record->isOpen())
                    ->action(function (Invoice $record): void {
                        app(BillingService::class)->void($record);
                        Notification::make()->title('Invoice voided')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
