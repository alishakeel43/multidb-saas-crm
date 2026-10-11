<?php

namespace Webkul\QuoteApproval\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\QuoteApproval\Services\QuoteApprovalService;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        $this->app['events']->listen('quote.create.after', function ($quote): void {
            app(QuoteApprovalService::class)->forQuote($quote);
        });
    }
}
