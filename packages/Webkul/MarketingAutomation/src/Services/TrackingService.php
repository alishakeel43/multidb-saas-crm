<?php

namespace Webkul\MarketingAutomation\Services;

use Symfony\Component\HttpFoundation\Response;
use Webkul\MarketingAutomation\Models\EmailMessage;
use Webkul\MarketingAutomation\Support\CrmEntity;

class TrackingService
{
    public function __construct(private EngagementScorer $engagement) {}

    public function open(string $token): Response
    {
        $message = EmailMessage::query()->where('token', $token)->first();

        if ($message && $message->opened_at === null && ! in_array($message->status, ['failed', 'suppressed', 'bounced'], true)) {
            $message->forceFill([
                'opened_at' => now(),
                'status' => $message->status === 'clicked' ? 'clicked' : 'opened',
            ])->save();

            $this->score($message, 'email_open');
        }

        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function click(string $token): Response
    {
        $message = EmailMessage::query()->where('token', $token)->first();

        if (! $message || ! $message->link_url) {
            abort(404);
        }

        if ($message->clicked_at === null && ! in_array($message->status, ['failed', 'suppressed', 'bounced'], true)) {
            $message->forceFill([
                'clicked_at' => now(),
                'opened_at' => $message->opened_at ?? now(),
                'status' => 'clicked',
            ])->save();

            $this->score($message, 'email_click');
        }

        return redirect()->away($message->link_url);
    }

    private function score(EmailMessage $message, string $eventType): void
    {
        $leadId = (int) ($message->lead_id ?? 0);

        if ($leadId < 1) {
            $leadId = (int) (CrmEntity::latestLeadId($message->person_id ? (int) $message->person_id : null) ?? 0);
        }

        if ($leadId < 1) {
            return;
        }

        $this->engagement->record($leadId, $eventType, 'message:'.$message->id.':'.$eventType);
    }
}
