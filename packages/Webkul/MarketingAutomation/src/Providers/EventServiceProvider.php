<?php

namespace Webkul\MarketingAutomation\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\MarketingAutomation\Services\AttributionRecorder;
use Webkul\MarketingAutomation\Services\CampaignExtension;
use Webkul\MarketingAutomation\Services\CoreWorkflowActions;
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
                    app(CoreWorkflowActions::class)->handleOtherwise($event, $entity);
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
            $this->addTemplate($view, 'marketing_automation::attribution-script');
        });

        foreach ([
            'admin.settings.workflows.create.action.after' => 'marketing_automation::extend.workflow-hint',
            'admin.settings.workflows.edit.action.after' => 'marketing_automation::extend.workflow-hint',
            'admin.settings.marketing.campaigns.index.form_controls.modal.content.controls.after' => 'marketing_automation::extend.campaign-fields',
            'admin.settings.marketing.campaigns.index.create_button.after' => 'marketing_automation::extend.campaign-tools',
            'admin.settings.webform.edit.right.after' => 'marketing_automation::extend.webform-attribution',
            'admin.settings.webform.create.right.after' => 'marketing_automation::extend.webform-attribution',
            'lead_scoring.settings.after' => 'marketing_automation::extend.engagement',
        ] as $event => $template) {
            $this->app['events']->listen($event, function ($view) use ($template): void {
                $this->addTemplate($view, $template);
            });
        }

        $this->app['events']->listen('settings.marketing.campaigns.create.after', function ($campaign): void {
            if (is_object($campaign)) {
                app(CampaignExtension::class)->sync($campaign);
            }
        });

        $this->app['events']->listen('settings.marketing.campaigns.update.after', function ($campaign): void {
            if (is_object($campaign)) {
                app(CampaignExtension::class)->sync($campaign);
            }
        });

        foreach (['settings.marketing.campaigns.delete.after'] as $event) {
            $this->app['events']->listen($event, function ($campaign): void {
                app(CampaignExtension::class)->forget($campaign);
            });
        }
    }

    private function addTemplate(mixed $view, string $template): void
    {
        if (is_object($view) && method_exists($view, 'addTemplate')) {
            $view->addTemplate($template);
        }
    }
}
