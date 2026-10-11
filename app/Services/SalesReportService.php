<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Booking\Models\Booking;
use Webkul\Consent\Models\PersonConsent;
use Webkul\Lead\Models\Lead;
use Webkul\QuoteApproval\Models\QuoteApproval;

/**
 * Lightweight CRM sales metrics for P0 reporting hooks.
 */
class SalesReportService
{
    /**
     * @return array<string, int|float>
     */
    public function snapshot(): array
    {
        return [
            'open_leads' => $this->safeCount(fn () => Lead::query()->where('status', 1)->count()),
            'won_leads' => $this->safeCount(fn () => Lead::query()->whereNotNull('closed_at')->where('status', 0)->count()),
            'quotes_pending_approval' => $this->safeCount(fn () => QuoteApproval::query()->where('status', QuoteApproval::STATUS_PENDING)->count()),
            'quotes_approved' => $this->safeCount(fn () => QuoteApproval::query()->where('status', QuoteApproval::STATUS_APPROVED)->count()),
            'bookings_upcoming' => $this->safeCount(fn () => Booking::query()->where('starts_at', '>=', now())->where('status', '!=', 'cancelled')->count()),
            'consent_opt_ins' => $this->safeCount(fn () => PersonConsent::query()->where('is_opted_in', true)->count()),
            'lead_value_open' => $this->safeSum(),
        ];
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
}
