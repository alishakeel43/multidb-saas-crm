<?php

namespace App\Filament\Widgets;

use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalTenants = Tenant::query()->count();
        $activeTenants = Tenant::query()->where('status', 'active')->count();
        $trialSubscriptions = Subscription::query()->where('status', 'trial')->count();
        $openInvoices = $this->openInvoiceCount();
        $activeDomains = Domain::query()->where('is_active', true)->count();

        return [
            Stat::make('Tenant workspaces', number_format($totalTenants))
                ->description($activeTenants.' currently active')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart($this->sparkline()),
            Stat::make('Trial subscriptions', number_format($trialSubscriptions))
                ->description('Workspaces still evaluating')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Open invoices', number_format($openInvoices))
                ->description('Awaiting collection')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($openInvoices > 0 ? 'danger' : 'success'),
            Stat::make('Active domains', number_format($activeDomains))
                ->description('Tenant entry points online')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),
        ];
    }

    private function openInvoiceCount(): int
    {
        try {
            if (! Schema::connection('landlord')->hasTable('invoices')) {
                return 0;
            }

            return Invoice::query()->where('status', 'open')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return list<int>
     */
    private function sparkline(): array
    {
        $counts = Tenant::query()
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn (Tenant $tenant): string => $tenant->created_at->format('Y-m-d'))
            ->map->count();

        return collect(range(0, 6))
            ->map(fn (int $offset): int => (int) $counts->get(now()->subDays(6 - $offset)->format('Y-m-d'), 0))
            ->all();
    }
}
