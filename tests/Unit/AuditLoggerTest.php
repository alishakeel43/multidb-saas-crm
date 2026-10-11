<?php

use App\Models\Activity;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Tests\Support\LandlordSchema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    LandlordSchema::configureMemoryConnections();
    LandlordSchema::migrate();
});

it('writes sanitized saas audit events', function () {
    $plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'is_active' => true,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme-audit',
        'database' => 'tenant_acme_audit',
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    app(AuditLogger::class)->log('tenant.tested', [
        'tenant_id' => $tenant->id,
        'password' => 'super-secret',
    ], $tenant, 'tested');

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('tenant.tested')
        ->and($activity->properties['password'])->toBe('[redacted]')
        ->and($activity->properties['tenant_id'])->toBe($tenant->id);
});
