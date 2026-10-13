<?php

namespace Webkul\MarketingAutomation\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Webkul\MarketingAutomation\Console\Commands\RunMarketingAutomationCommand;
use Webkul\MarketingAutomation\Helpers\Entity\Activity;
use Webkul\MarketingAutomation\Helpers\Entity\Lead;
use Webkul\MarketingAutomation\Helpers\Entity\Person;
use Webkul\MarketingAutomation\Helpers\Entity\Quote;
use Webkul\MarketingAutomation\Http\Middleware\ExtendCampaignPayload;

class MarketingAutomationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'marketing_automation');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'marketing_automation');

        $this->app->register(EventServiceProvider::class);

        foreach ([
            'leads' => Lead::class,
            'activities' => Activity::class,
            'persons' => Person::class,
            'quotes' => Quote::class,
        ] as $entity => $class) {
            if (is_array(config('workflows.trigger_entities.'.$entity))) {
                config(['workflows.trigger_entities.'.$entity.'.class' => $class]);
            }
        }

        $this->app['router']->pushMiddlewareToGroup('web', ExtendCampaignPayload::class);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('marketing-automation:run --all')->everyMinute()->withoutOverlapping();
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunMarketingAutomationCommand::class,
            ]);
        }
    }
}
