<?php

namespace Webkul\Assignment\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Assignment\Models\AssignmentCursor;
use Webkul\Assignment\Models\AssignmentRule;
use Webkul\Assignment\Repositories\AssignmentRuleRepository;
use Webkul\Lead\Contracts\Lead;
use Webkul\User\Models\User;

class LeadAssignmentService
{
    public function __construct(
        protected AssignmentRuleRepository $assignmentRuleRepository,
    ) {}

    public function assignIfNeeded(Lead $lead): ?User
    {
        $rule = $this->resolveRule($lead);

        if (! $rule) {
            return null;
        }

        if ($rule->only_when_empty && filled($lead->user_id)) {
            return null;
        }

        $user = $this->pickUser($rule);

        if (! $user) {
            return null;
        }

        $lead->user_id = $user->id;
        $lead->save();

        event('assignment.lead.assigned', [$lead, $user]);

        return $user;
    }

    public function resolveRule(Lead $lead): ?AssignmentRule
    {
        return $this->assignmentRuleRepository
            ->getModel()
            ->newQuery()
            ->where('is_active', true)
            ->where('entity_type', 'leads')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get()
            ->first(fn (AssignmentRule $rule): bool => $this->matchesConditions($rule, $lead));
    }

    public function pickUser(AssignmentRule $rule): ?User
    {
        $candidates = $this->activeCandidates($rule);

        if ($candidates->isEmpty()) {
            return null;
        }

        return match ($rule->strategy) {
            'least_workload' => $this->pickLeastWorkload($candidates),
            'first_available' => $candidates->first(),
            default => $this->pickRoundRobin($rule, $candidates),
        };
    }

    /**
     * @return Collection<int, User>
     */
    private function activeCandidates(AssignmentRule $rule): Collection
    {
        $query = $rule->users()
            ->where('users.status', 1)
            ->wherePivot('is_active', true)
            ->orderByPivot('sort_order')
            ->orderBy('users.id');

        $query = \Webkul\User\Models\User::applySupportExclusion($query);

        return $query->get();
    }

    private function pickRoundRobin(AssignmentRule $rule, Collection $candidates): User
    {
        return DB::transaction(function () use ($rule, $candidates): User {
            $cursor = AssignmentCursor::query()
                ->where('assignment_rule_id', $rule->id)
                ->lockForUpdate()
                ->first();

            if (! $cursor) {
                $cursor = AssignmentCursor::query()->create([
                    'assignment_rule_id' => $rule->id,
                    'last_user_id' => null,
                ]);
            }

            $ids = $candidates->pluck('id')->values();
            $next = $ids->first();

            if ($cursor->last_user_id) {
                $index = $ids->search((int) $cursor->last_user_id);

                if ($index !== false) {
                    $next = $ids[($index + 1) % $ids->count()];
                }
            }

            $cursor->update(['last_user_id' => $next]);

            return $candidates->firstWhere('id', $next);
        });
    }

    private function pickLeastWorkload(Collection $candidates): User
    {
        $counts = DB::table('leads')
            ->selectRaw('user_id, COUNT(*) as aggregate')
            ->whereIn('user_id', $candidates->pluck('id'))
            ->where('status', 1)
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        return $candidates
            ->sortBy(fn (User $user): int => (int) ($counts[$user->id] ?? 0))
            ->values()
            ->first();
    }

    private function matchesConditions(AssignmentRule $rule, Lead $lead): bool
    {
        $conditions = $rule->conditions ?? [];

        if ($conditions === [] || $conditions === null) {
            return true;
        }

        if (! empty($conditions['lead_source_ids'])) {
            if (! in_array((int) $lead->lead_source_id, array_map('intval', $conditions['lead_source_ids']), true)) {
                return false;
            }
        }

        if (! empty($conditions['lead_type_ids'])) {
            if (! in_array((int) $lead->lead_type_id, array_map('intval', $conditions['lead_type_ids']), true)) {
                return false;
            }
        }

        return true;
    }
}
