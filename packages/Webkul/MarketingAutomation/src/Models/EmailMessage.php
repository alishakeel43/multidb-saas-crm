<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessage extends Model
{
    protected $table = 'ma_email_messages';

    protected $fillable = [
        'enrollment_id',
        'step_id',
        'person_id',
        'lead_id',
        'email',
        'subject',
        'link_url',
        'token',
        'status',
        'scheduled_at',
        'sent_at',
        'opened_at',
        'clicked_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(EmailEnrollment::class, 'enrollment_id');
    }
}
