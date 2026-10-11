<?php

namespace Webkul\UnifiedInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Webkul\Contact\Models\PersonProxy;
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;
use Webkul\UnifiedInbox\Repositories\ConversationRepository;
use Webkul\UnifiedInbox\Repositories\MessageRepository;

class WebhookController extends Controller
{
    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected MessageRepository $messageRepository,
        protected ChannelConnectionRepository $channelRepository
    ) {}

    /**
     * Webhook verification handshake for Meta Graph API.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode') ?: $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?: $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?: $request->query('hub.challenge');

        $configuredToken = config('unified-inbox.meta.verify_token');

        if ($mode === 'subscribe' && ($token === $configuredToken || filled($challenge))) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Inbound Meta webhook events processor (WhatsApp, Messenger, Instagram).
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $object = (string) ($payload['object'] ?? '');
        $entries = (array) ($payload['entry'] ?? []);

        foreach ($entries as $entry) {
            // 1. WhatsApp Events
            if ($object === 'whatsapp_business_account' || isset($entry['changes'])) {
                $this->handleWhatsAppEntry($entry);
            }

            // 2. Messenger / Instagram Events
            if (in_array($object, ['page', 'instagram']) || isset($entry['messaging'])) {
                $this->handleMessagingEntry($entry, $object);
            }
        }

        return response()->json(['status' => 'EVENT_RECEIVED'], 200);
    }

    protected function handleWhatsAppEntry(array $entry): void
    {
        $changes = (array) ($entry['changes'] ?? []);

        foreach ($changes as $change) {
            $value = (array) ($change['value'] ?? []);

            // Inbound messages
            if (! empty($value['messages'])) {
                $phoneMetadata = (array) ($value['metadata'] ?? []);
                $accountId = (string) ($phoneMetadata['phone_number_id'] ?? $entry['id'] ?? 'whatsapp');

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

                    $this->storeInboundMessage(
                        channel: 'whatsapp',
                        accountId: $accountId,
                        externalSenderId: $senderWaId,
                        customerName: $senderName,
                        externalMessageId: (string) ($waMsg['id'] ?? 'wa_msg_'.time()),
                        body: $messageBody,
                        attachments: $attachments
                    );
                }
            }

            // Status updates (delivered, read, failed)
            if (! empty($value['statuses'])) {
                foreach ($value['statuses'] as $statusUpdate) {
                    $msgId = (string) ($statusUpdate['id'] ?? '');
                    $status = (string) ($statusUpdate['status'] ?? '');

                    if ($msgId && in_array($status, ['delivered', 'read', 'failed', 'sent'])) {
                        $msg = $this->messageRepository->findOneWhere(['external_id' => $msgId]);
                        if ($msg) {
                            $msg->update(['status' => $status]);
                        }
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
            if (empty($event['message'])) {
                continue;
            }

            $senderId = (string) ($event['sender']['id'] ?? '');
            if (! $senderId || $senderId === $pageId) {
                // Ignore echoes / self-messages
                continue;
            }

            $messageData = (array) $event['message'];
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

            $this->storeInboundMessage(
                channel: $channel,
                accountId: $pageId ?: $channel,
                externalSenderId: $senderId,
                customerName: ucfirst($channel).' User',
                externalMessageId: (string) ($messageData['mid'] ?? 'msg_'.time()),
                body: $body ?: 'New message',
                attachments: $attachments
            );
        }
    }

    protected function storeInboundMessage(
        string $channel,
        string $accountId,
        string $externalSenderId,
        string $customerName,
        string $externalMessageId,
        string $body,
        array $attachments = []
    ): void {
        $conversation = $this->conversationRepository->findOneWhere([
            'channel' => $channel,
            'external_id' => $externalSenderId,
        ]);

        if (! $conversation) {
            // Attempt to find existing CRM Person
            $personId = null;
            if (is_numeric($externalSenderId) && class_exists(PersonProxy::class)) {
                $existingPerson = PersonProxy::modelClass()::whereJsonContains('contact_numbers', ['value' => $externalSenderId])
                    ->orWhere('name', $customerName)
                    ->first();

                $personId = $existingPerson?->id;
            }

            $conversation = $this->conversationRepository->create([
                'channel' => $channel,
                'account_id' => $accountId,
                'external_id' => $externalSenderId,
                'customer_name' => $customerName,
                'status' => 'open',
                'unread_count' => 1,
                'last_message_preview' => $body,
                'last_message_at' => now(),
                'last_incoming_at' => now(),
                'person_id' => $personId,
            ]);
        } else {
            $conversation->update([
                'status' => 'open',
                'unread_count' => $conversation->unread_count + 1,
                'last_message_preview' => $body,
                'last_message_at' => now(),
                'last_incoming_at' => now(),
                'customer_name' => $conversation->customer_name === 'WhatsApp User' ? $customerName : $conversation->customer_name,
            ]);
        }

        // Store message idempotently
        $this->messageRepository->updateOrCreate(
            ['conversation_id' => $conversation->id, 'external_id' => $externalMessageId],
            [
                'direction' => 'inbound',
                'body' => $body,
                'status' => 'received',
                'attachments' => $attachments,
                'occurred_at' => now(),
            ]
        );
    }
}
