<?php

namespace Webkul\MarketingAutomation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\MarketingAutomation\Models\Audience;
use Webkul\MarketingAutomation\Support\CrmEntity;

class AudienceMatcher
{
    public function __construct(private ConditionEvaluator $conditions) {}

    /**
     * @return list<int>
     */
    public function matchingPersonIds(?Audience $audience): array
    {
        if (! Schema::hasTable('persons')) {
            return [];
        }

        $rules = $audience?->rules ?? [];

        return DB::table('persons')
            ->select(['id', 'name', 'job_title', 'emails'])
            ->orderBy('id')
            ->get()
            ->filter(function ($person) use ($rules): bool {
                if ($rules === []) {
                    return true;
                }

                return $this->conditions->matches('and', $rules, [
                    'name' => (string) ($person->name ?? ''),
                    'job_title' => (string) ($person->job_title ?? ''),
                    'email' => implode(' ', CrmEntity::emailList($person->emails)),
                ]);
            })
            ->map(fn ($person): int => (int) $person->id)
            ->values()
            ->all();
    }
}
