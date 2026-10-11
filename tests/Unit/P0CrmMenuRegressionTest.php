<?php

use App\Services\FeatureEntitlementService;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Guardrail: P0 packages must not turn unrelated core top-level menus into
 * parent items (Krayin sidebar uses javascript:void(0) for parents, which
 * hides Create buttons that live on the list page).
 */
it('does not nest children under core leaf menus that must stay clickable', function () {
    $menu = collect(config('menu.admin'));

    $childrenOf = function (string $parent) use ($menu): array {
        return $menu
            ->pluck('key')
            ->filter(fn (string $key): bool => str_starts_with($key, $parent.'.'))
            ->values()
            ->all();
    };

    expect($childrenOf('leads'))->toBe([])
        ->and($childrenOf('activities'))->toBe([])
        ->and($childrenOf('products'))->toBe([])
        ->and($childrenOf('dashboard'))->toBe([])
        ->and($childrenOf('bookings'))->toBe([]);
});

it('keeps Quotes list reachable after Approvals is nested under Quotes', function () {
    $keys = collect(config('menu.admin'))->pluck('key');

    expect($keys)->toContain('quotes')
        ->and($keys)->toContain('quotes.quotes')
        ->and($keys)->toContain('quotes.approvals');

    $quotesList = collect(config('menu.admin'))->firstWhere('key', 'quotes.quotes');
    $approvals = collect(config('menu.admin'))->firstWhere('key', 'quotes.approvals');

    expect($quotesList['route'] ?? null)->toBe('admin.quotes.index')
        ->and($approvals['route'] ?? null)->toBe('admin.quotes.approvals.index');
});

it('nests P0 settings under Other Settings so Settings index shows clickable cards', function () {
    $firstLevel = collect(config('menu.admin'))
        ->pluck('key')
        ->filter(fn (string $key): bool => str_starts_with($key, 'settings.') && substr_count($key, '.') === 1)
        ->values();

    // Must NOT be empty parent sections on Settings index (those render blank boxes).
    expect($firstLevel)->not->toContain('settings.assignment')
        ->and($firstLevel)->not->toContain('settings.booking')
        ->and($firstLevel)->not->toContain('settings.consent')
        ->and($firstLevel)->toContain('settings.user')
        ->and($firstLevel)->toContain('settings.lead')
        ->and($firstLevel)->toContain('settings.other_settings');

    $otherSettings = collect(config('menu.admin'))->pluck('key');

    expect($otherSettings)->toContain('settings.other_settings.assignment')
        ->and($otherSettings)->toContain('settings.other_settings.booking')
        ->and($otherSettings)->toContain('settings.other_settings.consent')
        ->and($otherSettings)->toContain('settings.other_settings.notifications')
        ->and($otherSettings)->toContain('settings.other_settings.lead_scoring')
        ->and($otherSettings)->toContain('settings.other_settings.tags');
});

it('maps P0 and core admin routes to the intended feature entitlements', function () {
    $svc = app(FeatureEntitlementService::class);

    expect($svc->featureForRoute('admin.quotes.index'))->toBe('quotes')
        ->and($svc->featureForRoute('admin.quotes.create'))->toBe('quotes')
        ->and($svc->featureForRoute('admin.quotes.approvals.index'))->toBe('quote_approvals')
        ->and($svc->featureForRoute('admin.leads.index'))->toBe('leads')
        ->and($svc->featureForRoute('admin.leads.duplicates.check'))->toBe('assignment')
        ->and($svc->featureForRoute('admin.activities.index'))->toBe('activities')
        ->and($svc->featureForRoute('admin.bookings.index'))->toBe('bookings')
        ->and($svc->featureForRoute('admin.sales-reports.index'))->toBe('sales_reports')
        ->and($svc->featureForRoute('admin.notifications.index'))->toBe('notifications')
        ->and($svc->featureForRoute('admin.settings.lead-scoring.index'))->toBe('lead_scoring')
        ->and($svc->featureForRoute('admin.contacts.persons.index'))->toBe('contacts')
        ->and($svc->featureForRoute('admin.products.index'))->toBe('products')
        ->and($svc->featureForRoute('admin.mail.index'))->toBe('mail')
        ->and($svc->featureForRoute('admin.settings.assignment.index'))->toBe('assignment')
        ->and($svc->featureForRoute('admin.settings.consent.index'))->toBe('consent')
        ->and($svc->featureForRoute('admin.settings.booking.index'))->toBe('bookings');
});

it('registers ACL keys that match the Settings card menu keys', function () {
    $acl = collect(config('acl'))->pluck('key');

    expect($acl)->toContain('settings.other_settings.assignment')
        ->and($acl)->toContain('settings.other_settings.booking')
        ->and($acl)->toContain('settings.other_settings.consent')
        ->and($acl)->toContain('settings.other_settings.notifications')
        ->and($acl)->toContain('settings.other_settings.lead_scoring')
        ->and($acl)->toContain('sales_reports')
        ->and($acl)->toContain('quotes.quotes')
        ->and($acl)->toContain('quotes.approvals');
});

it('registers Sales reports as a top-level clickable menu', function () {
    $item = collect(config('menu.admin'))->firstWhere('key', 'sales_reports');

    expect($item)->not->toBeNull()
        ->and($item['route'] ?? null)->toBe('admin.sales-reports.index');
});
