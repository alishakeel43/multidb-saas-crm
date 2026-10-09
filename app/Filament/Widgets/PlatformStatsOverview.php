<?php

namespace App\Filament\Widgets;

use App\Models\Domain;
use App\Models\Subscription;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        $totalTenants = Tenant::query()->count();
        $activeTenants = Tenant::query()->where('status', 'active')->count();
        $trialSubscriptions = Subscription::query()->where('status', 'trial')->count();
        $activeDomains = Domain::query()->where('is_active', true)->count();

        return [
            Stat::make('Tenant workspaces', number_format($totalTenants))
                ->description('Provisioned across the platform')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),
            Stat::make('Active workspaces', number_format($activeTenants))
                ->description('Currently enabled tenants')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Trial subscriptions', number_format($trialSubscriptions))
                ->description('Tenants in their trial period')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Active domains', number_format($activeDomains))
                ->description('Verified tenant entry points')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),
        ];
    }
}
