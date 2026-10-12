<?php

namespace Webkul\Telephony\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\Telephony\Models\CallLog;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        CallLog::class,
    ];
}
