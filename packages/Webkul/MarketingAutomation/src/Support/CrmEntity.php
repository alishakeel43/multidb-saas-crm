<?php

namespace Webkul\MarketingAutomation\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Activity\Models\Activity;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Quote\Models\Quote;

class CrmEntity
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *     entity_type: string,
     *     entity_id: int,
     *     user_id: int|null,
     *     lead_id: int|null,
     *     person_id: int|null,
     *     email: string|null,
     *     attributes: array<string, mixed>
     * }
     */
    public static function context(object $entity): ?array
    {
        $type = self::typeOf($entity);

        if ($type === null) {
            return null;
        }

        $raw = method_exists($entity, 'getAttributes') ? $entity->getAttributes() : [];
        $id = (int) ($raw['id'] ?? 0);

        if ($id < 1) {
            return null;
        }

        $personId = $type === 'persons' ? $id : (isset($raw['person_id']) ? (int) $raw['person_id'] : null);
        $leadId = $type === 'leads' ? $id : (isset($raw['lead_id']) ? (int) $raw['lead_id'] : null);
        $attributes = [];

        foreach (array_keys(Catalog::ATTRIBUTES[$type] ?? []) as $key) {
            $attributes[$key] = $raw[$key] ?? null;
        }

        return [
            'entity_type' => $type,
            'entity_id' => $id,
            'user_id' => isset($raw['user_id']) ? (int) $raw['user_id'] : null,
            'lead_id' => $leadId ?: null,
            'person_id' => $personId ?: null,
            'email' => self::resolveEmail($type, $raw, $personId ?: null),
            'attributes' => $attributes,
        ];
    }

    public static function typeOf(object $entity): ?string
    {
        return match (true) {
            is_a($entity, Lead::class) => 'leads',
            is_a($entity, Person::class) => 'persons',
            is_a($entity, Activity::class) => 'activities',
            is_a($entity, Quote::class) => 'quotes',
            default => null,
        };
    }

    public static function latestLeadId(?int $personId): ?int
    {
        if (! $personId || ! Schema::hasTable('leads')) {
            return null;
        }

        $id = DB::table('leads')->where('person_id', $personId)->orderByDesc('id')->value('id');

        return $id ? (int) $id : null;
    }

    public static function emailForPersonId(?int $personId): ?string
    {
        if (! $personId || ! Schema::hasTable('persons')) {
            return null;
        }

        $row = DB::table('persons')->where('id', $personId)->first();

        if (! $row) {
            return null;
        }

        return self::firstEmail($row->emails ?? null);
    }

    public static function firstEmail(mixed $emails): ?string
    {
        $list = self::emailList($emails);

        return $list[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function emailList(mixed $emails): array
    {
        if (is_string($emails)) {
            $decoded = json_decode($emails, true);
            $emails = is_array($decoded) ? $decoded : [$emails];
        }

        if (! is_array($emails)) {
            return [];
        }

        $found = [];

        foreach ($emails as $email) {
            $value = is_array($email) ? ($email['value'] ?? null) : $email;

            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $found[] = $value;
            }
        }

        return $found;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private static function resolveEmail(string $type, array $raw, ?int $personId): ?string
    {
        if ($type === 'persons') {
            return self::firstEmail($raw['emails'] ?? null);
        }

        return self::emailForPersonId($personId);
    }
}
