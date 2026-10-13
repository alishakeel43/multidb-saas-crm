<?php

namespace Webkul\MarketingAutomation\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Webkul\MarketingAutomation\Console\Commands\RunMarketingAutomationCommand;

class MarketingAutomationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'marketing_automation');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'marketing_automation');

        $this->app->register(EventServiceProvider::class);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('marketing-automation:run --all')->everyMinute()->withoutOverlapping();
        });
    }

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RunMarketingAutomationCommand::class,
            ]);
        }
    }
}
