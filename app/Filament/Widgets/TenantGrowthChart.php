<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class TenantGrowthChart extends ChartWidget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'Tenant growth';

    protected static ?string $description = 'New workspaces created over the last six months.';

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $firstMonth = now()->startOfMonth()->subMonths(5);
        $months = collect(range(0, 5))
            ->map(fn (int $offset): Carbon => $firstMonth->copy()->addMonths($offset));

        $tenantCounts = Tenant::query()
            ->whereBetween('created_at', [$firstMonth, now()->endOfMonth()])
            ->get(['created_at'])
            ->groupBy(fn (Tenant $tenant): string => $tenant->created_at->format('Y-m'))
            ->map(fn ($tenants): int => $tenants->count());

        return [
            'datasets' => [
                [
                    'label' => 'New tenants',
                    'data' => $months
                        ->map(fn (Carbon $month): int => $tenantCounts->get($month->format('Y-m'), 0))
                        ->all(),
                    'fill' => 'start',
                    'tension' => 0.35,
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.12)',
                    'pointBackgroundColor' => '#0f766e',
                    'pointBorderColor' => '#0f766e',
                ],
            ],
            'labels' => $months->map(fn (Carbon $month): string => $month->format('M Y'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
