<?php

namespace Webkul\MarketingAutomation\Services;

use Webkul\MarketingAutomation\Models\Attribution;
use Webkul\MarketingAutomation\Models\Campaign;
use Webkul\MarketingAutomation\Models\EmailEnrollment;
use Webkul\MarketingAutomation\Models\EmailMessage;
use Webkul\MarketingAutomation\Models\EmailSequence;

class CampaignReport
{
    /**
     * @return array<string, int|string|float>
     */
    public function summarize(Campaign $campaign): array
    {
        $sequenceIds = EmailSequence::query()->where('campaign_id', $campaign->id)->pluck('id');
        $enrollmentIds = EmailEnrollment::query()->whereIn('sequence_id', $sequenceIds)->pluck('id');
        $messages = EmailMessage::query()->whereIn('enrollment_id', $enrollmentIds);

        $budget = (float) $campaign->budget;
        $spent = (float) $campaign->spent;

        return [
            'leads' => Attribution::query()
                ->where('campaign_id', $campaign->id)
                ->whereNotNull('lead_id')
                ->distinct()
                ->count('lead_id'),
            'budget' => $budget,
            'spent' => $spent,
            'remaining' => round($budget - $spent, 2),
            'sent' => (clone $messages)->whereNotNull('sent_at')->count(),
            'opened' => (clone $messages)->whereNotNull('opened_at')->count(),
            'clicked' => (clone $messages)->whereNotNull('clicked_at')->count(),
            'bounced' => (clone $messages)->where('status', 'bounced')->count(),
            'failed' => (clone $messages)->where('status', 'failed')->count(),
            'unsubscribed' => (clone $messages)->where('status', 'unsubscribed')->count(),
            'suppressed' => (clone $messages)->where('status', 'suppressed')->count(),
        ];
    }
}
