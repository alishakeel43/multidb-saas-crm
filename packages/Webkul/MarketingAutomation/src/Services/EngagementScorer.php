<?php

namespace Webkul\MarketingAutomation\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\MarketingAutomation\Models\EngagementEvent;
use Webkul\MarketingAutomation\Models\EngagementRule;
use Webkul\MarketingAutomation\Models\EngagementScore;

class EngagementScorer
{
    public function record(int $leadId, string $eventType, string $sourceKey): int
    {
        if ($leadId < 1 || ! Schema::hasTable('ma_engagement_events')) {
            return 0;
        }

        $already = EngagementEvent::query()
            ->where('lead_id', $leadId)
            ->where('event_type', $eventType)
            ->where('source_key', $sourceKey)
            ->exists();

        if ($already) {
            return $this->pointsFor($leadId);
        }

        $points = (int) EngagementRule::query()
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->sum('points');

        $hasRule = EngagementRule::query()
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->exists();

        if (! $hasRule) {
            return $this->pointsFor($leadId);
        }

        EngagementEvent::query()->create([
            'lead_id' => $leadId,
            'event_type' => $eventType,
            'source_key' => $sourceKey,
            'points' => $points,
        ]);

        $score = EngagementScore::query()->firstOrCreate(
            ['lead_id' => $leadId],
            ['points' => 0],
        );

        if ($points !== 0) {
            $score->increment('points', $points);
        }

        return $this->pointsFor($leadId);
    }

    public function pointsFor(int $leadId): int
    {
        if (! Schema::hasTable('ma_engagement_scores')) {
            return 0;
        }

        return (int) EngagementScore::query()->where('lead_id', $leadId)->value('points');
    }
}
