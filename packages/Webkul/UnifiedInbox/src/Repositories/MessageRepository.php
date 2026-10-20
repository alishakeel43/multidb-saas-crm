<?php

namespace Webkul\UnifiedInbox\Repositories;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;
use Webkul\UnifiedInbox\Contracts\Message;
use Webkul\UnifiedInbox\Models\Message as MessageModel;

class MessageRepository extends Repository
{
    public function model(): string
    {
        return Message::class;
    }

    /**
     * Remove a message's content from the inbox while keeping its row as a tombstone.
     * Meta has no "delete for everyone" API, so the customer's copy is not affected.
     *
     * @param  string  $source  agent (deleted in the CRM) or customer (unsent on Meta)
     */
    public function markDeleted($message, string $source = 'agent', ?int $userId = null)
    {
        if ($message->isDeleted()) {
            return $message;
        }

        $this->tombstone($message, $source, $userId);

        $conversation = $message->conversation;
        $latest = $conversation?->messages()->orderByDesc('occurred_at')->orderByDesc('id')->first();

        if ($latest && $latest->is($message)) {
            $conversation->update(['last_message_preview' => MessageModel::DELETED_BODY]);
        }

        return $message;
    }

    /**
     * Strip the body and stored attachments, keeping the row (and its external_id) for de-duplication.
     */
    public function tombstone($message, string $source = 'agent', ?int $userId = null): void
    {
        if ($message->isDeleted()) {
            return;
        }

        foreach ((array) ($message->attachments ?? []) as $attachment) {
            $path = is_array($attachment) ? (string) ($attachment['path'] ?? '') : '';

            if ($path !== '' && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }

        $message->update([
            'body' => MessageModel::DELETED_BODY,
            'attachments' => null,
            'metadata' => array_merge((array) ($message->metadata ?? []), array_filter([
                'deleted_at' => now()->toIso8601String(),
                'deleted_by' => $userId,
                'deleted_source' => $source,
            ], fn ($value) => $value !== null)),
        ]);
    }
}
