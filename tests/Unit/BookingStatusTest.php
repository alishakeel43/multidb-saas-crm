<?php

use Tests\TestCase;
use Webkul\Booking\States\BookingStatus;
use Webkul\Booking\States\Cancelled;
use Webkul\Booking\States\Completed;
use Webkul\Booking\States\Confirmed;
use Webkul\Booking\States\NoShow;
use Webkul\Booking\States\Pending;

uses(TestCase::class);

it('allows the expected booking status transitions', function () {
    $config = BookingStatus::config();

    expect($config->defaultStateClass)->toBe(Pending::class)
        ->and($config->isTransitionAllowed(Pending::getMorphClass(), Confirmed::getMorphClass()))->toBeTrue()
        ->and($config->isTransitionAllowed(Pending::getMorphClass(), Cancelled::getMorphClass()))->toBeTrue()
        ->and($config->isTransitionAllowed(Confirmed::getMorphClass(), Completed::getMorphClass()))->toBeTrue()
        ->and($config->isTransitionAllowed(Confirmed::getMorphClass(), Cancelled::getMorphClass()))->toBeTrue()
        ->and($config->isTransitionAllowed(Confirmed::getMorphClass(), NoShow::getMorphClass()))->toBeTrue()
        ->and($config->isTransitionAllowed(Pending::getMorphClass(), Completed::getMorphClass()))->toBeFalse();
});
