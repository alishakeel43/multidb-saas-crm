<?php

namespace Webkul\Telephony\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Telephony\Contracts\CallLog as CallLogContract;
use Webkul\Telephony\Models\CallLog;
use Webkul\Telephony\Services\TelephonyService;
use Webkul\Telephony\Services\TenantTelephonyRouter;
use Webkul\Telephony\Services\TwilioClientFactory;

class TelephonyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bindIf(CallLogContract::class, CallLog::class);

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__.'/../Config/core_config.php', 'core_config');

        $this->app->singleton(TwilioClientFactory::class);
        $this->app->singleton(TelephonyService::class);
        $this->app->singleton(TenantTelephonyRouter::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'telephony');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'telephony');

        $this->app->register(EventServiceProvider::class);
        $this->app->register(ModuleServiceProvider::class);

        $this->app->booted(function () {
            $concord = app('concord');

            if (! $concord->model(CallLogContract::class)) {
                $concord->registerModel(CallLogContract::class, CallLog::class);
            }
        });
    }
}
