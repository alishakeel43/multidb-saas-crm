<?php

namespace Webkul\LeadScoring\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\LeadScoring\Contracts\LeadScoringRule as LeadScoringRuleContract;

class LeadScoringRule extends Model implements LeadScoringRuleContract
{
    protected $fillable = [
        'name',
        'field',
        'operator',
        'value',
        'points',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'points' => 'integer',
            'priority' => 'integer',
        ];
    }
}
