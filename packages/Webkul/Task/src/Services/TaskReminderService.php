<?php

namespace Webkul\Task\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Webkul\Notification\Services\NotificationService;
use Webkul\Task\Models\Task;

class TaskReminderService
{
    public function __construct(
        protected NotificationService $notifications,
    ) {}

    public function dispatchDue(Carbon $now): int
    {
        if (! Schema::hasTable('tasks')) {
            return 0;
        }

        $today = $now->copy()->startOfDay()->toDateString();

        $tasks = Task::query()
            ->incomplete()
            ->whereNotNull('due_date')
            ->where(function ($query) use ($today) {
                $query->whereDate('due_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('reminder_sent_at')
                    ->orWhereDate('reminder_sent_at', '<', $today);
            })
            ->get();

        $sent = 0;
        $canNotify = Schema::hasTable('user_notifications');

        foreach ($tasks as $task) {
            $userId = $task->assigned_to ?: $task->created_by;

            if ($canNotify && $userId) {
                $overdue = $task->due_date->toDateString() < $today
                    || ($task->due_time && $task->due_date->toDateString() === $today && $task->due_time < $now->toTimeString());

                $this->notifications->notify(
                    (int) $userId,
                    $overdue ? 'task_overdue' : 'task_due',
                    $overdue
                        ? trans('task::app.reminders.overdue', ['title' => $task->title])
                        : trans('task::app.reminders.due', ['title' => $task->title]),
                    $task->description,
                    route('admin.tasks.view', $task->id),
                    ['status' => $task->status, 'priority' => $task->priority],
                    'tasks',
                    $task->id,
                );
            }

            $task->forceFill(['reminder_sent_at' => $now])->save();
            $sent++;
        }

        return $sent;
    }
}
