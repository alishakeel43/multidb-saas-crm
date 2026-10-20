<?php

namespace Webkul\UnifiedInbox\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\UnifiedInbox\Contracts\Conversation;

class ConversationRepository extends Repository
{
    public function __construct(
        protected MessageRepository $messageRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    public function model(): string
    {
        return Conversation::class;
    }

    /**
     * "Delete chat": remove every message's content and hide the conversation until the customer writes again.
     * Rows are kept as tombstones so webhooks and Messenger/Instagram history sync never re-import them;
     * CRM links, notes and the customer's copy on Meta are not affected.
     */
    public function clear($conversation, ?int $userId = null)
    {
        DB::connection($conversation->getConnectionName())->transaction(function () use ($conversation, $userId) {
            $conversation->messages()->lazyById(200)->each(
                fn ($message) => $this->messageRepository->tombstone($message, 'agent', $userId)
            );

            $conversation->update([
                'cleared_at' => now(),
                'unread_count' => 0,
                'last_message_preview' => null,
            ]);
        });

        return $conversation;
    }
}
