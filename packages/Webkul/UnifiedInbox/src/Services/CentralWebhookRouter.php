<?php

namespace Webkul\UnifiedInbox\Services;

use App\Models\Tenant;
use App\Models\TenantChannelRoute;
use RuntimeException;

class CentralWebhookRouter
{
    /**
     * Get the authoritative central webhook URL using the root platform URL.
     */
    public function getCentralWebhookUrl(): string
    {
        $explicit = config('unified-inbox.webhook_url');
        if (filled($explicit)) {
            return (string) $explicit;
        }

        $appUrl = (string) config('app.url', '');
        $tenantBaseDomain = (string) config('multitenancy.tenant_base_domain', '');

        if (filled($appUrl)) {
            $parsed = parse_url($appUrl);
            $host = $parsed['host'] ?? '';

            // If the host in app.url contains a tenant subdomain (e.g., demo.krayinsaas.localhost),
            // strip the tenant subdomain so it is strictly the root platform domain.
            if ($tenantBaseDomain && str_ends_with($host, $tenantBaseDomain) && $host !== $tenantBaseDomain) {
                $scheme = $parsed['scheme'] ?? 'http';
                $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

                return "{$scheme}://{$tenantBaseDomain}{$port}/unified-inbox/meta/webhook";
            }

            return rtrim($appUrl, '/').'/unified-inbox/meta/webhook';
        }

        if (request()) {
            $scheme = request()->getScheme();
            $port = request()->getPort();
            $base = $tenantBaseDomain ?: request()->getHost();

            if ($tenantBaseDomain && str_ends_with($base, $tenantBaseDomain)) {
                $base = $tenantBaseDomain;
            }

            $portStr = ($port && ! in_array($port, [80, 443])) ? ":{$port}" : '';

            return "{$scheme}://{$base}{$portStr}/unified-inbox/meta/webhook";
        }

        return 'http://localhost/unified-inbox/meta/webhook';
    }

    /**
     * Resolve the target Tenant based on the incoming channel and external account ID.
     */
    public function resolveTenant(string $channel, string $externalAccountId, ?string $wabaId = null): ?Tenant
    {
        if ($externalAccountId === '') {
            return null;
        }

        $route = TenantChannelRoute::query()
            ->where('channel', $channel)
            ->where('external_id', $externalAccountId)
            ->where('is_active', true)
            ->first();

        $tenant = $route?->tenant;

        if (! $tenant || ! in_array($tenant->status, ['active', null], true)) {
            return null;
        }

        return $tenant;
    }

    /**
     * Register a channel connection route in the landlord database.
     */
    public function registerRoute(int $tenantId, string $channel, string $externalId, ?string $wabaId = null, ?string $name = null): void
    {
        if ($externalId === '') {
            throw new RuntimeException('A channel route requires an external account ID.');
        }

        $route = TenantChannelRoute::query()
            ->where('channel', $channel)
            ->where('external_id', $externalId)
            ->first();

        if ($route && (int) $route->tenant_id !== $tenantId) {
            throw new RuntimeException('This external channel account is already assigned to another tenant.');
        }

        TenantChannelRoute::updateOrCreate(
            ['channel' => $channel, 'external_id' => $externalId],
            [
                'tenant_id' => $tenantId,
                'waba_id' => $wabaId,
                'account_name' => $name,
                'is_active' => true,
            ]
        );
    }

    /**
     * Remove or deactivate a channel route from the landlord database.
     */
    public function removeRoute(int $tenantId, string $channel, string $externalId): void
    {
        TenantChannelRoute::where('channel', $channel)
            ->where('external_id', $externalId)
            ->where('tenant_id', $tenantId)
            ->delete();
    }
}
