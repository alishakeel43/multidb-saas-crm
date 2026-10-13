<?php

namespace Webkul\MarketingAutomation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MarketingAutomation\Contracts\Workflow;

class WorkflowRepository extends Repository
{
    public function model(): string
    {
        return Workflow::class;
    }
}
