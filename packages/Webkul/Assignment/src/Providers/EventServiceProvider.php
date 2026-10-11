<?php

namespace Webkul\Assignment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Assignment\Services\LeadAssignmentService;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        $this->app['events']->listen('lead.create.after', function ($lead): void {
            try {
                app(LeadAssignmentService::class)->assignIfNeeded($lead);
            } catch (Throwable $exception) {
                // Never block core lead creation if assignment tables/rules are unavailable.
                Log::warning('assignment.lead_create_hook_failed', [
                    'lead_id' => $lead->id ?? null,
                    'error' => $exception->getMessage(),
                ]);
            }
        });

        Event::listen('admin.leads.create.form.after', function ($viewRenderManager) {
            if (is_object($viewRenderManager) && method_exists($viewRenderManager, 'addTemplate')) {
                $viewRenderManager->addTemplate('assignment::leads.duplicate-check');
            }
        });
    }
}
