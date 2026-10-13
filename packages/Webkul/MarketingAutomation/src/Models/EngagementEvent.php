<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class EngagementEvent extends Model
{
    protected $table = 'ma_engagement_events';

    protected $fillable = [
        'lead_id',
        'event_type',
        'source_key',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }
}
