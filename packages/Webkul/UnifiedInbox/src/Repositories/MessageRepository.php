<?php

namespace Webkul\UnifiedInbox\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\UnifiedInbox\Contracts\Message;

class MessageRepository extends Repository
{
    public function model(): string
    {
        return Message::class;
    }
}
