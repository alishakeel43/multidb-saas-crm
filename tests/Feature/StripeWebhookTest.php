<?php

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\BillingService;
use Tests\Support\LandlordSchema;

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();

    config([
        'services.stripe.secret' => null,
        'services.stripe.webhook_secret' => null,
    ]);

    $plan = Plan::query()->create([
        'slug' => 'pro',
        'name' => 'Pro',
        'monthly_price' => 10,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme-stripe',
        'database' => 'tenant_acme_stripe',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);

    $this->invoice = app(BillingService::class)->createInvoiceForSubscription($subscription);
});

it('rejects invalid stripe webhook payloads', function () {
    $this->postJson('/stripe/webhook', ['nope' => true])
        ->assertStatus(400);
});

it('marks an invoice paid from checkout.session.completed', function () {
    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_123',
                'payment_intent' => 'pi_test_123',
                'customer' => 'cus_test',
                'metadata' => [
                    'invoice_id' => (string) $this->invoice->id,
                    'tenant_id' => (string) $this->invoice->tenant_id,
                ],
                'client_reference_id' => (string) $this->invoice->id,
            ],
        ],
    ];

    $this->call(
        'POST',
        '/stripe/webhook',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode($payload),
    )->assertOk();

    expect(Invoice::query()->find($this->invoice->id)->status)->toBe('paid');
});
