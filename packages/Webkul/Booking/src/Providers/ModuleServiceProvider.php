<?php

namespace Webkul\Booking\Providers;

use Webkul\Booking\Models\Booking;
use Webkul\Booking\Models\BookingAvailability;
use Webkul\Booking\Models\BookingService;
use Webkul\Core\Providers\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Booking::class,
        BookingService::class,
        BookingAvailability::class,
    ];
}
