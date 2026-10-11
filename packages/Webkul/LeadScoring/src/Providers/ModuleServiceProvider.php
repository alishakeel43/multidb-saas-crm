<?php

namespace Webkul\LeadScoring\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\LeadScoring\Models\LeadScoringRule;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [LeadScoringRule::class];
}
