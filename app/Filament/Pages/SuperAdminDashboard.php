<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

class SuperAdminDashboard extends Dashboard
{
    protected static ?string $title = 'SaaS overview';

    protected static ?string $navigationLabel = 'Overview';

    protected ?string $heading = 'Platform overview';

    protected ?string $subheading = 'Monitor tenant workspaces, subscriptions, and CRM activity from one place.';

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }
}
