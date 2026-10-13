<?php

namespace Webkul\AiKnowledge\Providers;

use Webkul\AiKnowledge\Models\KnowledgeDocument;
use Webkul\Core\Providers\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        KnowledgeDocument::class,
    ];
}
