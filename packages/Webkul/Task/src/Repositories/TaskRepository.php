<?php

namespace Webkul\Task\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\Task\Contracts\Task as TaskContract;
use Webkul\Task\Events\TaskCompleted;
use Webkul\Task\Events\TaskCreated;
use Webkul\Task\Events\TaskStatusChanged;
use Webkul\Task\Events\TaskUpdated;
use Webkul\Task\Models\Task;

class TaskRepository extends Repository
{
    /**
     * Searchable fields.
     */
    protected $fieldSearchable = [
        'title' => 'like',
        'status',
        'priority',
        'due_date',
        'assigned_to',
        'created_by',
        'entity_type',
        'entity_id',
    ];

    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return TaskContract::class;
    }

    /**
     * Create a new task with lifecycle handling.
     */
    public function create(array $data)
    {
        if (empty($data['created_by']) && auth()->guard('user')->check()) {
            $data['created_by'] = auth()->guard('user')->id();
        }

        if (empty($data['status'])) {
            $data['status'] = Task::STATUS_PENDING;
        }

        if (empty($data['priority'])) {
            $data['priority'] = Task::PRIORITY_MEDIUM;
        }

        if ($data['status'] === Task::STATUS_COMPLETED && empty($data['completed_at'])) {
            $data['completed_at'] = Carbon::now();
            $data['completed_by'] = $data['completed_by'] ?? (auth()->guard('user')->check() ? auth()->guard('user')->id() : null);
        }

        Event::dispatch('task.create.before', [$data]);

        $participants = $data['participants'] ?? null;
        unset($data['participants']);

        $task = DB::transaction(function () use ($data, $participants) {
            $task = parent::create($data);

            if ($participants !== null) {
                $this->syncParticipants($task, $participants);
            }

            return $task;
        });

        Event::dispatch('task.create.after', $task);
        event(new TaskCreated($task));

        return $task;
    }

    /**
     * Update an existing task with status transition tracking.
     */
    public function update(array $data, $id)
    {
        $task = $this->find($id);

        if (! $task) {
            return null;
        }

        $oldStatus = $task->status;
        $participants = $data['participants'] ?? null;
        unset($data['participants']);

        // Handle completion metadata when status changes
        if (isset($data['status'])) {
            if ($data['status'] === Task::STATUS_COMPLETED && $oldStatus !== Task::STATUS_COMPLETED) {
                $data['completed_at'] = Carbon::now();
                $data['completed_by'] = auth()->guard('user')->check() ? auth()->guard('user')->id() : ($data['completed_by'] ?? null);
            } elseif ($data['status'] !== Task::STATUS_COMPLETED && $oldStatus === Task::STATUS_COMPLETED) {
                $data['completed_at'] = null;
                $data['completed_by'] = null;
            }
        }

        Event::dispatch('task.update.before', [$id, $data]);

        $updated = DB::transaction(function () use ($data, $id, $participants) {
            $updated = parent::update($data, $id);

            if ($participants !== null) {
                $this->syncParticipants($updated, $participants);
            }

            return $updated;
        });

        Event::dispatch('task.update.after', $updated);
        event(new TaskUpdated($updated));

        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            event(new TaskStatusChanged($updated, $oldStatus, $data['status']));

            if ($data['status'] === Task::STATUS_COMPLETED) {
                event(new TaskCompleted($updated));
            }
        }

        return $updated;
    }

    /**
     * Replace the participant set for a task.
     *
     * @param  array{users?: array<int, int|string>, persons?: array<int, int|string>}  $participants
     */
    private function syncParticipants(Task $task, array $participants): void
    {
        $task->participants()->delete();

        foreach (array_unique(array_filter($participants['users'] ?? [])) as $userId) {
            $task->participants()->create(['user_id' => (int) $userId]);
        }

        foreach (array_unique(array_filter($participants['persons'] ?? [])) as $personId) {
            $task->participants()->create(['person_id' => (int) $personId]);
        }
    }

    /**
     * Update task status safely.
     */
    public function updateStatus(Task|int $task, string $status, ?int $userId = null): ?Task
    {
        $instance = $task instanceof Task ? $task : $this->find($task);

        if (! $instance) {
            return null;
        }

        $oldStatus = $instance->status;
        if ($oldStatus === $status) {
            return $instance;
        }

        $updateData = ['status' => $status];

        if ($status === Task::STATUS_COMPLETED) {
            $updateData['completed_at'] = Carbon::now();
            $updateData['completed_by'] = $userId ?? (auth()->guard('user')->check() ? auth()->guard('user')->id() : null);
        } elseif ($oldStatus === Task::STATUS_COMPLETED) {
            $updateData['completed_at'] = null;
            $updateData['completed_by'] = null;
        }

        $instance->update($updateData);

        event(new TaskStatusChanged($instance, $oldStatus, $status));

        if ($status === Task::STATUS_COMPLETED) {
            event(new TaskCompleted($instance));
        }

        return $instance;
    }

    /**
     * Mark a task as completed.
     */
    public function complete(Task|int $task, ?int $userId = null): ?Task
    {
        return $this->updateStatus($task, Task::STATUS_COMPLETED, $userId);
    }

    /**
     * Reopen a completed task.
     */
    public function reopen(Task|int $task): ?Task
    {
        return $this->updateStatus($task, Task::STATUS_PENDING);
    }

    /**
     * Start a task (set to In Progress).
     */
    public function start(Task|int $task): ?Task
    {
        return $this->updateStatus($task, Task::STATUS_IN_PROGRESS);
    }

    /**
     * Cancel a task.
     */
    public function cancel(Task|int $task): ?Task
    {
        return $this->updateStatus($task, Task::STATUS_CANCELLED);
    }

    /**
     * Assign a task to a user.
     */
    public function assign(Task|int $task, ?int $assignedTo): ?Task
    {
        $instance = $task instanceof Task ? $task : $this->find($task);

        if (! $instance) {
            return null;
        }

        $instance->update(['assigned_to' => $assignedTo]);

        event(new TaskUpdated($instance));

        return $instance;
    }

    /**
     * Query tasks due today.
     */
    public function getTodayTasks(?int $userId = null, int $limit = 10): Collection
    {
        $query = $this->model->newQuery()
            ->with(['assignedUser', 'creator', 'entity'])
            ->dueToday();

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query->orderBy('due_time', 'asc')
            ->orderBy('priority', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Query overdue tasks.
     */
    public function getOverdueTasks(?int $userId = null, int $limit = 10): Collection
    {
        $query = $this->model->newQuery()
            ->with(['assignedUser', 'creator', 'entity'])
            ->overdue();

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query->orderBy('due_date', 'asc')
            ->orderBy('due_time', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Query pending workload.
     */
    public function getPendingTasks(?int $userId = null, int $limit = 10): Collection
    {
        $query = $this->model->newQuery()
            ->with(['assignedUser', 'creator', 'entity'])
            ->incomplete();

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query->orderBy('due_date', 'asc')
            ->orderBy('priority', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Query recently completed tasks.
     */
    public function getRecentlyCompletedTasks(?int $userId = null, int $limit = 10): Collection
    {
        $query = $this->model->newQuery()
            ->with(['assignedUser', 'completedBy', 'entity'])
            ->completed();

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                    ->orWhere('completed_by', $userId);
            });
        }

        return $query->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Aggregate task metrics for dashboard and header stats.
     */
    public function getTaskStats(?int $userId = null): array
    {
        $baseQuery = $this->model->newQuery();

        if ($userId) {
            $baseQuery->where('assigned_to', $userId);
        }

        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->toTimeString();

        $stats = (clone $baseQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status NOT IN ('completed', 'cancelled') AND due_date = ? THEN 1 ELSE 0 END) as due_today,
                SUM(CASE WHEN status NOT IN ('completed', 'cancelled') AND due_date IS NOT NULL AND (due_date < ? OR (due_date = ? AND due_time IS NOT NULL AND due_time < ?)) THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN status NOT IN ('completed', 'cancelled') AND due_date > ? THEN 1 ELSE 0 END) as upcoming
            ", [$today, $today, $today, $currentTime, $today])
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
            'in_progress' => (int) ($stats->in_progress ?? 0),
            'completed' => (int) ($stats->completed ?? 0),
            'cancelled' => (int) ($stats->cancelled ?? 0),
            'due_today' => (int) ($stats->due_today ?? 0),
            'overdue' => (int) ($stats->overdue ?? 0),
            'upcoming' => (int) ($stats->upcoming ?? 0),
        ];
    }

    /**
     * Get tasks linked to a specific CRM entity.
     */
    public function getForEntity(string $entityType, int $entityId): Collection
    {
        return $this->model->newQuery()
            ->with(['assignedUser', 'creator', 'completedBy'])
            ->forEntity($entityType, $entityId)
            ->orderBy('status', 'asc')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get tasks grouped by status columns for the Kanban board view.
     */
    public function getKanbanStages(array $filters = [], ?array $authorizedUserIds = null): array
    {
        $stages = [
            Task::STATUS_PENDING => [
                'id' => Task::STATUS_PENDING,
                'code' => Task::STATUS_PENDING,
                'name' => trans('task::app.status.pending'),
                'color' => '#F59E0B',
                'badge_class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            ],
            Task::STATUS_IN_PROGRESS => [
                'id' => Task::STATUS_IN_PROGRESS,
                'code' => Task::STATUS_IN_PROGRESS,
                'name' => trans('task::app.status.in_progress'),
                'color' => '#3B82F6',
                'badge_class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            ],
            Task::STATUS_COMPLETED => [
                'id' => Task::STATUS_COMPLETED,
                'code' => Task::STATUS_COMPLETED,
                'name' => trans('task::app.status.completed'),
                'color' => '#10B981',
                'badge_class' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            ],
            Task::STATUS_CANCELLED => [
                'id' => Task::STATUS_CANCELLED,
                'code' => Task::STATUS_CANCELLED,
                'name' => trans('task::app.status.cancelled'),
                'color' => '#6B7280',
                'badge_class' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
            ],
        ];

        $result = [];

        foreach ($stages as $statusCode => $stageMeta) {
            $query = $this->model->newQuery()
                ->with(['assignedUser', 'creator', 'entity'])
                ->where('status', $statusCode);

            if ($authorizedUserIds !== null) {
                $query->whereIn('assigned_to', $authorizedUserIds);
            }

            if (isset($filters['user_id'])) {
                if ((string) $filters['user_id'] === '0') {
                    $query->whereNull('assigned_to');
                } elseif ($filters['user_id'] !== null && $filters['user_id'] !== '') {
                    $query->where('assigned_to', (int) $filters['user_id']);
                }
            }

            if (! empty($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }

            if (! empty($filters['search'])) {
                $search = '%'.trim($filters['search']).'%';
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            }

            if (! empty($filters['view'])) {
                match ($filters['view']) {
                    'today' => $query->dueToday(),
                    'upcoming' => $query->upcoming(),
                    'overdue' => $query->overdue(),
                    'completed' => $query->completed(),
                    'unassigned' => $query->whereNull('assigned_to'),
                    'my_tasks' => auth()->guard('user')->check() ? $query->where('assigned_to', auth()->guard('user')->id()) : null,
                    default => null,
                };
            }

            $tasks = $query->orderBy('due_date', 'asc')
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            $tasksData = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description ? Str::limit($task->description, 120) : null,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'priority_label' => trans('task::app.priority.'.$task->priority),
                    'status_label' => trans('task::app.status.'.$task->status),
                    'due_date' => $task->due_date ? Carbon::parse($task->due_date)->format('Y-m-d') : null,
                    'due_time' => $task->due_time,
                    'due_date_formatted' => $task->due_date ? Carbon::parse($task->due_date)->isoFormat('MMM D, YYYY') : null,
                    'is_overdue' => (bool) $task->is_overdue,
                    'assigned_to' => $task->assigned_to,
                    'assigned_user_name' => $task->assignedUser?->name,
                    'entity_type' => $task->entity_type,
                    'entity_id' => $task->entity_id,
                    'entity_title' => $task->entity_title,
                    'entity_url' => $task->entity_url,
                    'view_url' => route('admin.tasks.view', $task->id),
                    'edit_url' => route('admin.tasks.edit', $task->id),
                    'delete_url' => route('admin.tasks.delete', $task->id),
                    'status_url' => route('admin.tasks.status', $task->id),
                ];
            });

            $result[$statusCode] = array_merge($stageMeta, [
                'total' => $tasksData->count(),
                'tasks' => $tasksData->values()->all(),
            ]);
        }

        return $result;
    }
}
