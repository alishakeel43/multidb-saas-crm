<?php

namespace Webkul\UnifiedInbox\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\UnifiedInbox\Contracts\ChannelConnection;

class ChannelConnectionRepository extends Repository
{
    public function model(): string
    {
        return ChannelConnection::class;
    }

    /**
     * The account to show for a channel. A newer connected row wins over an older disconnected one.
     */
    public function preferredForChannel(Collection $connections, string $channel): mixed
    {
        $matches = $connections
            ->where('channel', $channel)
            ->sortByDesc(fn ($connection) => (int) $connection->id)
            ->values();

        return $matches->firstWhere('status', 'connected') ?? $matches->first();
    }
}
