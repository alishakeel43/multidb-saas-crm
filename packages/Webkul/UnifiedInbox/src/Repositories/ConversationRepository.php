<?php

namespace Webkul\UnifiedInbox\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\UnifiedInbox\Contracts\Conversation;

class ConversationRepository extends Repository
{
    public function model(): string
    {
        return Conversation::class;
    }
}
