<?php

namespace Webkul\LeadScoring\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\Lead\Contracts\Lead;
use Webkul\LeadScoring\Models\LeadScoringRule;

class LeadScoringService
{
    public function score(Lead $lead): int
    {
        if (! Schema::hasTable('lead_scoring_rules')) {
            return (int) ($lead->score ?? 0);
        }

        $total = 0;

        $rules = LeadScoringRule::query()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matches($lead, $rule)) {
                $total += (int) $rule->points;
            }
        }

        if (Schema::hasColumn('leads', 'score')) {
            $lead->forceFill(['score' => $total])->saveQuietly();
        }

        return $total;
    }

    private function matches(Lead $lead, LeadScoringRule $rule): bool
    {
        $actual = data_get($lead, $rule->field);
        $expected = $rule->value;

        return match ($rule->operator) {
            'eq' => (string) $actual === (string) $expected,
            'neq' => (string) $actual !== (string) $expected,
            'gt' => (float) $actual > (float) $expected,
            'gte' => (float) $actual >= (float) $expected,
            'lt' => (float) $actual < (float) $expected,
            'lte' => (float) $actual <= (float) $expected,
            'contains' => str_contains(strtolower((string) $actual), strtolower((string) $expected)),
            default => false,
        };
    }
}
