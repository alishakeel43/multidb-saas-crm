<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BillingService
{
    public function __construct(
        private readonly PlatformSettingsRepository $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function createInvoiceForSubscription(Subscription $subscription, ?string $description = null): Invoice
    {
        $subscription->loadMissing(['tenant', 'plan']);

        $tenant = $subscription->tenant;
        $plan = $subscription->plan;

        if (! $tenant || ! $plan) {
            throw new InvalidArgumentException('Subscription must have a tenant and plan.');
        }

        return $this->createInvoice($tenant, $plan, $subscription, $description);
    }

    public function createInvoice(
        Tenant $tenant,
        Plan $plan,
        ?Subscription $subscription = null,
        ?string $description = null,
    ): Invoice {
        $currency = $plan->currency ?: $this->settings->currency();
        $subtotal = (float) $plan->monthly_price;
        $taxPercent = $this->settings->taxPercent();
        $tax = round($subtotal * ($taxPercent / 100), 2);
        $total = round($subtotal + $tax, 2);

        return DB::connection('landlord')->transaction(function () use (
            $tenant,
            $plan,
            $subscription,
            $description,
            $currency,
            $subtotal,
            $tax,
            $total,
        ): Invoice {
            $invoice = Invoice::query()->create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription?->id,
                'number' => $this->nextInvoiceNumber(),
                'status' => 'open',
                'currency' => $currency,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'due_at' => now()->addDays(14),
            ]);

            $invoice->items()->create([
                'description' => $description ?: sprintf('%s plan — monthly subscription', $plan->name),
                'quantity' => 1,
                'unit_amount' => $subtotal,
                'amount' => $subtotal,
            ]);

            $this->auditLogger->log('invoice.created', [
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'total' => $total,
            ], $invoice, 'created');

            return $invoice->load('items');
        });
    }

    public function markPaidManually(Invoice $invoice, ?string $reference = null): Payment
    {
        if ($invoice->isPaid()) {
            throw new RuntimeException('Invoice is already paid.');
        }

        if ($invoice->status === 'void') {
            throw new RuntimeException('Cannot pay a void invoice.');
        }

        return DB::connection('landlord')->transaction(function () use ($invoice, $reference): Payment {
            $payment = Payment::query()->create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'provider' => 'manual',
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
                'status' => 'succeeded',
                'provider_payment_id' => $reference,
                'paid_at' => now(),
                'meta' => ['method' => 'manual'],
            ]);

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->auditLogger->log('invoice.paid_manual', [
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
            ], $invoice, 'paid');

            return $payment;
        });
    }

    public function markPaidFromStripe(Invoice $invoice, string $providerPaymentId, array $meta = []): Payment
    {
        return DB::connection('landlord')->transaction(function () use ($invoice, $providerPaymentId, $meta): Payment {
            if ($invoice->isPaid()) {
                return $invoice->payments()
                    ->where('provider_payment_id', $providerPaymentId)
                    ->first()
                    ?? $invoice->payments()->latest('id')->firstOrFail();
            }

            $payment = Payment::query()->updateOrCreate(
                [
                    'provider' => 'stripe',
                    'provider_payment_id' => $providerPaymentId,
                ],
                [
                    'tenant_id' => $invoice->tenant_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total,
                    'currency' => $invoice->currency,
                    'status' => 'succeeded',
                    'paid_at' => now(),
                    'meta' => $meta,
                ],
            );

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->auditLogger->log('invoice.paid_stripe', [
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
            ], $invoice, 'paid');

            return $payment;
        });
    }

    public function void(Invoice $invoice): Invoice
    {
        if ($invoice->isPaid()) {
            throw new RuntimeException('Cannot void a paid invoice.');
        }

        $invoice->update(['status' => 'void']);

        $this->auditLogger->log('invoice.voided', [
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
        ], $invoice, 'voided');

        return $invoice->refresh();
    }

    public function nextInvoiceNumber(): string
    {
        $prefix = config('saas.invoice_number_prefix', 'INV');
        $year = now()->format('Y');

        $latest = Invoice::query()
            ->where('number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('number')
            ->value('number');

        $sequence = 1;

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}
