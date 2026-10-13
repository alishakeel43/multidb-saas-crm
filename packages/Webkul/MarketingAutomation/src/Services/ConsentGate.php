<?php

namespace Webkul\MarketingAutomation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConsentGate
{
    public function suppressed(?int $personId): bool
    {
        if (! $personId || ! Schema::hasTable('person_consents')) {
            return false;
        }

        $rows = DB::table('person_consents')
            ->where('person_id', $personId)
            ->whereIn('channel', ['email', 'marketing'])
            ->get();

        foreach ($rows as $row) {
            if (! $row->is_opted_in) {
                return true;
            }
        }

        return false;
    }

    public function optOut(?int $personId, string $source = 'unsubscribe'): void
    {
        if (! $personId || ! Schema::hasTable('person_consents')) {
            return;
        }

        $now = now();

        foreach (['email', 'marketing'] as $channel) {
            $existing = DB::table('person_consents')
                ->where('person_id', $personId)
                ->where('channel', $channel)
                ->first();

            $payload = [
                'is_opted_in' => false,
                'source' => $source,
                'captured_at' => $now,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('person_consents')->where('id', $existing->id)->update($payload);

                continue;
            }

            DB::table('person_consents')->insert($payload + [
                'person_id' => $personId,
                'channel' => $channel,
                'created_at' => $now,
            ]);
        }
    }
}
