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
        $metrics = $this->reports->snapshot();

        return view('sales-reports.index', [
            'metrics' => $metrics,
            'sources' => $metrics['leads_by_source'] ?? [],
            'generatedAt' => now(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $rows = $this->reports->exportRows();
        $filename = 'sales-metrics-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['metric', 'value']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
