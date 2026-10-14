<?php

namespace Webkul\Task\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Webkul\Task\Events\TaskCompleted;
use Webkul\Task\Events\TaskCreated;
use Webkul\Task\Events\TaskStatusChanged;
use Webkul\Task\Events\TaskUpdated;
use Webkul\Task\Models\Task;
use Webkul\Task\Repositories\TaskRepository;
use Webkul\Task\Repositories\TaskStageRepository;

class TaskWorkflow
{
    public function __construct(
        protected TaskRepository $tasks,
        protected TaskStageRepository $stages,
    ) {}

    public function create(array $data): Task
    {
        $this->stages->ensureDefaults();

        $payload = $this->normalize($data);
        $payload['created_by'] = $payload['created_by'] ?? $this->actorId();
        $payload['status'] = $payload['status'] ?? $this->defaultStatus();
        $payload['priority'] = $payload['priority'] ?? Task::PRIORITY_MEDIUM;

        $this->guardStage($payload['status']);
        $this->guardPriority($payload['priority']);
        $this->applyCompletion($payload, null);

        $task = $this->tasks->create($payload);
        event(new TaskCreated($task));

        if ($task->status === Task::STATUS_COMPLETED) {
            event(new TaskCompleted($task));
        }

        return $task;
    }

    public function update(Task $task, array $data): Task
    {
        $previous = $task->status;
        $payload = $this->normalize($data);

        if (isset($payload['status'])) {
            $this->guardStage($payload['status']);
        }

        if (isset($payload['priority'])) {
            $this->guardPriority($payload['priority']);
        }

        $this->applyCompletion($payload, $previous);

        $task = $this->tasks->update($payload, $task->id);
        event(new TaskUpdated($task));

        if (isset($payload['status']) && $payload['status'] !== $previous) {
            event(new TaskStatusChanged($task, $previous, $payload['status']));

            if ($payload['status'] === Task::STATUS_COMPLETED) {
                event(new TaskCompleted($task));
            }
        }

        return $task;
    }

