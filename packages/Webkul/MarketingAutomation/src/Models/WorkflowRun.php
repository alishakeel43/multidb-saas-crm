<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRun extends Model
{
    protected $table = 'ma_workflow_runs';

    protected $fillable = [
        'workflow_id',
        'branch',
        'next_action_index',
        'context',
        'status',
        'run_at',
        'finished_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'run_at' => 'datetime',
            'finished_at' => 'datetime',
            'next_action_index' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}
