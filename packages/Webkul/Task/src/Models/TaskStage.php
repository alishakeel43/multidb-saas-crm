<?php

namespace Webkul\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Task\Contracts\TaskStage as TaskStageContract;

class TaskStage extends Model implements TaskStageContract
{
    /**
     * Default workflow stages for any new workspace.
     */
    public const DEFAULT_STAGES = [
        [
            'code' => 'pending',
            'name' => 'Pending',
            'color' => '#F59E0B',
            'sort_order' => 1,
            'is_default' => true,
        ],
        [
            'code' => 'in_progress',
            'name' => 'In Progress',
            'color' => '#3B82F6',
            'sort_order' => 2,
            'is_default' => true,
        ],
        [
            'code' => 'completed',
            'name' => 'Completed',
            'color' => '#10B981',
            'sort_order' => 3,
            'is_default' => true,
        ],
        [
            'code' => 'cancelled',
            'name' => 'Cancelled',
            'color' => '#6B7280',
            'sort_order' => 4,
            'is_default' => true,
        ],
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_stages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'color',
        'sort_order',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get tasks currently assigned to this stage status code.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TaskProxy::modelClass(), 'status', 'code');
    }
}
