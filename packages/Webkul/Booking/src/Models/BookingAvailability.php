<?php

namespace Webkul\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Booking\Contracts\BookingAvailability as BookingAvailabilityContract;
use Webkul\User\Models\UserProxy;

class BookingAvailability extends Model implements BookingAvailabilityContract
{
    protected $fillable = [
        'user_id',
        'weekday',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weekday' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }
}
