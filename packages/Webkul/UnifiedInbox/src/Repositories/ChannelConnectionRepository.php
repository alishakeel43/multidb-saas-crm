<?php

namespace Webkul\UnifiedInbox\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\UnifiedInbox\Contracts\ChannelConnection;

class ChannelConnectionRepository extends Repository
{
    public function model(): string
    {
        return ChannelConnection::class;
    }
}
