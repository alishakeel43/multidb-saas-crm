<?php

namespace Webkul\UnifiedInbox\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\UnifiedInbox\Models\ChannelConnection;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;

/**
 * One-off / idempotent backfill of the central phone_number_id (page id) -> tenant mapping
 * from each tenant's existing unified_inbox_connections rows. Never touches tokens.
 */
class SyncChannelRoutesCommand extends Command
{
    protected $signature = 'unified-inbox:sync-routes {tenant? : Tenant slug or ID} {--dry-run : Only show what would change}';

    protected $description = 'Backfill the central webhook routing table from tenant channel connections';

    public function handle(CentralWebhookRouter $router): int
    {
        $identifier = $this->argument('tenant');
        $dry = (bool) $this->option('dry-run');

        $tenants = Tenant::query()
            ->when($identifier, fn ($q) => ctype_digit((string) $identifier) ? $q->whereKey((int) $identifier) : $q->where('slug', $identifier))
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenants.');

            return self::FAILURE;
        }

        $rows = [];
        $failed = false;

        foreach ($tenants as $tenant) {
            try {
                $tenant->execute(function () use ($tenant, $router, $dry, &$rows) {
                    if (! Schema::connection('tenant')->hasTable('unified_inbox_connections')) {
                        $rows[] = [$tenant->slug, '-', '-', 'skipped (no inbox tables)'];

                        return;
                    }

                    foreach (ChannelConnection::query()->get() as $connection) {
                        $externalId = (string) $connection->external_id;

                        if ($externalId === '' || str_starts_with($externalId, 'demo-')) {
                            continue;
                        }

                        $credentials = $connection->credentials ?? [];
                        $action = $connection->status === 'connected' ? 'upsert' : 'deactivate';

                        if (! $dry) {
                            if ($action === 'upsert') {
                                $router->registerRoute(
                                    $tenant->id,
                                    $connection->channel,
                                    $externalId,
                                    ($credentials['waba_id'] ?? null) ?: null,
                                    $connection->name,
                                    ($credentials['display_phone_number'] ?? null) ?: null
                                );
                            } else {
                                $router->deactivateRoute($tenant->id, $connection->channel, $externalId);
                            }
                        }

                        $rows[] = [$tenant->slug, $connection->channel, $externalId, $action.($dry ? ' (dry-run)' : '')];
                    }
                });
            } catch (\Throwable $e) {
                $failed = true;
                $rows[] = [$tenant->slug, '-', '-', 'error: '.$e->getMessage()];
            }
        }

        $this->table(['Tenant', 'Channel', 'External ID', 'Action'], $rows);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
