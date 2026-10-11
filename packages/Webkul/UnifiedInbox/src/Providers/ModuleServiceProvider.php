<?php

namespace Webkul\UnifiedInbox\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\UnifiedInbox\Models\ChannelConnection;
use Webkul\UnifiedInbox\Models\Conversation;
use Webkul\UnifiedInbox\Models\Message;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        ChannelConnection::class,
        Conversation::class,
        Message::class,
    ];
}
