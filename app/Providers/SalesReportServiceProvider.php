<?php

namespace App\Providers;

use App\Http\Controllers\Admin\SalesReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SalesReportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->name('admin.')
            ->group(function () {
                Route::get('sales-reports', [SalesReportController::class, 'index'])
                    ->name('sales-reports.index');
                Route::get('sales-reports/export', [SalesReportController::class, 'export'])
                    ->name('sales-reports.export');
            });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/sales_report_menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/sales_report_acl.php', 'acl');
    }
}
