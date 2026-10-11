<?php

namespace Webkul\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;
use Webkul\Activity\Models\ActivityProxy;
use Webkul\Booking\Contracts\Booking as BookingContract;
use Webkul\Booking\States\BookingStatus;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\User\Models\UserProxy;

class Booking extends Model implements BookingContract
{
    use HasStates;

    protected $fillable = [
        'booking_service_id',
        'user_id',
        'person_id',
        'lead_id',
        'activity_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'status' => BookingStatus::class,
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingServiceProxy::modelClass(), 'booking_service_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ActivityProxy::modelClass());
    }
}
