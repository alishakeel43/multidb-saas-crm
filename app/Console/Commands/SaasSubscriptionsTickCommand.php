<?php

namespace App\Console\Commands;

use App\Services\SubscriptionLifecycleService;
use Illuminate\Console\Command;

class SaasSubscriptionsTickCommand extends Command
{
    protected $signature = 'saas:subscriptions:tick';

    protected $description = 'Expire trials, open renewal invoices, and expire past-due subscriptions';

    public function handle(SubscriptionLifecycleService $subscriptions): int
    {
        $summary = $subscriptions->tick();

        $this->info(sprintf(
            'Trials processed: %d, renewal invoices: %d, expired: %d',
            $summary['expired_trials'],
            $summary['renewal_invoices'],
            $summary['expired'],
        ));

        return self::SUCCESS;
    }
}
