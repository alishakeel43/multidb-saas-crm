<?php

namespace Webkul\UnifiedInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Webkul\Contact\Models\PersonProxy;
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;
use Webkul\UnifiedInbox\Repositories\ConversationRepository;
use Webkul\UnifiedInbox\Repositories\MessageRepository;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\UnifiedInbox\Services\InboxNotifier;
use Webkul\UnifiedInbox\Services\MetaGraphClient;

class WebhookController extends Controller
{
    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected MessageRepository $messageRepository,
        protected ChannelConnectionRepository $channelRepository,
        protected CentralWebhookRouter $centralRouter,
        protected MetaGraphClient $metaGraph
    ) {}

    /**
     * Webhook verification handshake for Meta Graph API.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode') ?: $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?: $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?: $request->query('hub.challenge');

        $configuredToken = (string) config('unified-inbox.meta.verify_token', '');

        if ($configuredToken !== '' && $mode === 'subscribe' && hash_equals($configuredToken, (string) $token) && filled($challenge)) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Centralized Inbound Meta webhook events processor (WhatsApp, Messenger, Instagram).
     * Resolves each event using its exact channel account ID before entering tenant context.
     */
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('unified-inbox.meta.app_secret', '');
        abort_if($secret === '', 503, 'Meta webhook signing is not configured.');
        abort_if(strlen($request->getContent()) > 1_048_576, 413);

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        abort_unless(hash_equals($expectedSignature, $signature), 403);

        $payload = json_decode($request->getContent(), true);
        abort_unless(is_array($payload), 400);

        $object = (string) ($payload['object'] ?? '');
        $entries = (array) ($payload['entry'] ?? []);

        foreach ($entries as $entry) {
            if ($object === 'whatsapp_business_account') {
                $this->routeAndProcessWhatsAppEntry($entry);
            } elseif (in_array($object, ['page', 'instagram'], true)) {
                $this->routeAndProcessMessagingEntry($entry, $object);
            }
        }

        return response()->json(['status' => 'EVENT_RECEIVED'], 200);
    }

    protected function routeAndProcessWhatsAppEntry(array $entry): void
    {
        $changes = (array) ($entry['changes'] ?? []);
        $wabaId = (string) ($entry['id'] ?? '');

        foreach ($changes as $change) {
            $value = (array) ($change['value'] ?? []);
            $phoneMetadata = (array) ($value['metadata'] ?? []);
            $phoneId = (string) ($phoneMetadata['phone_number_id'] ?? '');

            if ($phoneId === '') {
                Log::warning('Unified Inbox ignored WhatsApp webhook without a phone number ID.');

                continue;
            }

            $this->runInTenantContext(
                channel: 'whatsapp',
                externalId: $phoneId,
                wabaId: $wabaId,
                callback: fn () => $this->handleWhatsAppEntryChanges($change, $phoneId, $wabaId)
            );
        }
    }

    protected function routeAndProcessMessagingEntry(array $entry, string $object): void
    {
        $channel = $object === 'instagram' ? 'instagram' : 'messenger';

        // Route on entry.id (Page ID / IG account ID); fall back to messaging[].recipient.id
        // (the business side of an inbound event) when entry.id has no active route.
        $candidates = collect([(string) ($entry['id'] ?? '')])
            ->merge(collect((array) ($entry['messaging'] ?? []))->map(fn ($e) => (string) data_get($e, 'recipient.id', '')))
            ->filter()->unique()->values();

        $pageId = $candidates->first(fn ($id) => $this->centralRouter->resolveTenant($channel, $id) !== null)
            ?? (string) ($candidates->first() ?? '');

        $this->runInTenantContext(
            channel: $channel,
            externalId: $pageId,
            wabaId: null,
            callback: fn () => $this->handleMessagingEntry($entry, $object, $pageId)
        );
    }

    /**
     * Switch to the resolved Tenant context or execute safely.
     */
    protected function runInTenantContext(string $channel, string $externalId, ?string $wabaId, callable $callback): void
    {
        $targetTenant = $this->centralRouter->resolveTenant($channel, $externalId, $wabaId);

        if (! $targetTenant) {
            Log::warning("Unified Inbox webhook has no active tenant route for {$channel} account.", [
                'channel' => $channel,
                'external_id' => $externalId,
                'waba_id' => $wabaId,
            ]);

            return;
        }

        $targetTenant->execute(function () use ($channel, $externalId, $callback) {
            $connection = $this->channelRepository->findOneWhere([
                'channel' => $channel,
                'external_id' => $externalId,
                'status' => 'connected',
            ]);

            if (! $connection) {
                Log::warning("Unified Inbox webhook route has no connected {$channel} account in its tenant database.");

                return;
            }

            $callback();
        });
    }

    protected function handleWhatsAppEntryChanges(array $change, string $phoneId, string $wabaId): void
    {
        $value = (array) ($change['value'] ?? []);

        // Inbound messages
        if (! empty($value['messages'])) {
            $contacts = collect((array) ($value['contacts'] ?? []));

            foreach ($value['messages'] as $waMsg) {
                $senderWaId = (string) ($waMsg['from'] ?? '');
                if (! $senderWaId) {
                    continue;
                }

                $contact = $contacts->firstWhere('wa_id', $senderWaId);
                $senderName = $contact['profile']['name'] ?? "+{$senderWaId}";

                $messageBody = '';
                $attachments = [];
                $type = (string) ($waMsg['type'] ?? 'text');

                if ($type === 'text') {
                    $messageBody = (string) ($waMsg['text']['body'] ?? '');
                } elseif (in_array($type, ['image', 'audio', 'document', 'video', 'sticker'], true)) {
                    $media = (array) ($waMsg[$type] ?? []);
                    $label = ['image' => '[Image]', 'audio' => '[Voice Note]', 'document' => '[Document]', 'video' => '[Video]', 'sticker' => '[Sticker]'][$type];
                    $caption = (string) ($media['caption'] ?? '');
                    $messageBody = $type === 'document'
                        ? trim($label.' '.($media['filename'] ?? '').($caption !== '' ? ' — '.$caption : ''))
                        : trim($label.' '.$caption);
                    $attachments[] = array_filter([
                        'type' => $type,
                        'id' => $media['id'] ?? null,
                        'mime_type' => $media['mime_type'] ?? null,
                        'filename' => isset($media['filename']) ? basename((string) $media['filename']) : null,
                        'caption' => $caption !== '' ? $caption : null,
                    ], fn ($v) => $v !== null);
                } else {
                    $messageBody = "[{$type}]";
                }

                $externalMessageId = (string) ($waMsg['id'] ?? '');

                if ($externalMessageId === '') {
                    continue;
                }

                $this->storeInboundMessage(
                    channel: 'whatsapp',
                    accountId: $phoneId ?: 'whatsapp',
                    externalSenderId: $senderWaId,
                    customerName: $senderName,
                    externalMessageId: $externalMessageId,
                    body: $messageBody,
                    attachments: $attachments,
                    occurredAt: $this->timestamp($waMsg['timestamp'] ?? null)
                );
            }
        }

        // Status updates (delivered, read, failed)
        if (! empty($value['statuses'])) {
            foreach ($value['statuses'] as $statusUpdate) {
                $msgId = (string) ($statusUpdate['id'] ?? '');
                $status = (string) ($statusUpdate['status'] ?? '');

                if ($msgId && in_array($status, ['delivered', 'read', 'failed', 'sent'])) {
                    $msg = $this->messageRepository->getModel()->newQuery()
                        ->where('external_id', $msgId)
                        ->where('direction', 'outbound')
                        ->whereHas('conversation', fn ($query) => $query
                            ->where('channel', 'whatsapp')
                            ->where('account_id', $phoneId))
                        ->first();

                    if ($msg) {
                        $this->applyDeliveryStatus($msg, $status);
                    }
                }
            }
        }
    }

    protected function handleMessagingEntry(array $entry, string $object, ?string $accountId = null): void
    {
        $messagingEvents = (array) ($entry['messaging'] ?? []);
        $channel = $object === 'instagram' ? 'instagram' : 'messenger';
        $pageId = $accountId ?: (string) ($entry['id'] ?? '');

        foreach ($messagingEvents as $event) {
            if (! empty($event['message'])) {
                $senderId = (string) ($event['sender']['id'] ?? '');
                $messageData = (array) $event['message'];
                $messageId = (string) ($messageData['mid'] ?? '');
                $isEcho = (bool) ($messageData['is_echo'] ?? false);

                // The customer unsent this message on Messenger / Instagram.
                if (! empty($messageData['is_deleted'])) {
                    $this->markUnsentMessage($channel, $pageId, $messageId);

                    continue;
                }

                if ($isEcho && $messageId !== '') {
                    $this->updateOutboundOccurredAt(
                        $channel,
                        $pageId,
                        $messageId,
                        $this->timestamp($event['timestamp'] ?? null, milliseconds: true),
                    );
                }

                if ($senderId && $senderId !== $pageId && ! $isEcho) {
                    $body = (string) ($messageData['text'] ?? '');
                    $attachments = [];

                    if (! empty($messageData['attachments'])) {
                        foreach ($messageData['attachments'] as $att) {
                            $attType = $att['type'] ?? 'file';
                            $attUrl = $att['payload']['url'] ?? '';
                            $attachments[] = ['type' => $attType, 'url' => $attUrl];
                            if (! $body) {
                                $body = "[{$attType}]";
                            }
                        }
                    }

                    if ($messageId !== '') {
                        $profile = $this->resolveMessagingCustomerProfile($channel, $pageId, $senderId);
                        $this->storeInboundMessage(
                            channel: $channel,
                            accountId: $pageId,
                            externalSenderId: $senderId,
                            customerName: $profile['name'],
                            externalMessageId: $messageId,
                            body: $body ?: 'New message',
                            attachments: $attachments,
                            occurredAt: $this->timestamp($event['timestamp'] ?? null, milliseconds: true),
                            avatarUrl: $profile['profile_pic'] !== '' ? $profile['profile_pic'] : null,
                        );
                    }
                }
            }

            // Button / quick-reply postbacks: stored as an inbound message so agents see the choice.
            if (! empty($event['postback'])) {
                $senderId = (string) ($event['sender']['id'] ?? '');
                $postback = (array) $event['postback'];
                $title = trim((string) ($postback['title'] ?? ''));
                $payload = (string) ($postback['payload'] ?? '');

                if ($senderId !== '' && $senderId !== $pageId) {
                    $profile = $this->resolveMessagingCustomerProfile($channel, $pageId, $senderId);
                    $this->storeInboundMessage(
                        channel: $channel,
                        accountId: $pageId,
                        externalSenderId: $senderId,
                        customerName: $profile['name'],
                        externalMessageId: (string) ($postback['mid'] ?? '') ?: 'postback:'.sha1($senderId.'|'.($event['timestamp'] ?? '').'|'.$payload),
                        body: '[Button] '.($title !== '' ? $title : $payload),
                        attachments: [array_filter(['type' => 'postback', 'title' => $title ?: null, 'payload' => $payload !== '' ? mb_substr($payload, 0, 1000) : null])],
                        occurredAt: $this->timestamp($event['timestamp'] ?? null, milliseconds: true),
                        avatarUrl: $profile['profile_pic'] !== '' ? $profile['profile_pic'] : null,
                    );
                }
            }

            $recipientId = (string) ($event['recipient']['id'] ?? '');

            // Instagram messaging_seen: read.mid marks that message (and earlier ones) as read.
            $seenMid = (string) data_get($event, 'read.mid', '');
            $customerId = (string) ($event['sender']['id'] ?? '');

            if ($seenMid !== '' && $customerId !== '') {
                $seen = $this->messageRepository->getModel()->newQuery()
                    ->where('external_id', $seenMid)
                    ->where('direction', 'outbound')
                    ->whereHas('conversation', fn ($query) => $query
                        ->where('channel', $channel)
                        ->where('account_id', $pageId)
                        ->where('external_id', $customerId))
                    ->first();

                if ($seen) {
                    $seen->conversation->messages()
                        ->where('direction', 'outbound')
                        ->where('occurred_at', '<=', $seen->occurred_at)
                        ->whereNotIn('status', ['read', 'failed'])
                        ->update(['status' => 'read']);
                }
            }

            foreach ((array) data_get($event, 'delivery.mids', []) as $messageId) {
                $this->updateDeliveryStatus($channel, $pageId, $recipientId, (string) $messageId, 'delivered');
            }

            $watermark = data_get($event, 'read.watermark');

            if (is_numeric($watermark)) {
                $readAt = $this->timestamp($watermark, milliseconds: true);
                // Meta sends read receipts with sender = customer, recipient = Page; accept either side.
                $customerIds = array_values(array_filter(array_unique([$customerId, $recipientId]), fn ($id) => $id !== '' && $id !== $pageId));

                if ($customerIds !== []) {
                    $this->messageRepository->getModel()->newQuery()
                        ->where('direction', 'outbound')
                        ->whereNotNull('external_id')
                        ->where('occurred_at', '<=', $readAt)
                        ->whereNotIn('status', ['read', 'failed'])
                        ->whereHas('conversation', fn ($query) => $query
                            ->where('channel', $channel)
                            ->where('account_id', $pageId)
                            ->whereIn('external_id', $customerIds))
                        ->update(['status' => 'read']);
                }
            }
        }
    }

    protected function storeInboundMessage(
        string $channel,
        string $accountId,
        string $externalSenderId,
        string $customerName,
        string $externalMessageId,
        string $body,
        array $attachments = [],
        ?Carbon $occurredAt = null,
        ?string $avatarUrl = null,
    ): void {
        $occurredAt ??= now();

        $stored = DB::transaction(function () use ($channel, $accountId, $externalSenderId, $customerName, $externalMessageId, $body, $attachments, $occurredAt, $avatarUrl) {
            // Same customer on an older/other number of this tenant: carry CRM links into the new
            // number's conversation and remember the previous thread (its history stays untouched).
            $previous = $this->conversationRepository->getModel()->newQuery()
                ->where('channel', $channel)
                ->where('external_id', $externalSenderId)
                ->where('account_id', '!=', $accountId)
                ->orderByDesc('last_message_at')
                ->first();

            $conversation = $this->conversationRepository->getModel()->newQuery()->firstOrCreate(
                [
                    'channel' => $channel,
                    'account_id' => $accountId,
                    'external_id' => $externalSenderId,
                ],
                array_filter([
                    'customer_name' => $customerName,
                    'status' => 'open',
                    'person_id' => $previous?->person_id,
                    'lead_id' => $previous?->lead_id,
                    'organization_id' => $previous?->organization_id,
                    'user_id' => $previous?->user_id,
                    'metadata' => $previous ? ['previous_conversation_id' => $previous->id] : null,
                ], fn ($v) => $v !== null)
            );
            $conversation = $conversation->newQuery()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            if ($conversation->messages()->where('external_id', $externalMessageId)->exists()) {
                return null;
            }

            $personId = null;

            try {
                if ($channel === 'whatsapp' && preg_match('/^\+?\d+$/', $externalSenderId) && class_exists(PersonProxy::class)) {
                    $personId = PersonProxy::modelClass()::whereJsonContains('contact_numbers', ['value' => $externalSenderId])
                        ->value('id');
                }
            } catch (\Throwable) {
                $personId = null;
            }

            $this->messageRepository->create([
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'external_id' => $externalMessageId,
                'body' => $body,
                'status' => 'received',
                'attachments' => $attachments,
                'occurred_at' => $occurredAt,
            ]);

            $conversation->status = 'open';
            $conversation->unread_count++;
            $conversation->last_incoming_at = $conversation->last_incoming_at
                ? $conversation->last_incoming_at->max($occurredAt)
                : $occurredAt;
            $conversation->person_id ??= $personId;

            if (! $conversation->last_message_at || $occurredAt->gte($conversation->last_message_at)) {
                $conversation->last_message_preview = $body;
                $conversation->last_message_at = $occurredAt;
            }

            if ($this->isPlaceholderCustomerName((string) $conversation->customer_name) && $customerName !== '') {
                $conversation->customer_name = $customerName;
            }

            if (
                $avatarUrl
                && Schema::hasColumn($conversation->getTable(), 'avatar_url')
            ) {
                $conversation->avatar_url = $avatarUrl;
            }

            $conversation->save();

            return $conversation;
        }, 3);

        if ($stored) {
            try {
                app(InboxNotifier::class)->notifyInbound($stored, $body, $attachments !== []);
            } catch (\Throwable $e) {
                Log::warning('Unified Inbox could not create the new message notification.', [
                    'conversation_id' => $stored->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Fetch Messenger/Instagram sender profile from Graph (webhook payload has no name).
     *
     * @return array{name: string, profile_pic: string}
     */
    protected function resolveMessagingCustomerProfile(string $channel, string $accountId, string $senderId): array
    {
        $fallback = ['name' => ucfirst($channel).' User', 'profile_pic' => ''];

        try {
            $connection = $this->channelRepository->findOneWhere([
                'channel' => $channel,
                'external_id' => $accountId,
                'status' => 'connected',
            ]);

            // Instagram rows sometimes store the Page ID in metadata while external_id is the IG account.
            if (! $connection && $channel === 'instagram') {
                $connection = $this->channelRepository->getModel()->newQuery()
                    ->where('channel', 'instagram')
                    ->where('status', 'connected')
                    ->where(function ($query) use ($accountId) {
                        $query->where('external_id', $accountId)
                            ->orWhere('metadata->page_id', $accountId);
                    })
                    ->first();
            }

            $token = (string) (($connection?->credentials ?? [])['access_token'] ?? '');

            if ($token === '' || $senderId === '') {
                return $fallback;
            }

            $profile = $this->metaGraph->messagingUserProfile($senderId, $token, $channel);
            $name = trim((string) ($profile['name'] ?? ''));

            return [
                'name' => $name !== '' ? $name : $fallback['name'],
                'profile_pic' => (string) ($profile['profile_pic'] ?? ''),
            ];
        } catch (\Throwable $e) {
            Log::info('Unified Inbox could not resolve '.$channel.' sender name.', [
                'sender_id' => $senderId,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    protected function isPlaceholderCustomerName(string $name): bool
    {
        return in_array($name, ['', 'WhatsApp User', 'Messenger User', 'Instagram User'], true);
    }

    protected function updateDeliveryStatus(string $channel, string $accountId, string $recipientId, string $messageId, string $status): void
    {
        if ($recipientId === '' || $messageId === '') {
            return;
        }

        $message = $this->messageRepository->getModel()->newQuery()
            ->where('external_id', $messageId)
            ->where('direction', 'outbound')
            ->whereHas('conversation', fn ($query) => $query
                ->where('channel', $channel)
                ->where('account_id', $accountId)
                ->where('external_id', $recipientId))
            ->first();

        if ($message) {
            $this->applyDeliveryStatus($message, $status);
        }
    }

    /**
     * Meta echoes an outgoing Messenger/Instagram message with its own event timestamp.
     * Use that platform timestamp instead of the CRM server clock.
     */
    protected function updateOutboundOccurredAt(string $channel, string $accountId, string $messageId, Carbon $occurredAt): void
    {
        $message = $this->messageRepository->getModel()->newQuery()
            ->where('external_id', $messageId)
            ->where('direction', 'outbound')
            ->whereHas('conversation', fn ($query) => $query
                ->where('channel', $channel)
                ->where('account_id', $accountId))
            ->first();

        if (! $message) {
            return;
        }

        $message->update(['occurred_at' => $occurredAt]);

        $latest = $message->conversation->messages()->orderByDesc('occurred_at')->first();

        if ($latest) {
            $message->conversation->update([
                'last_message_preview' => $latest->body,
                'last_message_at' => $latest->occurred_at,
            ]);
        }
    }

    protected function markUnsentMessage(string $channel, string $accountId, string $messageId): void
    {
        if ($messageId === '') {
            return;
        }

        $message = $this->messageRepository->getModel()->newQuery()
            ->where('external_id', $messageId)
            ->whereHas('conversation', fn ($query) => $query
                ->where('channel', $channel)
                ->where('account_id', $accountId))
            ->first();

        if ($message) {
            $this->messageRepository->markDeleted($message, 'customer');
        }
    }

    protected function applyDeliveryStatus(Model $message, string $status): void
    {
        $priority = [
            'pending' => 0,
            'unknown' => 1,
            'accepted' => 1,
            'sent' => 2,
            'delivered' => 3,
            'read' => 4,
            'failed' => 5,
        ];

        if (($priority[$status] ?? -1) >= ($priority[$message->status] ?? -1)) {
            $message->update(['status' => $status]);
        }
    }

    protected function timestamp(mixed $value, bool $milliseconds = false): Carbon
    {
        if (! is_numeric($value)) {
            return now();
        }

        $timestamp = (int) $value;
        $timestamp = $milliseconds ? (int) floor($timestamp / 1000) : $timestamp;

        return Carbon::createFromTimestampUTC($timestamp)
            ->min(now()->utc())
            ->setTimezone(config('app.timezone'));
    }
}
