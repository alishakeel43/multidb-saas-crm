<?php

namespace Webkul\Notification\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\Notification\Models\UserNotification;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        UserNotification::class,
    ];
}
