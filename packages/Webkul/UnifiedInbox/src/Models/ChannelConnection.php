<?php

namespace Webkul\UnifiedInbox\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\UnifiedInbox\Contracts\ChannelConnection as ChannelConnectionContract;

class ChannelConnection extends Model implements ChannelConnectionContract
{
    protected $table = 'unified_inbox_connections';

    protected $fillable = [
        'channel',
        'external_id',
        'name',
        'status',
        'credentials',
        'metadata',
        'expires_at',
    ];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function usable(): bool
    {
        return $this->status === 'connected'
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
