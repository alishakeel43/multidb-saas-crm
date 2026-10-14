<?php

use App\Services\FeatureEntitlementService;
use Tests\TestCase;

uses(TestCase::class);

it('registers tasks as a top-level workspace feature', function () {
    $item = collect(config('menu.admin'))->firstWhere('key', 'tasks');

    expect($item)->not->toBeNull()
        ->and($item['route'] ?? null)->toBe('admin.tasks.index')
        ->and(collect(config('acl'))->pluck('key'))->toContain('tasks')
        ->and(app(FeatureEntitlementService::class)->featureForRoute('admin.tasks.index'))->toBe('tasks')
        ->and(app(FeatureEntitlementService::class)->featureForRoute('admin.tasks.view'))->toBe('tasks');
});
