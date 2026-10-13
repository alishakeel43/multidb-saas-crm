<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    protected $table = 'ma_workflow_actions';

    protected $fillable = [
        'workflow_id',
        'branch',
        'sort',
        'type',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'sort' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}
