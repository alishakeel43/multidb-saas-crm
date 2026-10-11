<?php

namespace Webkul\Assignment\Providers;

use Webkul\Assignment\Models\AssignmentCursor;
use Webkul\Assignment\Models\AssignmentRule;
use Webkul\Core\Providers\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        AssignmentRule::class,
        AssignmentCursor::class,
    ];
}
