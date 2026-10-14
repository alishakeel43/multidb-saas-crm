<?php

namespace Webkul\Task\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Contact\Models\OrganizationProxy;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\Quote\Models\QuoteProxy;
use Webkul\Task\Contracts\Task as TaskContract;
use Webkul\User\Models\UserProxy;

class Task extends Model implements TaskContract
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    /**
     * Short entity keys stored on the row. Isolation is the tenant database,
     * so these keys never carry a tenant id.
     *
     * @var array<string, class-string>
     */
    public const ENTITIES = [
        'leads' => LeadProxy::class,
        'persons' => PersonProxy::class,
        'organizations' => OrganizationProxy::class,
        'quotes' => QuoteProxy::class,
    ];

    protected $table = 'tasks';

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'due_time',
        'assigned_to',
        'created_by',
        'completed_by',
        'completed_at',
        'entity_type',
        'entity_id',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'completed_by');
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereNotIn('tasks.status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('tasks.status', self::STATUS_COMPLETED);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->toTimeString();

        return $query->incomplete()
            ->whereNotNull('tasks.due_date')
            ->where(function (Builder $inner) use ($today, $currentTime) {
                $inner->whereDate('tasks.due_date', '<', $today)
                    ->orWhere(function (Builder $sameDay) use ($today, $currentTime) {
                        $sameDay->whereDate('tasks.due_date', $today)
                            ->whereNotNull('tasks.due_time')
                            ->where('tasks.due_time', '<', $currentTime);
                    });
            });
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->incomplete()->whereDate('tasks.due_date', Carbon::today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->incomplete()->whereDate('tasks.due_date', '>', Carbon::today());
    }

    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true) || ! $this->due_date) {
            return false;
        }

        $due = Carbon::parse($this->due_date->format('Y-m-d').' '.($this->due_time ?: '23:59:59'));

        return Carbon::now()->greaterThan($due);
    }

    /**
     * @return array{type: string, id: int, title: string, url: ?string}|null
     */
    public function linkedEntity(): ?array
    {
        if (! $this->entity_type || ! $this->entity_id || ! isset(self::ENTITIES[$this->entity_type])) {
            return null;
        }

        $proxy = self::ENTITIES[$this->entity_type];
        $modelClass = $proxy::modelClass();
        $record = $modelClass::query()->find($this->entity_id);

        if (! $record) {
            return null;
        }

        $title = match ($this->entity_type) {
            'leads' => $record->title ?? ('#'.$record->id),
            'quotes' => $record->subject ?? ('#'.$record->id),
            default => $record->name ?? ('#'.$record->id),
        };

        $url = match ($this->entity_type) {
            'leads' => route('admin.leads.view', $record->id),
            'persons' => route('admin.contacts.persons.view', $record->id),
            'organizations' => route('admin.contacts.organizations.edit', $record->id),
            'quotes' => route('admin.quotes.edit', $record->id),
            default => null,
        };

        return [
            'type' => $this->entity_type,
            'id' => (int) $record->id,
            'title' => (string) $title,
            'url' => $url,
        ];
    }

    /**
     * @return array<int, int>
     */
    public function ownerIds(): array
    {
        return array_values(array_filter([
            $this->assigned_to,
            $this->created_by,
        ]));
    }
}
