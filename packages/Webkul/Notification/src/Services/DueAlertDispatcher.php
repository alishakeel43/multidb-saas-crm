<?php

namespace Webkul\Notification\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Webkul\Activity\Models\Activity;
use Webkul\Lead\Models\Lead;
use Webkul\Notification\Models\UserNotification;

class DueAlertDispatcher
{
    public function __construct(
        protected NotificationService $notifications,
    ) {}

    /**
     * @return array{follow_ups: int, deadlines: int, overdue: int}
     */
    public function dispatch(?Carbon $now = null): array
    {
        $now ??= now();

        return [
            'follow_ups' => $this->dispatchFollowUps($now),
            'deadlines' => $this->dispatchLeadDeadlines($now),
            'overdue' => $this->dispatchOverdueActivities($now),
        ];
    }

    private function dispatchFollowUps(Carbon $now): int
    {
        if (! Schema::hasTable('activities')) {
            return 0;
        }

        $windowEnd = (clone $now)->addHour();
        $count = 0;

        $activities = Activity::query()
            ->where('is_done', 0)
            ->whereNotNull('user_id')
            ->whereNotNull('schedule_from')
            ->where('schedule_from', '>', $now)
            ->where('schedule_from', '<=', $windowEnd)
            ->limit(200)
            ->get();

        foreach ($activities as $activity) {
            if ($this->alreadyNotified((int) $activity->user_id, 'follow_up', 'activity', (int) $activity->id)) {
                continue;
            }

            $when = $activity->schedule_from?->timezone(config('app.timezone'))->format('M j, g:i A');
            $this->notifications->notify(
                (int) $activity->user_id,
                'follow_up',
                'Upcoming follow-up: '.($activity->title ?: 'Activity'),
                "Scheduled for {$when}",
                $this->safeRoute('admin.activities.index'),
                ['activity_id' => $activity->id],
                'activity',
                (int) $activity->id,
            );
            $count++;
        }

        return $count;
    }

    private function dispatchOverdueActivities(Carbon $now): int
    {
        if (! Schema::hasTable('activities')) {
            return 0;
        }

        $count = 0;
        $activities = Activity::query()
            ->where('is_done', 0)
            ->whereNotNull('user_id')
            ->whereNotNull('schedule_to')
            ->where('schedule_to', '<', $now)
            ->where('schedule_to', '>=', (clone $now)->subDay())
            ->limit(200)
            ->get();

        foreach ($activities as $activity) {
            if ($this->alreadyNotified((int) $activity->user_id, 'deadline_overdue', 'activity', (int) $activity->id)) {
                continue;
            }

            $this->notifications->notify(
                (int) $activity->user_id,
                'deadline_overdue',
                'Overdue: '.($activity->title ?: 'Activity'),
                'This follow-up is past its deadline.',
                $this->safeRoute('admin.activities.index'),
                ['activity_id' => $activity->id],
                'activity',
                (int) $activity->id,
            );
            $count++;
        }

        return $count;
    }

    private function dispatchLeadDeadlines(Carbon $now): int
    {
        if (! Schema::hasTable('leads')) {
            return 0;
        }

        $count = 0;
        $today = $now->toDateString();
        $leads = Lead::query()
            ->where('status', 1)
            ->whereNotNull('user_id')
            ->whereNotNull('expected_close_date')
            ->whereDate('expected_close_date', '<=', $today)
            ->whereDate('expected_close_date', '>=', (clone $now)->subDay()->toDateString())
            ->limit(200)
            ->get();

        foreach ($leads as $lead) {
            if ($this->alreadyNotified((int) $lead->user_id, 'lead_deadline', 'lead', (int) $lead->id)) {
                continue;
            }

            $this->notifications->notify(
                (int) $lead->user_id,
                'lead_deadline',
                'Lead close date due: '.($lead->title ?: '#'.$lead->id),
                'Expected close date is '.$lead->expected_close_date,
                $this->safeRoute('admin.leads.view', [$lead->id]),
                ['lead_id' => $lead->id],
                'lead',
                (int) $lead->id,
            );
            $count++;
        }

        return $count;
    }

    private function alreadyNotified(int $userId, string $type, string $entityType, int $entityId): bool
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('created_at', '>=', now()->subDay())
            ->exists();
    }

    private function safeRoute(string $name, array $params = []): ?string
    {
        try {
            return route($name, $params);
        } catch (\Throwable) {
            return null;
        }
    }
}
