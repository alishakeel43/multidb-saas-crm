<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\BillingService;
use Tests\Support\LandlordSchema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();

    $this->plan = Plan::query()->create([
        'slug' => 'pro',
        'name' => 'Pro',
        'monthly_price' => 49,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $this->tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme',
        'database' => 'tenant_acme',
        'plan_id' => $this->plan->id,
        'status' => 'active',
    ]);

    $this->subscription = Subscription::query()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);
});

it('creates an open invoice from a subscription', function () {
    $invoice = app(BillingService::class)->createInvoiceForSubscription($this->subscription);

    expect($invoice->status)->toBe('open')
        ->and($invoice->total)->toBe('49.00')
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->number)->toStartWith('INV-');
});

it('marks an invoice paid manually', function () {
    $billing = app(BillingService::class);
    $invoice = $billing->createInvoiceForSubscription($this->subscription);
    $payment = $billing->markPaidManually($invoice, 'CHK-1');

    expect($payment->status)->toBe('succeeded')
        ->and($invoice->fresh()->status)->toBe('paid')
        ->and($invoice->fresh()->paid_at)->not->toBeNull();
});

it('voids an open invoice', function () {
    $billing = app(BillingService::class);
    $invoice = $billing->createInvoiceForSubscription($this->subscription);

    expect($billing->void($invoice)->status)->toBe('void');
});
