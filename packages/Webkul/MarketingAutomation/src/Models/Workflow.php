<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\MarketingAutomation\Contracts\Workflow as WorkflowContract;

class Workflow extends Model implements WorkflowContract
{
    protected $table = 'ma_workflows';

    protected $fillable = [
        'name',
        'entity_type',
        'event',
        'condition_type',
        'conditions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class, 'workflow_id')->orderBy('sort');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class, 'workflow_id');
    }
}
