<?php

namespace Webkul\Booking\States;

class NoShow extends BookingStatus
{
    public static string $name = 'no_show';

    public function label(): string
    {
        return 'No show';
    }
}
