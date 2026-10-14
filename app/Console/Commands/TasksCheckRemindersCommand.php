<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Webkul\Task\Services\TaskReminderService;

class TasksCheckRemindersCommand extends Command
{
    protected $signature = 'tasks:check-reminders {--tenant= : Tenant slug or ID} {--all : Process every tenant}';

    protected $description = 'Notify assignees about due and overdue tasks in each tenant database';

    public function handle(TaskReminderService $reminders): int
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

        $total = 0;

        foreach ($tenants as $tenant) {
            try {
                $tenant->makeCurrent();
                $sent = $reminders->dispatchDue(now());
                $total += $sent;
                $this->info("[{$tenant->slug}] task reminders: {$sent}");
            } catch (\Throwable $exception) {
                $this->error("[{$tenant->slug}] {$exception->getMessage()}");
            } finally {
                Tenant::forgetCurrent();
            }
        }

        $this->info("Total task reminders: {$total}");

        return self::SUCCESS;
    }
}
