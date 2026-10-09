<?php

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class);

it('renders the lead product quick-create form and catalog endpoint', function () {
    $html = Blade::render("@include('admin::leads.common.products') @stack('scripts')");

    expect($html)->toContain('/admin/products/create')
        ->and($html)->toContain('deferAddNew')
        ->and($html)->toContain('openProductModal')
        ->and($html)->toContain('syncProductModal')
        ->and($html)->toContain("this.\$emit('add-new', name)");
});
