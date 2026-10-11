<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use RuntimeException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutService
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function client(): StripeClient
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe is not configured. Set STRIPE_SECRET in the environment.');
        }

        return new StripeClient(config('services.stripe.secret'));
    }

    public function ensureCustomer(Tenant $tenant): string
    {
        if (filled($tenant->stripe_customer_id)) {
            return $tenant->stripe_customer_id;
        }

        $customer = $this->client()->customers->create([
            'name' => $tenant->name,
            'metadata' => [
                'tenant_id' => (string) $tenant->id,
                'tenant_slug' => $tenant->slug,
            ],
        ]);

        $tenant->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * @return array{url: string, session_id: string, payment: Payment}
     */
    public function createCheckoutSession(Invoice $invoice, string $successUrl, string $cancelUrl): array
    {
        if (! $invoice->isOpen()) {
            throw new RuntimeException('Only open invoices can be collected with Stripe.');
        }

        $invoice->loadMissing('tenant');
        $tenant = $invoice->tenant;

        if (! $tenant) {
            throw new RuntimeException('Invoice is missing a tenant.');
        }

        $customerId = $this->ensureCustomer($tenant);
        $amountCents = (int) round(((float) $invoice->total) * 100);

        if ($amountCents < 50) {
            throw new RuntimeException('Stripe Checkout requires a minimum chargeable amount.');
        }

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'customer' => $customerId,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $invoice->id,
            'metadata' => [
                'invoice_id' => (string) $invoice->id,
                'tenant_id' => (string) $tenant->id,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($invoice->currency),
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => 'Invoice '.$invoice->number,
                    ],
                ],
            ]],
        ]);

        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'provider' => 'stripe',
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'status' => 'pending',
            'provider_payment_id' => $session->id,
            'meta' => ['checkout_session_id' => $session->id],
        ]);

        $invoice->update(['stripe_checkout_session_id' => $session->id]);

        $this->auditLogger->log('invoice.stripe_checkout_created', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'session_id' => $session->id,
        ], $invoice, 'checkout');

        return [
            'url' => (string) $session->url,
            'session_id' => (string) $session->id,
            'payment' => $payment,
        ];
    }

    public function handleWebhook(string $payload, ?string $signature): void
    {
        $secret = config('services.stripe.webhook_secret');

        if (filled($secret)) {
            if (! filled($signature)) {
                throw new RuntimeException('Missing Stripe signature.');
            }

            $event = Webhook::constructEvent($payload, $signature, $secret);
        } else {
            $event = json_decode($payload);

            if (! $event || ! isset($event->type)) {
                throw new RuntimeException('Invalid Stripe webhook payload.');
            }
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'customer.subscription.updated',
            'customer.subscription.deleted' => null,
            default => null,
        };
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $invoiceId = $session->metadata->invoice_id
            ?? $session->client_reference_id
            ?? null;

        if (! $invoiceId) {
            return;
        }

        $invoice = Invoice::query()->find($invoiceId);

        if (! $invoice) {
            return;
        }

        $paymentIntent = is_string($session->payment_intent ?? null)
            ? $session->payment_intent
            : ($session->id ?? null);

        $this->billingService->markPaidFromStripe($invoice, (string) $paymentIntent, [
            'checkout_session_id' => $session->id ?? null,
            'customer' => $session->customer ?? null,
        ]);

        Payment::query()
            ->where('invoice_id', $invoice->id)
            ->where('provider', 'stripe')
            ->where('status', 'pending')
            ->where('provider_payment_id', $session->id ?? null)
            ->update(['status' => 'succeeded', 'paid_at' => now()]);
    }
}
