<?php

namespace Webkul\LeadScoring\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\LeadScoring\Contracts\LeadScoringRule;

class LeadScoringRuleRepository extends Repository
{
    public function model(): string
    {
        return LeadScoringRule::class;
    }
}
