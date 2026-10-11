<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Database\Seeders\BillingDemoSeeder;
use Tests\Support\LandlordSchema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();
});

it('seeds three demo invoices in open, paid, and void states', function () {
    $plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'monthly_price' => 0,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme-billing-demo',
        'database' => 'tenant_acme_billing_demo',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'trial',
        'starts_at' => now(),
        'trial_ends_at' => now()->addDays(14),
    ]);

    $this->seed(BillingDemoSeeder::class);
    $this->seed(BillingDemoSeeder::class); // idempotent

    expect(Invoice::query()->where('notes', BillingDemoSeeder::MARKER_OPEN)->where('status', 'open')->count())->toBe(1)
        ->and(Invoice::query()->where('notes', BillingDemoSeeder::MARKER_PAID)->where('status', 'paid')->count())->toBe(1)
        ->and(Invoice::query()->where('notes', BillingDemoSeeder::MARKER_VOID)->where('status', 'void')->count())->toBe(1)
        ->and(Payment::query()->where('tenant_id', $tenant->id)->where('provider', 'manual')->count())->toBe(1)
        ->and((float) $plan->fresh()->monthly_price)->toBe(49.0)
        ->and(Invoice::query()->count())->toBe(3);
});
