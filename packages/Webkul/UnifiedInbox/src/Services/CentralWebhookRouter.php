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
    public function registerRoute(int $tenantId, string $channel, string $externalId, ?string $wabaId = null, ?string $name = null, ?string $displayPhoneNumber = null): void
    {
        if ($externalId === '') {
            throw new RuntimeException('A channel route requires an external account ID.');
        }

        $route = TenantChannelRoute::query()
            ->where('channel', $channel)
            ->where('external_id', $externalId)
            ->first();

        // An inactive (disconnected) mapping may be claimed by the tenant that connects the number now.
        if ($route && (int) $route->tenant_id !== $tenantId && $route->is_active) {
            throw new RuntimeException('This external channel account is already assigned to another tenant.');
        }

        $attributes = [
            'tenant_id' => $tenantId,
            'waba_id' => $wabaId,
            'account_name' => $name,
            'is_active' => true,
        ];

        if ($this->hasColumn('display_phone_number') && filled($displayPhoneNumber)) {
            $attributes['display_phone_number'] = $displayPhoneNumber;
        }

        if ($this->hasColumn('deactivated_at')) {
            $attributes['deactivated_at'] = null;
        }

        TenantChannelRoute::updateOrCreate(
            ['channel' => $channel, 'external_id' => $externalId],
            $attributes
        );
    }

    /**
     * Keep the mapping row but stop routing to it (used when a tenant disconnects a channel).
     */
    public function deactivateRoute(int $tenantId, string $channel, string $externalId): void
    {
        $attributes = ['is_active' => false];

        if ($this->hasColumn('deactivated_at')) {
            $attributes['deactivated_at'] = now();
        }

        TenantChannelRoute::where('channel', $channel)
            ->where('external_id', $externalId)
            ->where('tenant_id', $tenantId)
            ->update($attributes);
    }

    /**
     * Central URL dedicated to WhatsApp (alias of the Meta webhook, same handler).
     */
    public function getWhatsAppWebhookUrl(): string
    {
        return preg_replace('#/unified-inbox/meta/webhook$#', '/api/whatsapp/webhook', $this->getCentralWebhookUrl());
    }

    protected function hasColumn(string $column): bool
    {
        static $cache = [];

        if (! array_key_exists($column, $cache)) {
            try {
                $cache[$column] = \Illuminate\Support\Facades\Schema::connection('landlord')->hasColumn('tenant_channel_routes', $column);
            } catch (\Throwable) {
                return false;
            }
        }

        return $cache[$column];
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
