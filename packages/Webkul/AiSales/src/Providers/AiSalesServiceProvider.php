<?php

namespace Webkul\AiSales\Providers;

use Illuminate\Support\ServiceProvider;

class AiSalesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/ai-sales.php', 'ai-sales');
    }
}
