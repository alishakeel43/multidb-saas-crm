<?php

namespace Webkul\Activity\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Activity\Models\Activity;
use Webkul\Activity\Models\ActivityReminder;

class ReminderService
{
    public function cancel(Activity $activity): void
    {
        ActivityReminder::query()
            ->where('activity_id', $activity->id)
            ->whereIn('status', ['pending', 'failed'])
            ->update(['status' => 'cancelled']);
    }

    public function schedule(Activity $activity): void
    {
        DB::transaction(function () use ($activity) {
            $activity = Activity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $this->cancel($activity);

            if ($activity->is_done || ! $activity->schedule_from || ! in_array($activity->type, ['task', 'call', 'meeting'], true)) {
                return;
            }

            $channels = ['in_app'];

            if (config('activity-reminders.email')) {
                $channels[] = 'email';
            }

            if (config('activity-reminders.sms.enabled') && config('activity-reminders.sms.driver') !== 'null') {
                $channels[] = 'sms';
            }

            foreach ($channels as $channel) {
                ActivityReminder::query()->create([
                    'activity_id' => $activity->id,
                    'user_id' => $activity->user_id,
                    'remind_at' => $activity->schedule_from->copy()->subMinutes(
                        $activity->reminder_minutes ?? config('activity-reminders.minutes')
                    ),
                    'channel' => $channel,
                    'status' => 'pending',
                    'idempotency_key' => (string) Str::uuid(),
                ]);
            }
        });
    }
}
