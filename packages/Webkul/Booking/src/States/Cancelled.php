<?php

namespace Webkul\Booking\States;

class Cancelled extends BookingStatus
{
    public static string $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }
}
