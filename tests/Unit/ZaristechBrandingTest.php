<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;
use Webkul\Attribute\Repositories\AttributeRepository;

uses(TestCase::class);

it('keeps the CRM logo variants on the Zaristech brand color', function () {
    $assetDirectory = base_path('packages/Webkul/Admin/src/Resources/assets/images');

    foreach (['logo.svg', 'dark-logo.svg', 'mobile-light-logo.svg', 'mobile-dark-logo.svg'] as $filename) {
        $svg = file_get_contents("{$assetDirectory}/{$filename}");

        expect($svg)->toContain('#2846A0');
    }

    expect(file_get_contents("{$assetDirectory}/logo.svg"))->toContain('Zaristech')
        ->and(file_get_contents("{$assetDirectory}/dark-logo.svg"))->toContain('Zaristech');
});

it('uses Zaristech branding in Super Admin and the Admin help translations', function () {
    $panel = Filament::getPanel('super-admin');
    $translations = require base_path('packages/Webkul/Admin/src/Resources/lang/en/app.php');

    expect((string) $panel->getBrandName())->toBe('Zaristech SaaS Platform')
        ->and($translations['help']['index']['description'])->toContain('Zaristech')
        ->and($translations['help']['index']['still-need-help-description'])->toContain('Zaristech');
});

it('translates the lead kanban user filters', function () {
    $translations = require base_path('packages/Webkul/Admin/src/Resources/lang/en/app.php');
    $userFilters = $translations['leads']['index']['kanban']['toolbar']['user-filter'];

    expect($userFilters['all'])->toBe('All users')
        ->and($userFilters['unassigned'])->toBe('Unassigned');
});

it('renders the lead contact form without the organization lookup when its attribute is missing', function () {
    $attributeRepository = Mockery::mock(AttributeRepository::class);
    $attributeRepository->shouldReceive('findOneWhere')
        ->once()
        ->with([
            'entity_type' => 'persons',
            'code' => 'organization_id',
        ])
        ->andReturnNull();

    app()->instance(AttributeRepository::class, $attributeRepository);

    $html = Blade::render("@include('admin::leads.common.contact') @stack('scripts')");

    expect($html)->not->toContain('person[organization_id]');
});
