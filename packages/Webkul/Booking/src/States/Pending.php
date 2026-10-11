<?php

namespace Webkul\Booking\States;

class Pending extends BookingStatus
{
    public static string $name = 'pending';

    public function label(): string
    {
        return 'Pending';
    }
}
