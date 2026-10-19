<?php

namespace Webkul\UnifiedInbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Activity\Models\ActivityProxy;
use Webkul\Contact\Models\OrganizationProxy;
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
        'organization_id',
        'metadata',
        'is_follow_up',
        'is_spam',
        'labels',
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'labels' => 'array',
            'is_follow_up' => 'boolean',
            'is_spam' => 'boolean',
            'last_message_at' => 'datetime',
            'last_incoming_at' => 'datetime',
        ];
    }

    /**
     * WhatsApp customer service window: 24h after the last inbound message.
     */
    public function serviceWindowEndsAt(): ?\Carbon\Carbon
    {
        return $this->last_incoming_at?->copy()->addHours(24);
    }

    public function serviceWindowOpen(): bool
    {
        $ends = $this->serviceWindowEndsAt();

        return $ends !== null && $ends->isFuture();
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationProxy::modelClass(), 'organization_id');
    }

    /**
     * Krayin activities (internal notes) written inside this conversation.
     */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(ActivityProxy::modelClass(), 'unified_inbox_conversation_activities', 'conversation_id', 'activity_id');
    }
}
