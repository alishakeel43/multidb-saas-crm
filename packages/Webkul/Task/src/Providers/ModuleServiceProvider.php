<?php

namespace Webkul\Task\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\Task\Models\Task;
use Webkul\Task\Models\TaskStage;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Task::class,
        TaskStage::class,
    ];
}
