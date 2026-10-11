<?php

use App\Services\SalesReportService;
use Tests\TestCase;

uses(TestCase::class);

it('returns a stable metrics snapshot shape even without CRM tables', function () {
    $snapshot = app(SalesReportService::class)->snapshot();

    expect($snapshot)->toHaveKeys([
        'open_leads',
        'won_leads',
        'quotes_pending_approval',
        'quotes_approved',
        'bookings_upcoming',
        'consent_opt_ins',
        'lead_value_open',
    ])->each->toBeNumeric();
});
