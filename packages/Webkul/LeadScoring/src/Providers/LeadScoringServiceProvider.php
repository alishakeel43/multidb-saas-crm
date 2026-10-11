<?php

namespace Webkul\LeadScoring\Providers;

use Illuminate\Support\ServiceProvider;

class LeadScoringServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'lead_scoring');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'lead_scoring');
        $this->app->register(EventServiceProvider::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }
}
