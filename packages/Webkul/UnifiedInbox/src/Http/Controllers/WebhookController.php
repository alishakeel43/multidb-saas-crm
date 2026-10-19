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
use Webkul\Contact\Models\PersonProxy;
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;
use Webkul\UnifiedInbox\Repositories\ConversationRepository;
use Webkul\UnifiedInbox\Repositories\MessageRepository;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;

class WebhookController extends Controller
{
    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected MessageRepository $messageRepository,
        protected ChannelConnectionRepository $channelRepository,
        protected CentralWebhookRouter $centralRouter
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
        $pageId = (string) ($entry['id'] ?? '');

        $this->runInTenantContext(
            channel: $channel,
            externalId: $pageId,
            wabaId: null,
            callback: fn () => $this->handleMessagingEntry($entry, $object)
        );
    }

    /**
     * Switch to the resolved Tenant context or execute safely.
     */
    protected function runInTenantContext(string $channel, string $externalId, ?string $wabaId, callable $callback): void
    {
        $targetTenant = $this->centralRouter->resolveTenant($channel, $externalId, $wabaId);

        if (! $targetTenant) {
            Log::warning("Unified Inbox webhook has no active tenant route for {$channel} account.");

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
                } elseif ($type === 'image') {
                    $messageBody = '[Image] '.($waMsg['image']['caption'] ?? '');
                    $attachments[] = ['type' => 'image', 'id' => $waMsg['image']['id'] ?? null];
                } elseif ($type === 'audio') {
                    $messageBody = '[Voice Note]';
                    $attachments[] = ['type' => 'audio', 'id' => $waMsg['audio']['id'] ?? null];
                } elseif ($type === 'document') {
                    $messageBody = '[Document] '.($waMsg['document']['filename'] ?? '');
                    $attachments[] = ['type' => 'document', 'id' => $waMsg['document']['id'] ?? null];
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

    protected function handleMessagingEntry(array $entry, string $object): void
    {
        $messagingEvents = (array) ($entry['messaging'] ?? []);
        $channel = $object === 'instagram' ? 'instagram' : 'messenger';
        $pageId = (string) ($entry['id'] ?? '');

        foreach ($messagingEvents as $event) {
            if (! empty($event['message'])) {
                $senderId = (string) ($event['sender']['id'] ?? '');

                if ($senderId && $senderId !== $pageId && ! ($event['message']['is_echo'] ?? false)) {
                    $messageData = (array) $event['message'];
                    $messageId = (string) ($messageData['mid'] ?? '');
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
                        $this->storeInboundMessage(
                            channel: $channel,
                            accountId: $pageId,
                            externalSenderId: $senderId,
                            customerName: ucfirst($channel).' User',
                            externalMessageId: $messageId,
                            body: $body ?: 'New message',
                            attachments: $attachments,
                            occurredAt: $this->timestamp($event['timestamp'] ?? null, milliseconds: true)
                        );
                    }
                }
            }

            $recipientId = (string) ($event['recipient']['id'] ?? '');

            foreach ((array) data_get($event, 'delivery.mids', []) as $messageId) {
                $this->updateDeliveryStatus($channel, $pageId, $recipientId, (string) $messageId, 'delivered');
            }

            $watermark = data_get($event, 'read.watermark');

            if ($recipientId !== '' && is_numeric($watermark)) {
                $readAt = $this->timestamp($watermark, milliseconds: true);
                $this->messageRepository->getModel()->newQuery()
                    ->where('direction', 'outbound')
                    ->whereNotNull('external_id')
                    ->where('occurred_at', '<=', $readAt)
                    ->whereHas('conversation', fn ($query) => $query
                        ->where('channel', $channel)
                        ->where('account_id', $pageId)
                        ->where('external_id', $recipientId))
                    ->update(['status' => 'read']);
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
        ?Carbon $occurredAt = null
    ): void {
        $occurredAt ??= now();

        DB::transaction(function () use ($channel, $accountId, $externalSenderId, $customerName, $externalMessageId, $body, $attachments, $occurredAt) {
            $conversation = $this->conversationRepository->getModel()->newQuery()->firstOrCreate(
                [
                    'channel' => $channel,
                    'account_id' => $accountId,
                    'external_id' => $externalSenderId,
                ],
                [
                    'customer_name' => $customerName,
                    'status' => 'open',
                ]
            );
            $conversation = $conversation->newQuery()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            if ($conversation->messages()->where('external_id', $externalMessageId)->exists()) {
                return;
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

            if ($conversation->customer_name === 'WhatsApp User') {
                $conversation->customer_name = $customerName;
            }

            $conversation->save();
        }, 3);
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

    protected function applyDeliveryStatus(Model $message, string $status): void
    {
        $priority = [
            'pending' => 0,
            'unknown' => 1,
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

        return Carbon::createFromTimestampUTC($timestamp)->min(now());
    }
}
