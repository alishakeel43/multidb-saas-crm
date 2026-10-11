<?php

namespace Webkul\Assignment\Services;

use Illuminate\Support\Collection;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;

class LeadDuplicateService
{
    /**
     * Find likely duplicate leads by shared person, email, or phone.
     *
     * @return Collection<int, Lead>
     */
    public function findDuplicates(?int $personId = null, ?string $email = null, ?string $phone = null, ?int $excludeLeadId = null): Collection
    {
        $personIds = collect();

        if ($personId) {
            $personIds->push($personId);
        }

        if (filled($email) || filled($phone)) {
            $matched = Person::query()
                ->when($email, function ($query) use ($email) {
                    $query->where('emails', 'like', '%'.$email.'%');
                })
                ->when($phone, function ($query) use ($phone) {
                    $query->orWhere('contact_numbers', 'like', '%'.$phone.'%');
                })
                ->pluck('id');

            $personIds = $personIds->merge($matched);
        }

        $personIds = $personIds->filter()->unique()->values();

        if ($personIds->isEmpty()) {
            return collect();
        }

        return Lead::query()
            ->with(['person', 'user'])
            ->whereIn('person_id', $personIds)
            ->when($excludeLeadId, fn ($query) => $query->where('id', '!=', $excludeLeadId))
            ->where('status', 1)
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }
}
