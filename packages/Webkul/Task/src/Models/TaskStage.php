<?php

namespace Webkul\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Task\Contracts\TaskStage as TaskStageContract;

class TaskStage extends Model implements TaskStageContract
{
    protected $table = 'task_stages';

    protected $fillable = [
        'code',
        'name',
        'color',
        'sort_order',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
