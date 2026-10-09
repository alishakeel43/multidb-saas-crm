<?php

namespace Webkul\Activity\Observers;

use Webkul\Activity\Models\Activity;
use Webkul\Activity\Services\ReminderService;

class ActivityReminderObserver
{
    public function created(Activity $activity): void
    {
        app(ReminderService::class)->schedule($activity);
    }

    public function updated(Activity $activity): void
    {
        if ($activity->wasChanged(['schedule_from', 'is_done', 'user_id', 'type', 'reminder_minutes'])) {
            app(ReminderService::class)->schedule($activity);
        }
    }

    public function deleting(Activity $activity): void
    {
        app(ReminderService::class)->cancel($activity);
    }
}
