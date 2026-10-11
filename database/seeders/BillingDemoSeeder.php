<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\BillingService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Idempotent landlord billing samples for Super Admin UI / later Stripe work.
 *
 * Creates up to three invoices on the first active tenant:
 *  1) open (ready for Stripe collect later)
 *  2) paid (manual payment already recorded)
 *  3) void (closed sample)
 */
class BillingDemoSeeder extends Seeder
{
    public const MARKER_OPEN = 'demo-billing-open';

    public const MARKER_PAID = 'demo-billing-paid';

    public const MARKER_VOID = 'demo-billing-void';

    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if (! $tenant) {
            throw new RuntimeException('No active tenant found. Provision a tenant before seeding billing demo data.');
        }

        $plan = $tenant->plan
            ?? Plan::query()->where('is_active', true)->orderBy('id')->first();

        if (! $plan) {
            throw new RuntimeException('No active plan found for billing demo invoices.');
        }

        // Ensure plan has a non-zero sample price so Stripe checkout can be tried later.
        if ((float) $plan->monthly_price <= 0) {
            $plan->update([
                'monthly_price' => 49,
                'currency' => $plan->currency ?: 'USD',
            ]);
            $plan->refresh();
        }

        $subscription = $tenant->subscriptions()->latest('id')->first()
            ?? Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'starts_at' => now()->subDays(3),
                'trial_ends_at' => now()->addDays(11),
            ]);

        $billing = app(BillingService::class);

        $this->seedOpenInvoice($billing, $tenant, $plan, $subscription);
        $this->seedPaidInvoice($billing, $tenant, $plan, $subscription);
        $this->seedVoidInvoice($billing, $tenant, $plan, $subscription);

        $this->command?->info("Billing demo data ready for tenant [{$tenant->slug}] (open + paid + void invoices).");
    }

    private function seedOpenInvoice(
        BillingService $billing,
        Tenant $tenant,
        Plan $plan,
        Subscription $subscription,
    ): void {
        if (Invoice::query()->where('notes', self::MARKER_OPEN)->exists()) {
            return;
        }

        $invoice = $billing->createInvoice(
            $tenant,
            $plan,
            $subscription,
            'Demo open invoice — use Collect with Stripe later',
        );

        $invoice->update(['notes' => self::MARKER_OPEN]);
    }

    private function seedPaidInvoice(
        BillingService $billing,
        Tenant $tenant,
        Plan $plan,
        Subscription $subscription,
    ): void {
        if (Invoice::query()->where('notes', self::MARKER_PAID)->exists()) {
            return;
        }

        $invoice = $billing->createInvoice(
            $tenant,
            $plan,
            $subscription,
            'Demo paid invoice — manual payment sample',
        );

        $invoice->update(['notes' => self::MARKER_PAID]);

        $billing->markPaidManually($invoice, 'demo-manual-'.now()->format('Ymd'));
    }

    private function seedVoidInvoice(
        BillingService $billing,
        Tenant $tenant,
        Plan $plan,
        Subscription $subscription,
    ): void {
        if (Invoice::query()->where('notes', self::MARKER_VOID)->exists()) {
            return;
        }

        $invoice = $billing->createInvoice(
            $tenant,
            $plan,
            $subscription,
            'Demo void invoice — closed sample',
        );

        $invoice->update(['notes' => self::MARKER_VOID]);

        $billing->void($invoice);
    }
}
