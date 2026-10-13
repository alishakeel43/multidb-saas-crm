<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\MarketingAutomation\Contracts\EmailSequence as EmailSequenceContract;

class EmailSequence extends Model implements EmailSequenceContract
{
    protected $table = 'ma_email_sequences';

    protected $fillable = [
        'campaign_id',
        'audience_id',
        'name',
        'status',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function audience(): BelongsTo
    {
        return $this->belongsTo(Audience::class, 'audience_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(EmailStep::class, 'sequence_id')->orderBy('sort');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(EmailEnrollment::class, 'sequence_id');
    }
}