    public function changeStatus(Task $task, string $status): Task
    {
        return $this->update($task, ['status' => $status]);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function massStatus(array $ids, string $status): int
    {
        $changed = 0;

        foreach ($this->tasks->findWhereIn('id', $ids) as $task) {
            if ($task->status === $status) {
                continue;
            }

            $this->changeStatus($task, $status);
            $changed++;
        }

        return $changed;
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function massDelete(array $ids): int
    {
        $deleted = 0;

        foreach ($ids as $id) {
            $deleted += $this->tasks->delete($id) ? 1 : 0;
        }

        return $deleted;
    }

    public function moveStage(string $from, string $to): int
    {
        $this->guardStage($to);

        if ($from === $to) {
            throw ValidationException::withMessages([
                'reassign_to' => trans('task::app.messages.same-stage'),
            ]);
        }

        $query = Task::query()->where('status', $from);
        $count = (clone $query)->count();

        if ($to === Task::STATUS_COMPLETED) {
            $query->update([
                'status' => $to,
                'completed_at' => Carbon::now(),
                'completed_by' => $this->actorId(),
            ]);
        } elseif ($from === Task::STATUS_COMPLETED) {
            $query->update([
                'status' => $to,
                'completed_at' => null,
                'completed_by' => null,
            ]);
        } else {
            $query->update(['status' => $to]);
        }

        return $count;
    }

    /**
     * @return array<string, int>
     */
    public function stats(?int $userId = null): array
    {
        $this->stages->ensureDefaults();

        $base = Task::query();

        if ($userId) {
            $base->where('assigned_to', $userId);
        }

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', Task::STATUS_PENDING)->count(),
            'in_progress' => (clone $base)->where('status', Task::STATUS_IN_PROGRESS)->count(),
            'completed' => (clone $base)->where('status', Task::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $base)->where('status', Task::STATUS_CANCELLED)->count(),
            'due_today' => (clone $base)->dueToday()->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'upcoming' => (clone $base)->upcoming()->count(),
        ];
    }

    /**
     * @return Collection<int, Task>
     */
    public function forEntity(string $entityType, int $entityId): Collection
    {
        $this->guardEntity($entityType, $entityId);

        return Task::query()
            ->with(['assignedUser'])
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function kanban(array $filters = []): array
    {
        $stages = $this->stages->ensureDefaults();
        $columns = [];

        foreach ($stages as $stage) {
            $query = Task::query()
                ->with(['assignedUser'])
                ->where('status', $stage->code);

            $this->applyVisibility($query);
            $this->applyFilters($query, $filters);

            $tasks = $query->orderBy('due_date')->orderByDesc('id')->limit(100)->get();

            $columns[] = [
                'code' => $stage->code,
                'name' => $stage->name,
                'color' => $stage->color,
                'total' => $tasks->count(),
                'tasks' => $tasks,
            ];
        }

        return $columns;
    }

    public function defaultStatus(): string
    {
        $stage = $this->stages->ensureDefaults()->firstWhere('is_default', true);

        return $stage->code ?? Task::STATUS_PENDING;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $allowed = [
            'title',
            'description',
            'status',
            'priority',
            'due_date',
            'due_time',
            'assigned_to',
            'created_by',
            'entity_type',
            'entity_id',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['assigned_to', 'created_by', 'entity_id'] as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] === '') {
                $payload[$key] = null;
            }
        }

        if (array_key_exists('due_date', $payload) && $payload['due_date'] === '') {
            $payload['due_date'] = null;
        }

        if (array_key_exists('due_time', $payload) && $payload['due_time'] === '') {
            $payload['due_time'] = null;
        }

        if (array_key_exists('entity_type', $payload) && $payload['entity_type'] === '') {
            $payload['entity_type'] = null;
        }

        if (
            (array_key_exists('entity_type', $payload) || array_key_exists('entity_id', $payload))
            && (empty($payload['entity_type']) || empty($payload['entity_id']))
        ) {
            $payload['entity_type'] = null;
            $payload['entity_id'] = null;
        } elseif (! empty($payload['entity_type']) && ! empty($payload['entity_id'])) {
            $this->guardEntity((string) $payload['entity_type'], (int) $payload['entity_id']);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyCompletion(array &$payload, ?string $previous): void
    {
        if (! isset($payload['status'])) {
            return;
        }

        if ($payload['status'] === Task::STATUS_COMPLETED && $previous !== Task::STATUS_COMPLETED) {
            $payload['completed_at'] = Carbon::now();
            $payload['completed_by'] = $this->actorId();
        }

        if ($payload['status'] !== Task::STATUS_COMPLETED && $previous === Task::STATUS_COMPLETED) {
            $payload['completed_at'] = null;
            $payload['completed_by'] = null;
        }
    }

    private function guardStage(string $status): void
    {
        $exists = $this->stages->ensureDefaults()->contains(fn ($stage) => $stage->code === $status);

        if (! $exists) {
            throw ValidationException::withMessages([
                'status' => trans('task::app.messages.unknown-stage'),
            ]);
        }
    }

    private function guardPriority(string $priority): void
    {
        if (! in_array($priority, Task::PRIORITIES, true)) {
            throw ValidationException::withMessages([
                'priority' => trans('task::app.messages.unknown-priority'),
            ]);
        }
    }

    private function guardEntity(string $type, ?int $id): void
    {
        if ($type === '' && ! $id) {
            return;
        }

        if (! isset(Task::ENTITIES[$type]) || ! $id) {
            throw ValidationException::withMessages([
                'entity_type' => trans('task::app.messages.unknown-entity'),
            ]);
        }

        $proxy = Task::ENTITIES[$type];
        $modelClass = $proxy::modelClass();

        if (! $modelClass::query()->whereKey($id)->exists()) {
            throw ValidationException::withMessages([
                'entity_id' => trans('task::app.messages.missing-entity'),
            ]);
        }
    }

    /**
     * @param  Builder<Task>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['assigned_to'])) {
            if ((string) $filters['assigned_to'] === '0') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', (int) $filters['assigned_to']);
            }
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim((string) $filters['search']).'%';
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        match ($filters['bucket'] ?? null) {
            'today' => $query->dueToday(),
            'upcoming' => $query->upcoming(),
            'overdue' => $query->overdue(),
            'mine' => $query->where('assigned_to', $this->actorId()),
            default => null,
        };
    }

    /**
     * @param  Builder<Task>  $query
     */
    private function applyVisibility($query): void
    {
        $userIds = bouncer()->getAuthorizedUserIds();

        if ($userIds === null) {
            return;
        }

        $query->where(function ($inner) use ($userIds) {
            $inner->whereIn('assigned_to', $userIds)
                ->orWhereIn('created_by', $userIds);
        });
    }

    private function actorId(): ?int
    {
        $id = auth()->guard('user')->id();

        return $id ? (int) $id : null;
    }
}
