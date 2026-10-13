<?php

namespace Webkul\MarketingAutomation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MarketingAutomation\Contracts\EngagementRule;

class EngagementRuleRepository extends Repository
{
    public function model(): string
    {
        return EngagementRule::class;
    }
}
