<?php

namespace Webkul\UnifiedInbox\Services;

use App\Models\Tenant;
use App\Models\TenantChannelRoute;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CentralWebhookRouter
{
    /**
     * Resolve the target Tenant based on the incoming channel and external account ID.
     */
    public function resolveTenant(string $channel, string $externalAccountId, ?string $wabaId = null): ?Tenant
    {
        // 1. Direct Landlord Routing Lookup
        if (class_exists(TenantChannelRoute::class)) {
            $route = TenantChannelRoute::where('channel', $channel)
                ->where('external_id', $externalAccountId)
                ->where('is_active', true)
                ->first();

            if ($route && $route->tenant) {
                return $route->tenant;
            }

            // Fallback for WhatsApp using WABA ID
            if ($wabaId && $channel === 'whatsapp') {
                $route = TenantChannelRoute::where('channel', 'whatsapp')
                    ->where('waba_id', $wabaId)
                    ->where('is_active', true)
                    ->first();

                if ($route && $route->tenant) {
                    return $route->tenant;
                }
            }
        }

        // 2. Fallback search across active tenants
        try {
            $tenants = Tenant::where('status', 'active')->orWhereNull('status')->get();

            foreach ($tenants as $tenant) {
                $found = false;
                $tenant->execute(function () use ($channel, $externalAccountId, &$found) {
                    $found = DB::table('unified_inbox_connections')
                        ->where('channel', $channel)
                        ->where('external_id', $externalAccountId)
                        ->where('status', 'connected')
                        ->exists();
                });

                if ($found) {
                    // Cache/Register route in landlord table for next time
                    $this->registerRoute($tenant->id, $channel, $externalAccountId, $wabaId, $tenant->name);

                    return $tenant;
                }
            }
        } catch (Exception $e) {
            Log::warning('CentralWebhookRouter fallback search failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Register a channel connection route in the landlord database.
     */
    public function registerRoute(int $tenantId, string $channel, string $externalId, ?string $wabaId = null, ?string $name = null): void
    {
        try {
            TenantChannelRoute::updateOrCreate(
                [
                    'channel' => $channel,
                    'external_id' => $externalId,
                ],
                [
                    'tenant_id' => $tenantId,
                    'waba_id' => $wabaId,
                    'account_name' => $name,
                    'is_active' => true,
                ]
            );
        } catch (Exception $e) {
            Log::warning('Failed to register tenant channel route: '.$e->getMessage());
        }
    }

    /**
     * Remove or deactivate a channel route from the landlord database.
     */
    public function removeRoute(string $channel, string $externalId): void
    {
        try {
            TenantChannelRoute::where('channel', $channel)
                ->where('external_id', $externalId)
                ->delete();
        } catch (Exception $e) {
            Log::warning('Failed to remove tenant channel route: '.$e->getMessage());
        }
    }
}
