<?php

namespace Webkul\MarketingAutomation\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\MarketingAutomation\Models\Attribution;
use Webkul\MarketingAutomation\Models\Audience;
use Webkul\MarketingAutomation\Models\Campaign;
use Webkul\MarketingAutomation\Models\EmailSequence;
use Webkul\MarketingAutomation\Models\EngagementRule;
use Webkul\MarketingAutomation\Models\Workflow;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Workflow::class,
        Campaign::class,
        Audience::class,
        EmailSequence::class,
        EngagementRule::class,
        Attribution::class,
    ];
}
