<?php

namespace Webkul\Booking\States;

class Completed extends BookingStatus
{
    public static string $name = 'completed';

    public function label(): string
    {
        return 'Completed';
    }
}
