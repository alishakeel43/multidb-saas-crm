<?php

namespace App\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class StripeConfiguredCheck extends Check
{
    public function run(): Result
    {
        $result = Result::make();

        if (filled(config('services.stripe.secret'))) {
            return $result->ok('Stripe secret is configured');
        }

        return $result->warning('Stripe secret is not configured — manual invoicing only');
    }
}
