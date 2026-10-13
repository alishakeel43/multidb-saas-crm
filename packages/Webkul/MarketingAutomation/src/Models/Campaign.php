<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\MarketingAutomation\Contracts\Campaign as CampaignContract;

class Campaign extends Model implements CampaignContract
{
    protected $table = 'ma_campaigns';

    protected $fillable = [
        'marketing_campaign_id',
        'name',
        'status',
        'budget',
        'spent',
        'currency',
        'audience_id',
        'source_name',
        'utm_campaign',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'spent' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function audience(): BelongsTo
    {
        return $this->belongsTo(Audience::class, 'audience_id');
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(EmailSequence::class, 'campaign_id');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(Attribution::class, 'campaign_id');
    }
}
