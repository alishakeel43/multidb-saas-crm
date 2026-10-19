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
        'display_phone_number',
        'connected_at',
        'disconnected_at',
    ];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
        ];
    }

    /**
     * Number history: stamp connect/disconnect dates and remember the display number.
     * Rows are never deleted, so a disconnected row is the archive record of an old number.
     */
    protected static function booted(): void
    {
        static::saving(function (self $connection) {
            if (! static::supportsHistory()) {
                return;
            }

            if ($connection->isDirty('status') || ! $connection->exists) {
                if ($connection->status === 'connected') {
                    $connection->connected_at = now();
                    $connection->disconnected_at = null;
                } elseif ($connection->exists && $connection->getOriginal('status') === 'connected') {
                    $connection->disconnected_at = now();
                }
            }

            if (blank($connection->display_phone_number)) {
                $display = (string) (($connection->credentials ?? [])['display_phone_number'] ?? '');
                if ($display !== '') {
                    $connection->display_phone_number = $display;
                }
            }
        });
    }

    public static function supportsHistory(): bool
    {
        // Cached per tenant database: tenants may be on different migration levels.
        static $supported = [];

        try {
            $model = new static;
            $key = $model->getConnection()->getName().'|'.$model->getConnection()->getDatabaseName();

            return $supported[$key] ??= $model->getConnection()->getSchemaBuilder()->hasColumn('unified_inbox_connections', 'disconnected_at');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Human label for a number/account: display number, else stored credential number, else name.
     */
    public function numberLabel(): string
    {
        $display = $this->display_phone_number ?: (($this->credentials ?? [])['display_phone_number'] ?? '');

        return $display !== '' ? $display : (string) $this->name;
    }

    public function usable(): bool
    {
        return $this->status === 'connected'
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
