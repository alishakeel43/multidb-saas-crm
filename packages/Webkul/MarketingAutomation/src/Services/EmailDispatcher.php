<?php

namespace Webkul\MarketingAutomation\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;
use Webkul\MarketingAutomation\Mail\SequenceMail;
use Webkul\MarketingAutomation\Models\EmailEnrollment;
use Webkul\MarketingAutomation\Models\EmailMessage;
use Webkul\MarketingAutomation\Support\CrmEntity;

class EmailDispatcher
{
    public function __construct(private ConsentGate $consent) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  array{enrollment_id?: int|null, step_id?: int|null, unsubscribe_token?: string|null}  $links
     */
    public function send(array $context, string $subject, string $body, ?string $linkUrl, ?string $linkLabel, array $links = []): ?EmailMessage
    {
        $personId = isset($context['person_id']) ? (int) $context['person_id'] : null;
        $email = $context['email'] ?? CrmEntity::emailForPersonId($personId);

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $safeLink = $this->safeUrl($linkUrl);
        $token = Str::random(40);
        $message = EmailMessage::query()->create([
            'enrollment_id' => $links['enrollment_id'] ?? null,
            'step_id' => $links['step_id'] ?? null,
            'person_id' => $personId,
            'lead_id' => $context['lead_id'] ?? null,
            'email' => $email,
            'subject' => $subject,
            'link_url' => $safeLink,
            'token' => $token,
            'status' => 'queued',
            'scheduled_at' => now(),
        ]);

        if ($this->consent->suppressed($personId)) {
            $message->forceFill(['status' => 'suppressed'])->save();

            return $message;
        }

        $unsubscribeToken = $links['unsubscribe_token'] ?? $token;

        try {
            Mail::to($email)->send(new SequenceMail(
                bodyText: $body,
                linkUrl: $safeLink,
                linkLabel: $linkLabel,
                openUrl: route('marketing_automation.track.open', ['token' => $token]),
                clickUrl: route('marketing_automation.track.click', ['token' => $token]),
                unsubscribeUrl: route('marketing_automation.track.unsubscribe', ['token' => $unsubscribeToken]),
                subjectLine: $subject,
            ));

            $message->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            Log::warning('marketing_automation.mail_failed', [
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);

            $message->forceFill([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ])->save();
        }

        return $message->fresh();
    }

    public function unsubscribe(string $token): bool
    {
        $enrollment = EmailEnrollment::query()->where('unsubscribe_token', $token)->first();
        $message = EmailMessage::query()->where('token', $token)->first();
        $personId = $enrollment->person_id ?? $message->person_id ?? null;

        if (! $enrollment && ! $message) {
            return false;
        }

        $this->consent->optOut($personId ? (int) $personId : null);

        if ($enrollment && $enrollment->status !== 'unsubscribed') {
            $enrollment->forceFill([
                'status' => 'unsubscribed',
                'next_run_at' => null,
            ])->save();
        }

        if ($message?->enrollment && $message->enrollment->status !== 'unsubscribed') {
            $message->enrollment->forceFill([
                'status' => 'unsubscribed',
                'next_run_at' => null,
            ])->save();
        }

        if ($message && $message->status !== 'unsubscribed') {
            $message->forceFill(['status' => 'unsubscribed'])->save();
        }

        $leadId = (int) ($message->lead_id ?? 0);

        if ($leadId < 1 && $personId) {
            $leadId = (int) (CrmEntity::latestLeadId((int) $personId) ?? 0);
        }

        if ($leadId > 0) {
            app(EngagementScorer::class)->record($leadId, 'unsubscribe', 'unsubscribe:'.$token);
        }

        return true;
    }

    private function safeUrl(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
