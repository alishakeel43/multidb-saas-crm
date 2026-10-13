<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailEnrollment extends Model
{
    protected $table = 'ma_email_enrollments';

    protected $fillable = [
        'sequence_id',
        'person_id',
        'status',
        'current_step',
        'attempts',
        'next_run_at',
        'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'current_step' => 'integer',
            'attempts' => 'integer',
            'next_run_at' => 'datetime',
        ];
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailSequence::class, 'sequence_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'enrollment_id');
    }
}
