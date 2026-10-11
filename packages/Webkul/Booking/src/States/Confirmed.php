<?php

namespace Webkul\Booking\States;

class Confirmed extends BookingStatus
{
    public static string $name = 'confirmed';

    public function label(): string
    {
        return 'Confirmed';
    }
}
