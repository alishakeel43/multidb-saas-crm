<?php

namespace Webkul\Task\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Contact\Models\OrganizationProxy;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\Quote\Models\QuoteProxy;
use Webkul\Task\Contracts\Task as TaskContract;
use Webkul\User\Models\UserProxy;

class Task extends Model implements TaskContract
{
    /**
     * Supported statuses.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Supported priorities.
     */
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    /**
     * Whitelist of CRM entities supported for polymorphic relationship.
     */
    public const ALLOWED_ENTITIES = [
        'leads' => LeadProxy::class,
        'persons' => PersonProxy::class,
        'organizations' => OrganizationProxy::class,
        'quotes' => QuoteProxy::class,
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'due_time',
        'schedule_from',
        'schedule_to',
        'location',
        'reminder_minutes',
        'assigned_to',
        'created_by',
        'completed_by',
        'completed_at',
        'entity_type',
        'entity_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'schedule_from' => 'datetime',
        'schedule_to' => 'datetime',
        'reminder_minutes' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationships that should be eager loaded by default where appropriate.
     */
    protected $with = [];

    /**
     * Get the assigned user.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'assigned_to');
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'created_by');
    }

    /**
     * Get the user who completed the task.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'completed_by');
    }

    /**
     * Get the polymorphic CRM entity associated with the task.
     */
    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TaskParticipant::class)->with(['user', 'person']);
    }

    /**
     * Scope: tasks that are incomplete (not completed and not cancelled).
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereNotIn('tasks.status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope: tasks that are completed.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('tasks.status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: tasks that are pending.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('tasks.status', self::STATUS_PENDING);
    }

    /**
     * Scope: tasks that are in progress.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('tasks.status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope: tasks that are cancelled.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('tasks.status', self::STATUS_CANCELLED);
    }

    /**
     * Scope: overdue tasks.
     * Centralized overdue definition:
     * - Must be incomplete (not completed, not cancelled)
     * - due_date < today OR (due_date == today AND due_time IS NOT NULL AND due_time < current time)
     */
    public function scopeOverdue(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->toTimeString();

        return $query->incomplete()
            ->whereNotNull('tasks.due_date')
            ->where(function (Builder $q) use ($today, $currentTime) {
                $q->where('tasks.due_date', '<', $today)
                    ->orWhere(function (Builder $sub) use ($today, $currentTime) {
                        $sub->where('tasks.due_date', '=', $today)
                            ->whereNotNull('tasks.due_time')
                            ->where('tasks.due_time', '<', $currentTime);
                    });
            });
    }

    /**
     * Scope: tasks due today.
     */
    public function scopeDueToday(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->incomplete()
            ->whereDate('tasks.due_date', $today);
    }

    /**
     * Scope: upcoming tasks (due after today).
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->incomplete()
            ->whereDate('tasks.due_date', '>', $today);
    }

    /**
     * Scope: tasks assigned to a specific user.
     */
    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('tasks.assigned_to', $userId);
    }

    /**
     * Scope: tasks assigned to the authenticated user.
     */
    public function scopeMyTasks(Builder $query): Builder
    {
        return $query->where('tasks.assigned_to', auth()->id());
    }

    /**
     * Scope: tasks related to a specific CRM entity.
     */
    public function scopeForEntity(Builder $query, string $entityType, int $entityId): Builder
    {
        return $query->where('tasks.entity_type', $entityType)
            ->where('tasks.entity_id', $entityId);
    }

    /**
     * Check if the task is currently overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            return false;
        }

        if (! $this->due_date) {
            return false;
        }

        $now = Carbon::now();
        $dueDate = Carbon::parse($this->due_date);

        if ($this->due_time) {
            $dueDateTime = Carbon::parse($dueDate->format('Y-m-d').' '.$this->due_time);

            return $now->greaterThan($dueDateTime);
        }

        return $now->startOfDay()->greaterThan($dueDate->startOfDay());
    }

    /**
     * Get combined due datetime Carbon instance, if available.
     */
    public function getDueDatetimeAttribute(): ?Carbon
    {
        if (! $this->due_date) {
            return null;
        }

        $dateString = Carbon::parse($this->due_date)->format('Y-m-d');

        if ($this->due_time) {
            return Carbon::parse($dateString.' '.$this->due_time);
        }

        return Carbon::parse($dateString)->endOfDay();
    }

    /**
     * Get a human-readable label/title for the related CRM entity safely without crashing on missing relation.
     */
    public function getEntityTitleAttribute(): ?string
    {
        if (! $this->entity_type || ! $this->entity_id) {
            return null;
        }

        $entity = $this->entity;

        if (! $entity) {
            return null;
        }

        return match ($this->entity_type) {
            'leads' => $entity->title ?? ('#'.$entity->id),
            'persons' => $entity->name ?? ('#'.$entity->id),
            'organizations' => $entity->name ?? ('#'.$entity->id),
            'quotes' => $entity->subject ?? ('#'.$entity->id),
            default => method_exists($entity, 'getTitle') ? $entity->getTitle() : ($entity->name ?? $entity->title ?? ('#'.$entity->id)),
        };
    }

    /**
     * Get the route to the related entity, if available.
     */
    public function getEntityUrlAttribute(): ?string
    {
        if (! $this->entity_type || ! $this->entity_id) {
            return null;
        }

        return match ($this->entity_type) {
            'leads' => route('admin.leads.view', $this->entity_id),
            'persons' => route('admin.contacts.persons.view', $this->entity_id),
            'organizations' => route('admin.contacts.organizations.edit', $this->entity_id),
            'quotes' => route('admin.quotes.edit', $this->entity_id),
            default => null,
        };
    }
}
