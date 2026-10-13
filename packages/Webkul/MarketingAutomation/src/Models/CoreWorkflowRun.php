<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class CoreWorkflowRun extends Model
{
    protected $table = 'ma_core_workflow_runs';

    protected $fillable = [
        'workflow_id',
        'entity_type',
        'entity_id',
        'actions',
        'status',
        'run_at',
        'finished_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'actions' => 'array',
            'run_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
