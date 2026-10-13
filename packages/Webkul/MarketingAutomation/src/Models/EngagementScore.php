<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;

class EngagementScore extends Model
{
    protected $table = 'ma_engagement_scores';

    protected $fillable = [
        'lead_id',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }
}
