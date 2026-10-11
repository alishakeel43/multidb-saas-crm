<?php

use App\Services\SalesReportService;
use Tests\TestCase;

uses(TestCase::class);

it('returns a stable metrics snapshot shape even without CRM tables', function () {
    $snapshot = app(SalesReportService::class)->snapshot();

    expect($snapshot)->toHaveKeys([
        'open_leads',
        'won_leads',
        'lost_leads',
        'conversion_rate',
        'quotes_pending_approval',
        'quotes_approved',
        'quotes_converted',
        'bookings_upcoming',
        'consent_opt_ins',
        'lead_value_open',
        'avg_lead_score',
        'leads_by_source',
    ]);

    expect($snapshot['leads_by_source'])->toBeArray();
});
