<?php

namespace Webkul\LeadScoring\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\LeadScoring\Services\LeadScoringService;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        foreach (['lead.create.after', 'lead.update.after'] as $event) {
            $this->app['events']->listen($event, function ($lead) use ($event): void {
                try {
                    app(LeadScoringService::class)->score($lead);
                } catch (Throwable $exception) {
                    Log::warning('lead_scoring.hook_failed', [
                        'event' => $event,
                        'lead_id' => $lead->id ?? null,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
        }
    }
}
