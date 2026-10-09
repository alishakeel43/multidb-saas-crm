<?php

namespace Webkul\Activity\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Webkul\Activity\Contracts\SmsSender;
use Webkul\Activity\Models\Activity;
use Webkul\Activity\Models\ActivityReminder;
use Webkul\Activity\Notifications\ActivityDue;
use Webkul\User\Models\User;

class SendActivityReminder implements NotTenantAware, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $reminderId,
        public int $tenantId,
    ) {}

    public function handle(SmsSender $sms): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (! $tenant || $tenant->status !== 'active') {
            return;
        }

        $tenant->execute(function () use ($sms) {
            $reminder = ActivityReminder::query()->find($this->reminderId);

            if (! $reminder || $reminder->status !== 'pending' || $reminder->remind_at->isFuture()) {
                return;
            }

            if ($reminder->channel !== 'in_app') {
                if (! ActivityReminder::query()->whereKey($reminder->id)->where('status', 'pending')->update([
                    'status' => 'failed',
                    'error' => 'Delivery outcome unknown; automatic retry suppressed.',
                ])) {
                    return;
                }
            }

            DB::transaction(function () use ($reminder, $sms) {
                $activity = Activity::query()->whereKey($reminder->activity_id)->lockForUpdate()->first();
                $row = ActivityReminder::query()->whereKey($reminder->id)->lockForUpdate()->first();

                if (! $row || ($row->channel === 'in_app' ? $row->status !== 'pending' : $row->status !== 'failed')) {
                    return;
                }

                $user = User::query()->whereKey($row->user_id)->where('status', 1)->first();
                $matchesSchedule = $activity?->schedule_from
                    && $activity->schedule_from->copy()->subMinutes(
                        $activity->reminder_minutes ?? config('activity-reminders.minutes')
                    )->equalTo($row->remind_at);

                if (
                    ! $activity
                    || ! $matchesSchedule
                    || ! in_array($activity->type, ['task', 'call', 'meeting'], true)
                    || $activity->is_done
                    || (int) $activity->user_id !== (int) $row->user_id
                    || ! $user
                    || ($row->channel === 'sms' && ! config('activity-reminders.sms.enabled'))
                    || ($row->channel === 'email' && ! config('activity-reminders.email'))
                ) {
                    $row->update(['status' => 'cancelled', 'error' => null]);

                    return;
                }

                try {
                    if ($row->channel === 'sms') {
                        $recipients = config('activity-reminders.sms.recipients', []);
                        $to = $recipients[$row->user_id] ?? null;

                        if (! is_string($to) || ! preg_match('/^\+[1-9]\d{7,14}$/', $to)) {
                            throw new \RuntimeException('Invalid SMS recipient.');
                        }

                        $sms->send($to, $activity->title.' — '.$activity->schedule_from->toIso8601String(), $row->idempotency_key);
                    } else {
                        $user->notifyNow(new ActivityDue($activity, $row->channel, $row->idempotency_key));
                    }

                    $row->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                } catch (\Throwable) {
                    $row->update(['status' => 'failed', 'error' => 'Delivery failed or its outcome is unknown.']);
                }
            });
        });
    }
}
