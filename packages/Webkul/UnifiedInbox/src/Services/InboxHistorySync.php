<?php

namespace Webkul\UnifiedInbox\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\UnifiedInbox\Models\ChannelConnection;
use Webkul\UnifiedInbox\Repositories\ConversationRepository;
use Webkul\UnifiedInbox\Repositories\MessageRepository;

class InboxHistorySync
{
    public function __construct(
        protected MetaGraphClient $meta,
        protected ConversationRepository $conversations,
        protected MessageRepository $messages,
    ) {}

    /**
     * Pull recent Messenger or Instagram threads into the tenant inbox.
     * WhatsApp Cloud API has no conversation history endpoint; those arrive by webhook.
     */
    public function sync(ChannelConnection $connection): int
    {
        $credentials = $connection->credentials ?? [];
        $token = (string) ($credentials['access_token'] ?? '');

        if ($token === '' || $connection->channel === 'whatsapp') {
            return 0;
        }

        $pageId = $connection->channel === 'instagram'
            ? (string) ($credentials['page_id'] ?? '')
            : (string) $connection->external_id;

        if ($pageId === '') {
            return 0;
        }

        $platform = $connection->channel === 'instagram' ? 'instagram' : 'messenger';

        try {
            $threads = $this->meta->getPageConversations($pageId, $platform, $token);
        } catch (Throwable $e) {
            Log::warning('Unified Inbox could not sync '.$connection->channel.' history.', [
                'reason' => $e->getMessage(),
            ]);

            return 0;
        }

        $stored = 0;
        $businessIds = array_filter([
            (string) $connection->external_id,
            (string) ($credentials['instagram_id'] ?? ''),
            (string) ($credentials['page_id'] ?? ''),
            $pageId,
        ]);

        foreach ($threads as $thread) {
            if (! is_array($thread)) {
                continue;
            }

            $stored += $this->storeThread($connection, $thread, $businessIds);
        }

        return $stored;
    }

    /**
     * @param  array<int, string>  $businessIds
     */
    protected function storeThread(ChannelConnection $connection, array $thread, array $businessIds): int
    {
        $participants = $thread['participants']['data'] ?? [];
        $customer = collect($participants)->first(function ($participant) use ($businessIds) {
            return is_array($participant) && ! in_array((string) ($participant['id'] ?? ''), $businessIds, true);
        });

        $customerId = (string) ($customer['id'] ?? '');

        if ($customerId === '') {
            return 0;
        }

        $customerName = (string) ($customer['name'] ?? $customer['username'] ?? ucfirst($connection->channel).' User');
        $rows = array_reverse((array) ($thread['messages']['data'] ?? []));
        $stored = 0;

        $conversation = $this->conversations->getModel()->newQuery()->firstOrCreate(
            [
                'channel' => $connection->channel,
                'account_id' => (string) $connection->external_id,
                'external_id' => $customerId,
            ],
            [
                'customer_name' => $customerName,
                'status' => 'open',
                'unread_count' => 0,
            ]
        );

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $externalId = (string) ($row['id'] ?? '');

            if ($externalId === '' || $conversation->messages()->where('external_id', $externalId)->exists()) {
                continue;
            }

            $fromId = (string) ($row['from']['id'] ?? '');
            $occurredAt = $this->time($row['created_time'] ?? null);
            $body = trim((string) ($row['message'] ?? ''));

            if ($body === '') {
                $body = '['.ucfirst($connection->channel).' message]';
            }

            $this->messages->create([
                'conversation_id' => $conversation->id,
                'direction' => in_array($fromId, $businessIds, true) ? 'outbound' : 'inbound',
                'external_id' => $externalId,
                'body' => $body,
                'status' => in_array($fromId, $businessIds, true) ? 'sent' : 'received',
                'occurred_at' => $occurredAt,
            ]);

            $stored++;

            if (! $conversation->last_message_at || $occurredAt->gte($conversation->last_message_at)) {
                $conversation->last_message_preview = $body;
                $conversation->last_message_at = $occurredAt;
            }
        }

        if ($conversation->customer_name === '' || str_ends_with($conversation->customer_name, ' User')) {
            $conversation->customer_name = $customerName;
        }

        $conversation->save();

        return $stored;
    }

    protected function time(mixed $value): Carbon
    {
        if (! is_string($value) || $value === '') {
            return now();
        }

        return Carbon::parse($value)->min(now());
    }
}
