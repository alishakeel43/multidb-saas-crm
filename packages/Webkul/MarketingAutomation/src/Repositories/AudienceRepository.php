<?php

namespace Webkul\MarketingAutomation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MarketingAutomation\Contracts\Audience;

class AudienceRepository extends Repository
{
    public function model(): string
    {
        return Audience::class;
    }
}
