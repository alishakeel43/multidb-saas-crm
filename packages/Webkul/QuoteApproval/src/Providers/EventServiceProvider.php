<?php

namespace Webkul\QuoteApproval\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;
use Throwable;
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
            try {
                app(QuoteApprovalService::class)->forQuote($quote);
            } catch (Throwable $exception) {
                // Never block core quote creation if approval tables are unavailable.
                Log::warning('quote_approval.quote_create_hook_failed', [
                    'quote_id' => $quote->id ?? null,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
