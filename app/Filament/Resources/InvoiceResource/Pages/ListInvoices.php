<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\TenantResource;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\BillingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
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
                        ->reactive()
                        ->helperText(fn (): string => Tenant::query()->exists()
                            ? 'Need a workspace that is not listed? Create a new tenant (opens in a new tab), then refresh this list.'
                            : 'No tenants yet. Create a new tenant, then come back and select it here.')
                        ->hintAction(
                            FormAction::make('createTenant')
                                ->label('Create new tenant')
                                ->icon('heroicon-m-plus')
                                ->url(fn (): string => TenantResource::getUrl('create'))
                                ->openUrlInNewTab()
                        ),
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
