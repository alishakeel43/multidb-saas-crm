<?php

namespace Webkul\Notification\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Notification\Services\NotificationService;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Event::listen('assignment.lead.assigned', function ($lead, $user): void {
            try {
                if (! $user?->id) {
                    return;
                }

                app(NotificationService::class)->notify(
                    (int) $user->id,
                    'assignment',
                    'Lead assigned: '.($lead->title ?: '#'.$lead->id),
                    'You were assigned a new lead.',
                    route('admin.leads.view', $lead->id),
                    ['lead_id' => $lead->id],
                    'lead',
                    (int) $lead->id,
                );
            } catch (Throwable $exception) {
                Log::warning('notification.assignment_hook_failed', [
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
