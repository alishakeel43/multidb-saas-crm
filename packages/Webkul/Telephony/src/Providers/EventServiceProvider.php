<?php

namespace Webkul\Telephony\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        Event::listen('admin.contact.persons.view.actions.after', function ($viewRenderManager): void {
            try {
                if (! is_object($viewRenderManager) || ! method_exists($viewRenderManager, 'addTemplate')) {
                    return;
                }

                if ($viewRenderManager->getParam('person')) {
                    $viewRenderManager->addTemplate('telephony::persons.click-to-call');
                }
            } catch (Throwable $exception) {
                Log::warning('telephony.person_view_actions_hook_failed', [
                    'error' => $exception->getMessage(),
                ]);
            }
        });

        Event::listen('admin.contact.persons.view.right.before', function ($viewRenderManager): void {
            try {
                if (! is_object($viewRenderManager) || ! method_exists($viewRenderManager, 'addTemplate')) {
                    return;
                }

                if ($viewRenderManager->getParam('person')) {
                    $viewRenderManager->addTemplate('telephony::persons.call-history');
                }
            } catch (Throwable $exception) {
                Log::warning('telephony.person_view_right_hook_failed', [
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
