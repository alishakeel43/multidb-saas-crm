<?php

namespace Webkul\Consent\Providers;

use Webkul\Consent\Models\PersonConsent;
use Webkul\Core\Providers\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        PersonConsent::class,
    ];
}
