<?php

namespace Webkul\Assignment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Assignment\Services\LeadAssignmentService;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string|callable>>
     */
    protected $listen = [];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        $this->app['events']->listen('lead.create.after', function ($lead): void {
            app(LeadAssignmentService::class)->assignIfNeeded($lead);
        });
    }
}
