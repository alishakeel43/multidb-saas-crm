<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;
use Webkul\Admin\Helpers\Reporting\AbstractReporting;

uses(TestCase::class);

afterEach(function () {
    Carbon::setTestNow();
});
it('calculates dashboard percentage changes without inventing a zero-baseline percentage', function ($previous, $current, $expected) {
    $report = new class extends AbstractReporting
    {
        public function __construct() {}
    };

    expect($report->getPercentageChange($previous, $current))->toEqual($expected);
})->with([
    'increase' => [100, 125, 25],
    'decrease' => [100, 75, -25],
    'unchanged' => [100, 100, 0],
    'complete decline' => [100, 0, -100],
    'genuine doubling' => [100, 200, 100],
    'growth above 100 percent' => [100, 350, 250],
    'empty periods' => [0, 0, 0],
    'no baseline' => [0, 23, null],
    'decimal values' => [0.5, 0.75, 50],
]);

it('hides dashboard percentage progress when the previous period has no baseline', function () {
    $html = view('admin::components.dashboard.progress', [
        'metric' => 'dashboardMetric',
        'inverse' => false,
        'share' => null,
    ])->render();

    expect($html)->toContain('v-if="dashboardMetric.progress !== null"');
});

it('reapplies request-specific dashboard dates and caps the end date at today', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-09 12:00:00', 'UTC'));
    Illuminate\Support\Carbon::setTestNow(Carbon::parse('2026-10-09 12:00:00', 'UTC'));
    app()->instance('request', Request::create('/', 'GET', [
        'start' => '2026-10-01',
        'end' => '2026-10-05',
    ]));

    $report = new class extends AbstractReporting {};

    expect($report->getStartDate()->toDateString())->toBe('2026-10-01')
        ->and($report->getEndDate()->toDateTimeString())->toBe('2026-10-05 23:59:59');

    app()->instance('request', Request::create('/', 'GET', [
        'start' => '2026-10-08',
        'end' => '2026-10-12',
    ]));
    $report->applyRequest();

    expect($report->getStartDate()->toDateString())->toBe('2026-10-08')
        ->and($report->getEndDate()->toDateTimeString())->toBe('2026-10-09 23:59:59')
        ->and($report->getLastStartDate()->toDateString())->toBe('2026-10-06')
        ->and($report->getLastEndDate()->toDateTimeString())->toBe('2026-10-07 23:59:59');
});
