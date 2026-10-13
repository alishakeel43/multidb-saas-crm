<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MarketingAutomation\Contracts\Attribution as AttributionContract;

class Attribution extends Model implements AttributionContract
{
    protected $table = 'ma_attributions';

    protected $fillable = [
        'lead_id',
        'person_id',
        'campaign_id',
        'web_form_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'referrer',
        'landing_url',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }
}
