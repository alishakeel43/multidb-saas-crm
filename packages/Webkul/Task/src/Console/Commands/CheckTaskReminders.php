<?php

namespace Webkul\Task\Console\Commands;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Webkul\Task\Models\Task;

class CheckTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan tasks due today or overdue across all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today()->toDateString();
        $dueTodayTotal = 0;
        $overdueTotal = 0;

        Tenant::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($today, &$dueTodayTotal, &$overdueTotal) {
                $tenant->execute(function () use ($today, &$dueTodayTotal, &$overdueTotal, $tenant) {
                    $dueToday = Task::query()
                        ->incomplete()
                        ->whereDate('due_date', $today)
                        ->with(['assignedUser'])
                        ->count();

                    $overdue = Task::query()
                        ->incomplete()
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', $today)
                        ->with(['assignedUser'])
                        ->count();

                    $dueTodayTotal += $dueToday;
                    $overdueTotal += $overdue;

                    $this->line("Tenant {$tenant->slug}: {$dueToday} due today, {$overdue} overdue.");
                });
            });

        $this->info("Found {$dueTodayTotal} tasks due today and {$overdueTotal} overdue tasks across active tenants.");

        return Command::SUCCESS;
    }
}
