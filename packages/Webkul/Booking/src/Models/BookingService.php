<?php

namespace Webkul\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Booking\Contracts\BookingService as BookingServiceContract;

class BookingService extends Model implements BookingServiceContract
{
    protected $fillable = [
        'name',
        'duration_minutes',
        'buffer_minutes',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingProxy::modelClass());
    }
}
