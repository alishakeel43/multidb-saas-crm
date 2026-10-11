<?php

namespace Webkul\UnifiedInbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\UnifiedInbox\Contracts\Message as MessageContract;

class Message extends Model implements MessageContract
{
    protected $table = 'unified_inbox_messages';

    protected $fillable = [
        'conversation_id',
        'direction',
        'external_id',
        'body',
        'status',
        'attachments',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ConversationProxy::modelClass(), 'conversation_id');
    }
}
