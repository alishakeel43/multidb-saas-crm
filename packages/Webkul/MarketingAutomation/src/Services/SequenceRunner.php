<?php

namespace Webkul\MarketingAutomation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\MarketingAutomation\Models\Audience;
use Webkul\MarketingAutomation\Models\EmailEnrollment;
use Webkul\MarketingAutomation\Models\EmailSequence;
use Webkul\MarketingAutomation\Support\CrmEntity;

class SequenceRunner
{
    public function __construct(
        private AudienceMatcher $audiences,
        private EmailDispatcher $mail,
        private ConsentGate $consent,
    ) {}

    public function enroll(EmailSequence $sequence): int
    {
        if ($sequence->status !== 'active' || ! Schema::hasTable('persons')) {
            return 0;
        }

        $sequence->loadMissing('campaign', 'steps');
        $audience = null;

        if ($sequence->audience_id) {
            $audience = Audience::query()->find($sequence->audience_id);
        } elseif ($sequence->campaign?->audience_id) {
            $audience = $sequence->campaign->audience;
        }

        $personIds = $audience
            ? $this->audiences->matchingPersonIds($audience)
            : DB::table('persons')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $firstDelay = (int) ($sequence->steps->sortBy('sort')->first()->delay_minutes ?? 0);
        $created = 0;

        foreach ($personIds as $personId) {
            if ($this->consent->suppressed($personId)) {
                continue;
            }

            $exists = EmailEnrollment::query()
                ->where('sequence_id', $sequence->id)
                ->where('person_id', $personId)
                ->whereIn('status', ['active', 'paused'])
                ->exists();

            if ($exists || ! CrmEntity::emailForPersonId($personId)) {
                continue;
            }

            EmailEnrollment::query()->create([
                'sequence_id' => $sequence->id,
                'person_id' => $personId,
                'status' => 'active',
                'current_step' => 0,
                'attempts' => 0,
                'next_run_at' => now()->addMinutes(max(0, $firstDelay)),
                'unsubscribe_token' => Str::random(40),
            ]);

            $created++;
        }

        return $created;
    }

    public function processDue(): int
    {
        if (! Schema::hasTable('ma_email_enrollments')) {
            return 0;
        }

        $sent = 0;

        $enrollments = EmailEnrollment::query()
            ->where('status', 'active')
            ->where('next_run_at', '<=', now())
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($enrollments as $enrollment) {
            $sequence = EmailSequence::query()->with('steps')->find($enrollment->sequence_id);

            if (! $sequence || $sequence->status !== 'active') {
                continue;
            }

            if ($this->consent->suppressed((int) $enrollment->person_id)) {
                $enrollment->forceFill([
                    'status' => 'unsubscribed',
                    'next_run_at' => null,
                ])->save();

                continue;
            }

            $steps = $sequence->steps->sortBy('sort')->values();
            $step = $steps->get((int) $enrollment->current_step);

            if (! $step) {
                $enrollment->forceFill([
                    'status' => 'completed',
                    'next_run_at' => null,
                ])->save();

                continue;
            }

            $personId = (int) $enrollment->person_id;
            $message = $this->mail->send(
                [
                    'entity_type' => 'persons',
                    'entity_id' => $personId,
                    'person_id' => $personId,
                    'lead_id' => CrmEntity::latestLeadId($personId),
                    'user_id' => null,
                    'email' => CrmEntity::emailForPersonId($personId),
                    'attributes' => [],
                ],
                $step->subject,
                $step->body,
                $step->link_url,
                $step->link_label,
                [
                    'enrollment_id' => $enrollment->id,
                    'step_id' => $step->id,
                    'unsubscribe_token' => $enrollment->unsubscribe_token,
                ],
            );

            if (! $message || $message->status === 'failed') {
                $attempts = (int) $enrollment->attempts + 1;
                $enrollment->forceFill([
                    'attempts' => $attempts,
                    'status' => $attempts >= 3 ? 'failed' : 'active',
                    'next_run_at' => $attempts >= 3 ? null : now()->addMinutes(30),
                ])->save();

                continue;
            }

            if ($message->status === 'suppressed') {
                $enrollment->forceFill([
                    'status' => 'unsubscribed',
                    'next_run_at' => null,
                ])->save();

                continue;
            }

            $nextIndex = (int) $enrollment->current_step + 1;
            $next = $steps->get($nextIndex);

            $enrollment->forceFill([
                'current_step' => $nextIndex,
                'attempts' => 0,
                'status' => $next ? 'active' : 'completed',
                'next_run_at' => $next ? now()->addMinutes(max(0, (int) $next->delay_minutes)) : null,
            ])->save();

            $sent++;
        }

        return $sent;
    }
}
