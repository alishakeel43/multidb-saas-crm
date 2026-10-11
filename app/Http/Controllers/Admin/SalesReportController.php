<?php

namespace App\Http\Controllers\Admin;

use App\Services\SalesReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Controllers\Controller;

class SalesReportController extends Controller
{
    public function __construct(
        protected SalesReportService $reports,
    ) {}

    public function index(): View
    {
        return view('sales-reports.index', [
            'metrics' => $this->reports->snapshot(),
            'generatedAt' => now(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $metrics = $this->reports->snapshot();
        $filename = 'sales-metrics-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($metrics) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['metric', 'value']);

            foreach ($metrics as $key => $value) {
                fputcsv($handle, [$key, $value]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
