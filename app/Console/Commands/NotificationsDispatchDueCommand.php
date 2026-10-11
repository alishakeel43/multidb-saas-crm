<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Webkul\Notification\Services\DueAlertDispatcher;

class NotificationsDispatchDueCommand extends Command
{
    protected $signature = 'notifications:dispatch-due {--tenant= : Tenant slug or ID} {--all : Process every active tenant}';

    protected $description = 'Create in-app alerts for upcoming follow-ups, overdue activities, and lead deadlines';

    public function handle(DueAlertDispatcher $dispatcher): int
    {
        $identifier = $this->option('tenant');
        $all = (bool) $this->option('all');

        if (($identifier && $all) || (! $identifier && ! $all)) {
            $this->error('Specify --tenant=slug|id or --all.');

            return self::FAILURE;
        }

        $tenants = $all
            ? Tenant::query()->where('status', 'active')->orderBy('id')->get()
            : Tenant::query()
                ->when(
                    ctype_digit((string) $identifier),
                    fn ($query) => $query->whereKey((int) $identifier),
                    fn ($query) => $query->where('slug', $identifier),
                )
                ->get();

        if ($tenants->isEmpty()) {
            $this->error($all ? 'No active tenants found.' : "Tenant [{$identifier}] was not found.");

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->makeCurrent();
                $summary = $dispatcher->dispatch();
                $this->info(sprintf(
                    '[%s] follow-ups=%d deadlines=%d overdue=%d',
                    $tenant->slug,
                    $summary['follow_ups'],
                    $summary['deadlines'],
                    $summary['overdue'],
                ));
            } catch (\Throwable $exception) {
                $this->error("[{$tenant->slug}] {$exception->getMessage()}");
            } finally {
                Tenant::forgetCurrent();
            }
        }

        return self::SUCCESS;
    }
}
