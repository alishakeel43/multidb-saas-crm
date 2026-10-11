<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

class SuperAdminDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $title = 'Operations overview';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = -20;

    protected ?string $heading = 'Operations overview';

    protected ?string $subheading = 'Tenant growth, subscription health, and recent workspace activity across the ZARIS platform.';

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'lg' => 2,
            'xl' => 2,
        ];
    }
}
