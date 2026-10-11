<?php

namespace Webkul\UnifiedInbox\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\UnifiedInbox\Services\MetaGraphClient;

class UnifiedInboxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'unified_inbox');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'unified_inbox');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/unified-inbox.php', 'unified-inbox');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        $this->app->singleton(MetaGraphClient::class);
    }
}
