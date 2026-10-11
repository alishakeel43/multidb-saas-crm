<?php

namespace Webkul\Consent\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\Consent\Models\PersonConsent;
use Webkul\Consent\Repositories\PersonConsentRepository;
use Webkul\Contact\Contracts\Person;

class ConsentService
{
    public const CHANNELS = ['email', 'sms', 'phone', 'whatsapp', 'marketing'];

    public function __construct(
        protected PersonConsentRepository $personConsentRepository,
    ) {}

    public function set(Person $person, string $channel, bool $optedIn, ?string $source = null, ?string $notes = null): PersonConsent
    {
        $existing = $this->personConsentRepository
            ->findWhere([
                'person_id' => $person->id,
                'channel' => $channel,
            ])
            ->first();

        $payload = [
            'person_id' => $person->id,
            'channel' => $channel,
            'is_opted_in' => $optedIn,
            'source' => $source,
            'changed_by' => Auth::guard('user')->id(),
            'captured_at' => now(),
            'notes' => $notes,
        ];

        $consent = $existing
            ? $this->personConsentRepository->update($payload, $existing->id)
            : $this->personConsentRepository->create($payload);

        $this->audit($person, $channel, $optedIn, $source);

        return $consent;
    }

    public function isOptedIn(Person $person, string $channel): bool
    {
        return (bool) $this->personConsentRepository
            ->findWhere([
                'person_id' => $person->id,
                'channel' => $channel,
                'is_opted_in' => 1,
            ])
            ->first();
    }

    public function canMarket(Person $person, string $channel = 'email'): bool
    {
        $row = $this->personConsentRepository
            ->findWhere([
                'person_id' => $person->id,
                'channel' => $channel,
            ])
            ->first();

        if (! $row) {
            return false;
        }

        return (bool) $row->is_opted_in;
    }

    private function audit(Person $person, string $channel, bool $optedIn, ?string $source): void
    {
        try {
            if (! Schema::hasTable('activity_log')) {
                return;
            }

            activity('consent')
                ->performedOn($person)
                ->withProperties([
                    'channel' => $channel,
                    'is_opted_in' => $optedIn,
                    'source' => $source,
                ])
                ->log($optedIn ? 'consent.opt_in' : 'consent.opt_out');
        } catch (Throwable $exception) {
            Log::info('consent.audit_skipped', [
                'person_id' => $person->id,
                'channel' => $channel,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
