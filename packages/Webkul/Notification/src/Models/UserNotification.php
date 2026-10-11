<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Notification\Contracts\UserNotification as UserNotificationContract;
use Webkul\User\Models\UserProxy;

class UserNotification extends Model implements UserNotificationContract
{
    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'link',
        'entity_type',
        'entity_id',
        'meta',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
