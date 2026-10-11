<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\BillingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createFromSubscription')
                ->label('Create invoice')
                ->form([
                    Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(fn () => Tenant::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->reactive(),
                    Select::make('subscription_id')
                        ->label('Subscription')
                        ->options(function (callable $get) {
                            $tenantId = $get('tenant_id');

                            if (! $tenantId) {
                                return [];
                            }

                            return Subscription::query()
                                ->where('tenant_id', $tenantId)
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn (Subscription $subscription) => [
                                    $subscription->id => "#{$subscription->id} — {$subscription->status}",
                                ]);
                        })
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $subscription = Subscription::query()->with(['tenant', 'plan'])->findOrFail($data['subscription_id']);
                        app(BillingService::class)->createInvoiceForSubscription($subscription);
                        Notification::make()->title('Invoice created')->success()->send();
                    } catch (Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
