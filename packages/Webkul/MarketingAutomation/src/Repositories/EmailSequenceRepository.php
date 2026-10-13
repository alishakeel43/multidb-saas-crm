<?php

namespace Webkul\MarketingAutomation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MarketingAutomation\Contracts\EmailSequence;

class EmailSequenceRepository extends Repository
{
    public function model(): string
    {
        return EmailSequence::class;
    }
}
