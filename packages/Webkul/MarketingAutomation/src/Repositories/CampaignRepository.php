<?php

namespace Webkul\MarketingAutomation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MarketingAutomation\Contracts\Campaign;

class CampaignRepository extends Repository
{
    public function model(): string
    {
        return Campaign::class;
    }
}
