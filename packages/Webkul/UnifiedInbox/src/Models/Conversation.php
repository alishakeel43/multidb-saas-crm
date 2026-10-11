<?php

namespace Webkul\UnifiedInbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\UnifiedInbox\Contracts\Conversation as ConversationContract;
use Webkul\User\Models\UserProxy;

class Conversation extends Model implements ConversationContract
{
    protected $table = 'unified_inbox_conversations';

    protected $fillable = [
        'channel',
        'account_id',
        'external_id',
        'customer_name',
        'user_id',
        'status',
        'unread_count',
        'last_message_preview',
        'last_message_at',
        'last_incoming_at',
        'person_id',
        'lead_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_message_at' => 'datetime',
            'last_incoming_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MessageProxy::modelClass(), 'conversation_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'person_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'user_id');
    }
}
