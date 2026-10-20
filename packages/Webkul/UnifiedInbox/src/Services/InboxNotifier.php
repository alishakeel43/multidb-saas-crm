<?php

namespace Webkul\UnifiedInbox\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\Notification\Models\UserNotification;
use Webkul\Notification\Services\NotificationService;
use Webkul\UnifiedInbox\Models\Conversation;
use Webkul\User\Models\UserProxy;

/**
 * Navbar bell notifications for new customer messages on every channel.
 * Each user keeps one unread notification per conversation, replaced by the latest message.
 */
class InboxNotifier
{
    public const TYPE = 'unified_inbox_message';

    public const ENTITY = 'unified_inbox_conversation';

    public function notifyInbound(Conversation $conversation, string $body, bool $hasAttachments = false): void
    {
        if (! $this->enabled() || $conversation->is_spam) {
            return;
        }

        $userIds = $this->recipients($conversation);

        if ($userIds === []) {
            return;
        }

        $channel = (string) $conversation->channel;
        $preview = trim($body);

        if ($preview === '' && $hasAttachments) {
            $preview = trans('unified_inbox::app.inbox.alert-attachment');
        }

        $title = trans('unified_inbox::app.notifications.new-message', [
            'channel' => config("unified-inbox.channels.{$channel}.label", ucfirst($channel)),
            'name' => $conversation->customer_name ?: $conversation->external_id,
        ]);

        // Relative link: webhooks arrive on the central host, but the bell is opened on the tenant host.
        $link = route('admin.unified_inbox.index', [
            'channel' => $channel,
            'conversation_id' => $conversation->id,
        ], false);

        $notifications = app(NotificationService::class);

        foreach ($userIds as $userId) {
            $this->unreadFor($userId, (int) $conversation->id)->delete();

            $notifications->notify(
                $userId,
                self::TYPE,
                $title,
                Str::limit($preview, 120),
                $link,
                ['channel' => $channel, 'unread' => (int) $conversation->unread_count],
                self::ENTITY,
                (int) $conversation->id,
            );
        }
    }

    public function markConversationRead(int $userId, int $conversationId): void
    {
        if ($userId > 0 && $this->enabled()) {
            $this->unreadFor($userId, $conversationId)->update(['read_at' => now()]);
        }
    }

    /**
     * The assigned agent, otherwise every active user allowed to open the inbox.
     *
     * @return array<int, int>
     */
    protected function recipients(Conversation $conversation): array
    {
        $query = UserProxy::modelClass()::query()
            ->with('role')
            ->where('status', 1);

        if ($conversation->user_id) {
            $query->whereKey($conversation->user_id);
        } else {
            $query = UserProxy::modelClass()::applySupportExclusion($query);
        }

        return $query->get()
            ->filter(fn ($user) => $user->role
                && ($user->role->permission_type === 'all' || in_array('unified_inbox', (array) $user->role->permissions, true)))
            ->map(fn ($user) => (int) $user->id)
            ->values()
            ->all();
    }

    protected function unreadFor(int $userId, int $conversationId)
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->where('type', self::TYPE)
            ->where('entity_type', self::ENTITY)
            ->where('entity_id', $conversationId)
            ->whereNull('read_at');
    }

    protected function enabled(): bool
    {
        return class_exists(NotificationService::class) && Schema::hasTable('user_notifications');
    }
}
