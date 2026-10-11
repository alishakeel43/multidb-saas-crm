<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionLifecycleService
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly PlatformSettingsRepository $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function startTrial(Tenant $tenant, Plan $plan): Subscription
    {
        $trialDays = $this->settings->trialDays();

        return DB::connection('landlord')->transaction(function () use ($tenant, $plan, $trialDays): Subscription {
            $subscription = Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'starts_at' => now(),
                'trial_ends_at' => now()->addDays($trialDays),
            ]);

            $tenant->update([
                'plan_id' => $plan->id,
                'trial_ends_at' => $subscription->trial_ends_at,
                'status' => $tenant->status === 'pending' ? 'active' : $tenant->status,
            ]);

            $this->auditLogger->log('subscription.trial_started', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
            ], $subscription, 'trial');

            return $subscription;
        });
    }

    public function activate(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'starts_at' => $subscription->starts_at ?? now(),
            'canceled_at' => null,
        ]);

        $subscription->tenant?->update(['status' => 'active']);

        $this->auditLogger->log('subscription.activated', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
        ], $subscription, 'activated');

        return $subscription->refresh();
    }

    public function changePlan(Subscription $subscription, Plan $newPlan, bool $createInvoice = true): Subscription
    {
        if ($subscription->plan_id === $newPlan->id) {
            throw new InvalidArgumentException('Subscription is already on this plan.');
        }

        return DB::connection('landlord')->transaction(function () use ($subscription, $newPlan, $createInvoice): Subscription {
            $subscription->update(['plan_id' => $newPlan->id]);
            $subscription->tenant?->update(['plan_id' => $newPlan->id]);

            if ($createInvoice && (float) $newPlan->monthly_price > 0) {
                $this->billingService->createInvoiceForSubscription(
                    $subscription->fresh(['tenant', 'plan']),
                    sprintf('Plan change to %s', $newPlan->name),
                );
            }

            $this->auditLogger->log('subscription.plan_changed', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $newPlan->id,
            ], $subscription, 'plan_changed');

            return $subscription->fresh(['plan', 'tenant']);
        });
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'ends_at' => now(),
        ]);

        $subscription->tenant?->update(['status' => 'canceled']);

        $this->auditLogger->log('subscription.canceled', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
        ], $subscription, 'canceled');

        return $subscription->refresh();
    }

    public function expire(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'expired',
            'ends_at' => now(),
        ]);

        $this->auditLogger->log('subscription.expired', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
        ], $subscription, 'expired');

        return $subscription->refresh();
    }

    public function markPastDue(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'past_due']);

        $this->auditLogger->log('subscription.past_due', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
        ], $subscription, 'past_due');

        return $subscription->refresh();
    }

    public function tick(): array
    {
        $summary = ['expired_trials' => 0, 'renewal_invoices' => 0, 'expired' => 0];

        Subscription::query()
            ->with(['tenant', 'plan'])
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->each(function (Subscription $subscription) use (&$summary): void {
                $plan = $subscription->plan;

                if ($plan && (float) $plan->monthly_price > 0) {
                    $this->markPastDue($subscription);
                    $this->billingService->createInvoiceForSubscription($subscription, 'Trial ended — first invoice');
                    $summary['renewal_invoices']++;
                } else {
                    $this->activate($subscription);
                }

                $summary['expired_trials']++;
            });

        Subscription::query()
            ->where('status', 'past_due')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->each(function (Subscription $subscription) use (&$summary): void {
                $this->expire($subscription);
                $summary['expired']++;
            });

        return $summary;
    }

    public function tenantHasAccess(Tenant $tenant): bool
    {
        if (! $this->settings->enforceSubscription()) {
            return true;
        }

        if (in_array($tenant->status, config('saas.tenant_blocked_statuses', []), true)) {
            return false;
        }

        $subscription = $tenant->latestSubscription();

        if (! $subscription) {
            return false;
        }

        if (in_array($subscription->status, config('saas.subscription_blocked_statuses', []), true)) {
            return false;
        }

        if ($subscription->status === 'past_due') {
            return false;
        }

        return true;
    }
}
