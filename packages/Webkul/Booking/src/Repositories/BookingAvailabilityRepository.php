<?php

namespace Webkul\Booking\Repositories;

use Webkul\Core\Eloquent\Repository;

class BookingAvailabilityRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return 'Webkul\Booking\Contracts\BookingAvailability';
    }
}
