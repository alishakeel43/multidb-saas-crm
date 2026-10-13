<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\MarketingAutomation\Contracts\EngagementRule as EngagementRuleContract;

class EngagementRule extends Model implements EngagementRuleContract
{
    protected $table = 'ma_engagement_rules';

    protected $fillable = [
        'name',
        'event_type',
        'points',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
