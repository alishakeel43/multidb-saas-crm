<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Booking\Models\Booking;
use Webkul\Consent\Models\PersonConsent;
use Webkul\Lead\Models\Lead;
use Webkul\QuoteApproval\Models\QuoteApproval;

/**
 * CRM sales metrics for P0 reporting hooks.
 */
class SalesReportService
{
    /**
     * @return array<string, int|float|array<int, array<string, mixed>>>
     */
    public function snapshot(): array
    {
        $openLeads = $this->safeCount(fn () => Lead::query()->where('status', 1)->count());
        $wonLeads = $this->safeCount(fn () => Lead::query()->whereNotNull('closed_at')->where('status', 0)->count());
        $lostLeads = $this->safeCount(fn () => Lead::query()->whereNull('closed_at')->where('status', 0)->count());
        $totalClosed = $wonLeads + $lostLeads;

        return [
            'open_leads' => $openLeads,
            'won_leads' => $wonLeads,
            'lost_leads' => $lostLeads,
            'conversion_rate' => $totalClosed > 0 ? round(($wonLeads / $totalClosed) * 100, 1) : 0.0,
            'quotes_pending_approval' => $this->safeCount(fn () => QuoteApproval::query()->where('status', QuoteApproval::STATUS_PENDING)->count()),
            'quotes_approved' => $this->safeCount(fn () => QuoteApproval::query()->where('status', QuoteApproval::STATUS_APPROVED)->count()),
            'quotes_converted' => $this->safeCount(fn () => QuoteApproval::query()->where('delivery_status', QuoteApproval::DELIVERY_CONVERTED)->count()),
            'bookings_upcoming' => $this->safeCount(fn () => Booking::query()->where('starts_at', '>=', now())->where('status', '!=', 'cancelled')->count()),
            'consent_opt_ins' => $this->safeCount(fn () => PersonConsent::query()->where('is_opted_in', true)->count()),
            'lead_value_open' => $this->safeSum(),
            'avg_lead_score' => $this->safeAvgScore(),
            'leads_by_source' => $this->leadsBySource(),
        ];
    }

    /**
     * Flatten snapshot (including nested source rows) for CSV export.
     *
     * @return array<int, array{0: string, 1: int|float|string}>
     */
    public function exportRows(): array
    {
        $snapshot = $this->snapshot();
        $rows = [];

        foreach ($snapshot as $key => $value) {
            if ($key === 'leads_by_source') {
                continue;
            }

            $rows[] = [$key, $value];
        }

        foreach ($snapshot['leads_by_source'] as $row) {
            $rows[] = ['source:'.($row['name'] ?? 'unknown'), $row['count'] ?? 0];
        }

        return $rows;
    }

    private function safeCount(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function safeSum(): float
    {
        try {
            if (! Schema::hasTable('leads')) {
                return 0.0;
            }

            return (float) DB::table('leads')->where('status', 1)->sum('lead_value');
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function safeAvgScore(): float
    {
        try {
            if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'score')) {
                return 0.0;
            }

            return round((float) DB::table('leads')->where('status', 1)->avg('score'), 1);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    /**
     * @return array<int, array{name: string, count: int}>
     */
    private function leadsBySource(): array
    {
        try {
            if (! Schema::hasTable('leads') || ! Schema::hasTable('lead_sources')) {
                return [];
            }

            return DB::table('leads')
                ->leftJoin('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
                ->selectRaw('COALESCE(lead_sources.name, ?) as name, COUNT(*) as count', ['Unspecified'])
                ->groupBy('lead_sources.name')
                ->orderByDesc('count')
                ->limit(12)
                ->get()
                ->map(fn ($row) => ['name' => (string) $row->name, 'count' => (int) $row->count])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
