<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\PlatformSettingsRepository;
use App\Services\SubscriptionLifecycleService;
use Tests\Support\LandlordSchema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();

    $this->plan = Plan::query()->create([
        'slug' => 'pro',
        'name' => 'Pro',
        'monthly_price' => 20,
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
});

it('starts a trial subscription', function () {
    $subscription = app(SubscriptionLifecycleService::class)->startTrial($this->tenant, $this->plan);

    expect($subscription->status)->toBe('trial')
        ->and($subscription->trial_ends_at)->not->toBeNull()
        ->and($this->tenant->fresh()->plan_id)->toBe($this->plan->id);
});

it('expires ended trials into past_due and opens an invoice', function () {
    Subscription::query()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'status' => 'trial',
        'starts_at' => now()->subDays(20),
        'trial_ends_at' => now()->subDay(),
    ]);

    $summary = app(SubscriptionLifecycleService::class)->tick();

    expect($summary['expired_trials'])->toBe(1)
        ->and($summary['renewal_invoices'])->toBe(1)
        ->and($this->tenant->subscriptions()->latest('id')->first()->status)->toBe('past_due')
        ->and($this->tenant->invoices()->count())->toBe(1);
});

it('blocks tenant access when enforcement is enabled and subscription is expired', function () {
    $subscription = Subscription::query()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'status' => 'expired',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);

    $settings = Mockery::mock(PlatformSettingsRepository::class);
    $settings->shouldReceive('enforceSubscription')->andReturn(true);
    $settings->shouldReceive('trialDays')->andReturn(14);
    $settings->shouldReceive('taxPercent')->andReturn(0);
    $settings->shouldReceive('currency')->andReturn('USD');
    $this->app->instance(PlatformSettingsRepository::class, $settings);

    $service = app(SubscriptionLifecycleService::class);

    expect($service->tenantHasAccess($this->tenant->fresh()))->toBeFalse();

    $subscription->update(['status' => 'active']);

    expect($service->tenantHasAccess($this->tenant->fresh()))->toBeTrue();
});
