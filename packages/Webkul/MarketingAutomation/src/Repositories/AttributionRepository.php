<?php

namespace Webkul\MarketingAutomation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MarketingAutomation\Contracts\Attribution;

class AttributionRepository extends Repository
{
    public function model(): string
    {
        return Attribution::class;
    }
}
