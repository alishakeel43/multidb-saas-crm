<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailStep extends Model
{
    protected $table = 'ma_email_steps';

    protected $fillable = [
        'sequence_id',
        'sort',
        'delay_minutes',
        'subject',
        'body',
        'link_url',
        'link_label',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'delay_minutes' => 'integer',
        ];
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailSequence::class, 'sequence_id');
    }
}
