<?php

namespace Webkul\Activity\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Webkul\Activity\Jobs\SendActivityReminder;
use Webkul\Activity\Models\ActivityReminder;

class DispatchActivityReminders extends Command
{
    protected $signature = 'activities:dispatch-reminders';

    protected $description = 'Queue due activity reminders for all active tenant databases';

    public function handle(): int
    {
        Tenant::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->each(function (Tenant $tenant) {
                $tenant->execute(function () use ($tenant) {
                    ActivityReminder::query()
                        ->where('status', 'pending')
                        ->where('remind_at', '<=', now())
                        ->chunkById(200, function ($rows) use ($tenant) {
                            foreach ($rows as $row) {
                                SendActivityReminder::dispatch($row->id, $tenant->id)->afterCommit();
                            }
                        });
                });
            });

        return self::SUCCESS;
    }
}
