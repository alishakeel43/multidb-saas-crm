<?php

namespace Webkul\QuoteApproval\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\QuoteApproval\Models\QuoteApproval;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        QuoteApproval::class,
    ];
}
