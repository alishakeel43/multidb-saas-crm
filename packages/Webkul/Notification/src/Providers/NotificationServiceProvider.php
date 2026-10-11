<?php

namespace Webkul\Notification\Providers;

use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'notification');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'notification');

        $this->app->register(EventServiceProvider::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }
}
