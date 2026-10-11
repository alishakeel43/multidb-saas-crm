<?php

namespace Webkul\Booking\Repositories;

use Webkul\Core\Eloquent\Repository;

class BookingServiceRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return 'Webkul\Booking\Contracts\BookingService';
    }
}
