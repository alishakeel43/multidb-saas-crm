<?php

namespace Webkul\MarketingAutomation\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\MarketingAutomation\Services\AttributionRecorder;
use Webkul\MarketingAutomation\Services\WorkflowEngine;
use Webkul\MarketingAutomation\Support\Catalog;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        foreach (Catalog::listenedEvents() as $event) {
            $this->app['events']->listen($event, function ($entity) use ($event): void {
                if (! is_object($entity)) {
                    return;
                }

                try {
                    app(WorkflowEngine::class)->handle($event, $entity);
                } catch (Throwable $exception) {
                    Log::warning('marketing_automation.workflow_hook_failed', [
                        'event' => $event,
                        'error' => $exception->getMessage(),
                    ]);
                }

                if ($event === 'contacts.person.create.after' && request()->input('entity_type') === 'leads') {
                    return;
                }

                if (! in_array($event, ['lead.create.after', 'contacts.person.create.after'], true)) {
                    return;
                }

                try {
                    app(AttributionRecorder::class)->capture($entity);
                } catch (Throwable $exception) {
                    Log::warning('marketing_automation.attribution_hook_failed', [
                        'event' => $event,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
        }

        $this->app['events']->listen('webform.layout.body.after', function ($view): void {
            if (is_object($view) && method_exists($view, 'addTemplate')) {
                $view->addTemplate('marketing_automation::attribution-script');
            }
        });

        $this->app['events']->listen('admin.settings.marketing.campaigns.index.create_button.after', function ($view): void {
            if (is_object($view) && method_exists($view, 'addTemplate') && bouncer()->hasPermission('settings.automation.campaigns')) {
                $view->addTemplate('<a class="secondary-button" href="'.e(route('admin.marketing_automation.campaigns')).'">Automation tools</a>');
            }
        });

        $this->app['events']->listen('admin.settings.workflows.index.create_button.after', function ($view): void {
            if (is_object($view) && method_exists($view, 'addTemplate') && bouncer()->hasPermission('settings.automation.workflows')) {
                $view->addTemplate('<a class="secondary-button" href="'.e(route('admin.marketing_automation.workflows')).'">Advanced workflows</a>');
            }
        });
    }
}
