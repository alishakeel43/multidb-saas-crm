<?php

namespace Webkul\MarketingAutomation\Console\Commands;

use Illuminate\Console\Command;
use Throwable;
use Webkul\MarketingAutomation\Services\MarketingAutomationRunner;

class RunMarketingAutomationCommand extends Command
{
    protected $signature = 'marketing-automation:run {--tenant= : Tenant slug or ID} {--all : Process every active tenant}';

    protected $description = 'Run due workflow delays and scheduled marketing emails';

    public function handle(MarketingAutomationRunner $runner): int
    {
        $tenantClass = 'App\\Models\\Tenant';

        if (! class_exists($tenantClass) || (! $this->option('all') && ! $this->option('tenant'))) {
            $result = $runner->run();
            $this->info("Workflow runs: {$result['workflows']}. Emails sent: {$result['emails']}.");

            return self::SUCCESS;
        }

        $identifier = $this->option('tenant');
        $all = (bool) $this->option('all');

        if ($identifier && $all) {
            $this->error('Specify either --tenant or --all.');

            return self::FAILURE;
        }

        $query = $tenantClass::query()->where('status', 'active')->orderBy('id');

        if (! $all) {
            $query = ctype_digit((string) $identifier)
                ? $tenantClass::query()->whereKey((int) $identifier)
                : $tenantClass::query()->where('slug', $identifier);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info($all ? 'No active tenants.' : "Tenant [{$identifier}] was not found.");

            return $all ? self::SUCCESS : self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->makeCurrent();
                $result = $runner->run();
                $this->info("[{$tenant->slug}] workflows {$result['workflows']}, emails {$result['emails']}");
            } catch (Throwable $exception) {
                $this->error("[{$tenant->slug}] {$exception->getMessage()}");
            } finally {
                $tenantClass::forgetCurrent();
            }
        }

        return self::SUCCESS;
    }
}
