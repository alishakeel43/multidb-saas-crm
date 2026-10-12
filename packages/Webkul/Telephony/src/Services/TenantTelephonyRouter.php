<?php

namespace Webkul\Telephony\Services;

use App\Models\Tenant;
use App\Models\TenantTelephonyRoute;
use RuntimeException;

class TenantTelephonyRouter
{
    /**
     * Resolve the Tenant that owns a given inbound Twilio phone number.
     *
     * Returns null when no active route exists or the tenant is not active.
     */
    public function resolveTenant(string $toNumber): ?Tenant
    {
        if ($toNumber === '') {
            return null;
        }

        $route = TenantTelephonyRoute::query()
            ->where('twilio_phone_number', $toNumber)
            ->where('is_active', true)
            ->first();

        $tenant = $route?->tenant;

        if (! $tenant || ! in_array($tenant->status, ['active', null], true)) {
            return null;
        }

        return $tenant;
    }

    /**
     * Register or reactivate a Twilio phone number → tenant mapping in the
     * landlord database. Raises if the number is already claimed by another tenant.
     */
    public function registerRoute(int $tenantId, string $phoneNumber): void
    {
        if ($phoneNumber === '') {
            throw new RuntimeException('A telephony route requires a phone number.');
        }

        $existing = TenantTelephonyRoute::query()
            ->where('twilio_phone_number', $phoneNumber)
            ->first();

        if ($existing && (int) $existing->tenant_id !== $tenantId) {
            throw new RuntimeException('This phone number is already assigned to another tenant.');
        }

        TenantTelephonyRoute::updateOrCreate(
            ['twilio_phone_number' => $phoneNumber],
            [
                'tenant_id' => $tenantId,
                'is_active' => true,
            ]
        );
    }

    /**
     * Remove the phone number → tenant route from the landlord database.
     */
    public function removeRoute(int $tenantId, string $phoneNumber): void
    {
        TenantTelephonyRoute::where('twilio_phone_number', $phoneNumber)
            ->where('tenant_id', $tenantId)
            ->delete();
    }
}
