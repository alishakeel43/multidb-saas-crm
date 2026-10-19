<?php

namespace Webkul\UnifiedInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Activity\Models\ActivityProxy;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Contact\Repositories\OrganizationRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\UnifiedInbox\Exceptions\MetaGraphException;
use Webkul\UnifiedInbox\Models\QuickReply;
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;
use Webkul\UnifiedInbox\Repositories\ConversationRepository;
use Webkul\UnifiedInbox\Repositories\MessageRepository;
use Webkul\UnifiedInbox\Services\AI\ReplySuggestionService;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\UnifiedInbox\Services\MetaGraphClient;
use Webkul\UnifiedInbox\Support\WhatsAppTemplateComposer;
use Webkul\User\Models\UserProxy;

class InboxController extends Controller
{
    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected MessageRepository $messageRepository,
        protected ChannelConnectionRepository $channelRepository,
        protected MetaGraphClient $metaClient,
        protected PersonRepository $personRepository,
        protected LeadRepository $leadRepository,
        protected PipelineRepository $pipelineRepository,
        protected CentralWebhookRouter $router,
        protected OrganizationRepository $organizationRepository,
        protected ActivityRepository $activityRepository
    ) {}

    public function index(Request $request): View
    {
        $channelFilter = $request->input('channel');
        $statusFilter = $request->input('status');
        $search = $request->input('search');
        // Unread is a modifier that stacks on top of one status filter.
        $unreadFilter = $request->boolean('unread');
        // Meta-style left rail folder: inbox | unread | follow_up | done | spam.
        $folder = in_array($request->input('folder'), ['inbox', 'unread', 'follow_up', 'done', 'spam'], true) ? $request->input('folder') : null;
        $labelFilter = trim((string) $request->input('label', ''));
        $hasFolders = \Illuminate\Support\Facades\Schema::hasColumn('unified_inbox_conversations', 'is_spam');
        $activeId = $request->input('conversation_id');

        $connections = $this->channelRepository->all();
        $linkedChannels = $connections->where('status', 'connected')->pluck('channel')->unique()->values()->all();
        $activeConnections = $connections->where('status', 'connected')->values();

        // History belongs to the tenant, not the number: conversations from every account this tenant
        // ever connected (current + archived/old numbers) are listed. Old ones get a "via old number" badge.
        $historyConnections = $connections->whereIn('status', ['connected', 'disconnected'])->values();
        $oldNumbers = $historyConnections
            ->where('status', '!=', 'connected')
            ->reject(fn ($c) => $activeConnections->contains(fn ($a) => $a->channel === $c->channel && $a->external_id === $c->external_id))
            ->mapWithKeys(fn ($c) => [$c->channel.'|'.$c->external_id => $c->numberLabel()])
            ->all();

        $query = $this->conversationRepository->getModel()->newQuery()
            ->with(['person', 'lead', 'user', 'organization'])
            ->orderByDesc('last_message_at');

        if ($historyConnections->isEmpty()) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where(function ($outer) use ($historyConnections) {
                foreach ($historyConnections as $connection) {
                    $outer->orWhere(function ($inner) use ($connection) {
                        $inner->where('channel', $connection->channel)
                            ->where('account_id', $connection->external_id);
                    });
                }
            });
        }

        if ($channelFilter && in_array($channelFilter, ['whatsapp', 'messenger', 'instagram'])) {
            $query->where('channel', $channelFilter);
        }

        if ($statusFilter && in_array($statusFilter, ['open', 'pending', 'closed'])) {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('last_message_preview', 'like', "%{$search}%");
            });
        }

        if ($unreadFilter) {
            $query->where('unread_count', '>', 0);
        }

        if ($hasFolders) {
            // Spam is hidden everywhere except the Spam folder.
            if ($folder === 'spam') {
                $query->where('is_spam', true);
            } else {
                $query->where(fn ($q) => $q->whereNull('is_spam')->orWhere('is_spam', false));
            }

            match ($folder) {
                'inbox' => $query->where('status', '!=', 'closed'),
                'unread' => $query->where('unread_count', '>', 0),
                'follow_up' => $query->where('is_follow_up', true),
                'done' => $query->where('status', 'closed'),
                default => null,
            };

            if ($labelFilter !== '') {
                $query->whereJsonContains('labels', $labelFilter);
            }
        }

        $folderCounts = [];
        $allLabels = [];
        if ($hasFolders) {
            $base = $this->conversationRepository->getModel()->newQuery();
            if ($historyConnections->isNotEmpty()) {
                $base->where(function ($outer) use ($historyConnections) {
                    foreach ($historyConnections as $connection) {
                        $outer->orWhere(fn ($inner) => $inner->where('channel', $connection->channel)->where('account_id', $connection->external_id));
                    }
                });
            } else {
                $base->whereRaw('1 = 0');
            }
            if ($channelFilter && in_array($channelFilter, ['whatsapp', 'messenger', 'instagram'])) {
                $base->where('channel', $channelFilter);
            }
            $notSpam = fn ($q) => $q->where(fn ($w) => $w->whereNull('is_spam')->orWhere('is_spam', false));
            $folderCounts = [
                'inbox' => $notSpam(clone $base)->where('status', '!=', 'closed')->count(),
                'unread' => $notSpam(clone $base)->where('unread_count', '>', 0)->count(),
                'follow_up' => $notSpam(clone $base)->where('is_follow_up', true)->count(),
                'done' => $notSpam(clone $base)->where('status', 'closed')->count(),
                'spam' => (clone $base)->where('is_spam', true)->count(),
            ];
            $allLabels = (clone $base)->whereNotNull('labels')->pluck('labels')
                ->flatten()->filter(fn ($l) => is_string($l) && $l !== '')->unique()->sort()->values()->all();
        }

        $activeConversation = null;
        if ($activeId) {
            $activeConversation = (clone $query)->whereKey((int) $activeId)->first();
        }

        $conversations = $query->paginate(25)->withQueryString();

        if ($activeConversation) {
            $activeConversation = $conversations->getCollection()->firstWhere('id', $activeConversation->id) ?? $activeConversation;
        }

        if (! $activeConversation && $conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        $activeMessages = collect();
        if ($activeConversation) {
            $activeMessages = $activeConversation->messages()
                ->orderByDesc('occurred_at')
                ->paginate(50, ['*'], 'messages_page')
                ->withQueryString();
            $activeMessages->setCollection($activeMessages->getCollection()->reverse()->values());

            // Mark unread as 0 when viewed
            if ($activeConversation->unread_count > 0) {
                $activeConversation->update(['unread_count' => 0]);
            }
        }

        $crmRelated = $activeConversation ? $this->relatedRecords($activeConversation) : null;
        $timeline = $activeConversation ? $this->timeline($activeConversation) : collect();
        $chatNotes = $activeConversation ? $this->chatNotes($activeConversation) : collect();

        $users = UserProxy::modelClass()::query()->where('status', 1)->excludeSupport()->get(['id', 'name', 'email']);
        $pipelines = $this->pipelineRepository->all(['id', 'name']);

        return view('unified_inbox::admin.inbox.index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'activeMessages' => $activeMessages,
            'users' => $users,
            'pipelines' => $pipelines,
            'connections' => $connections,
            'channels' => config('unified-inbox.channels', [
                'whatsapp' => ['label' => 'WhatsApp'],
                'messenger' => ['label' => 'Messenger'],
                'instagram' => ['label' => 'Instagram'],
            ]),
            'centralWebhookUrl' => $this->router->getCentralWebhookUrl(),
            'channelFilter' => $channelFilter,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'unreadFilter' => $unreadFilter,
            'crmRelated' => $crmRelated,
            'timeline' => $timeline,
            'chatNotes' => $chatNotes,
            'folder' => $folder,
            'labelFilter' => $labelFilter,
            'folderCounts' => $folderCounts,
            'allLabels' => $allLabels,
            'hasFolders' => $hasFolders,
            'quickReplies' => $this->quickReplies(),
            'oldNumbers' => $oldNumbers,
            'historyChannels' => $this->conversationRepository->getModel()->newQuery()
                ->whereIn('channel', $historyConnections->where('status', '!=', 'connected')->pluck('channel')->unique()->all() ?: ['__none__'])
                ->distinct()->pluck('channel')->all(),
            'priorThreads' => $activeConversation ? $this->priorThreads($activeConversation, $connections) : collect(),
            'continuation' => $activeConversation && isset($oldNumbers[$activeConversation->channel.'|'.$activeConversation->account_id])
                ? $this->continuationFor($activeConversation)
                : null,
        ]);
    }

    /**
     * Same customer (wa_id / PSID) on other numbers of this tenant, oldest number first, with recent messages.
     */
    protected function priorThreads($conversation, $connections)
    {
        return $this->conversationRepository->getModel()->newQuery()
            ->where('channel', $conversation->channel)
            ->where('external_id', $conversation->external_id)
            ->where('id', '!=', $conversation->id)
            ->orderBy('last_message_at')
            ->get()
            ->map(function ($other) use ($connections) {
                $conn = $connections->first(fn ($c) => $c->channel === $other->channel && $c->external_id === $other->account_id);
                $other->number_label = $conn?->numberLabel() ?? $other->account_id;
                $other->number_active = $conn?->status === 'connected';
                $other->recent_messages = $other->messages()->orderByDesc('occurred_at')->limit(30)->get()->reverse()->values();
                $other->message_total = $other->messages()->count();

                return $other;
            });
    }

    /**
     * For a conversation on an old number: the same customer's conversation on the current number, if any.
     */
    protected function continuationFor($conversation)
    {
        $active = $this->connectedChannel($conversation->channel);

        if (! $active || $active->status !== 'connected' || $active->external_id === $conversation->account_id) {
            return null;
        }

        return $this->conversationRepository->getModel()->newQuery()
            ->where('channel', $conversation->channel)
            ->where('account_id', $active->external_id)
            ->where('external_id', $conversation->external_id)
            ->first();
    }

    /**
     * Replies to a conversation whose number was disconnected go out from the tenant's current number,
     * continuing in that number's conversation with the same customer (created if needed, CRM links copied).
     */
    protected function resolveReplyConversation($conversation)
    {
        $own = $this->channelRepository->findOneWhere([
            'channel' => $conversation->channel,
            'external_id' => $conversation->account_id,
            'status' => 'connected',
        ]);

        if ($own || $conversation->channel !== 'whatsapp') {
            return $conversation;
        }

        $active = $this->connectedChannel('whatsapp');

        if (! $active || $active->status !== 'connected') {
            return $conversation;
        }

        return $this->conversationRepository->getModel()->newQuery()->firstOrCreate(
            [
                'channel' => 'whatsapp',
                'account_id' => (string) $active->external_id,
                'external_id' => $conversation->external_id,
            ],
            [
                'customer_name' => $conversation->customer_name,
                'status' => 'open',
                'unread_count' => 0,
                'person_id' => $conversation->person_id,
                'lead_id' => $conversation->lead_id,
                'organization_id' => $conversation->organization_id,
                'user_id' => $conversation->user_id,
                'metadata' => ['previous_conversation_id' => $conversation->id],
            ]
        );
    }

    protected function quickReplies()
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('unified_inbox_quick_replies')) {
            return collect();
        }

        return QuickReply::query()->orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'shortcut', 'body']);
    }

    /**
     * Toggle Meta-style folder flags: follow_up / spam.
     */
    public function updateFlags(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'is_follow_up' => 'sometimes|boolean',
            'is_spam' => 'sometimes|boolean',
        ]);

        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $conversation->update($validated);

        return response()->json([
            'success' => true,
            'is_follow_up' => (bool) $conversation->is_follow_up,
            'is_spam' => (bool) $conversation->is_spam,
        ]);
    }

    /**
     * Replace the label set of a conversation.
     */
    public function updateLabels(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'labels' => 'present|array|max:20',
            'labels.*' => 'nullable|string|max:40',
        ]);

        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $labels = collect($request->input('labels', []))
            ->map(fn ($l) => trim(strip_tags((string) $l)))
            ->filter()->unique()->values()->all();

        $conversation->update(['labels' => $labels ?: null]);

        return response()->json(['success' => true, 'labels' => $labels]);
    }

    public function quickRepliesIndex(): JsonResponse
    {
        return response()->json(['data' => $this->quickReplies()]);
    }

    public function quickRepliesStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'shortcut' => 'nullable|string|max:60|regex:/^[A-Za-z0-9_\-]+$/',
            'body' => 'required|string|max:4096',
        ]);

        $reply = QuickReply::create($validated + ['user_id' => auth()->guard('user')->id()]);

        return response()->json(['success' => true, 'data' => $reply]);
    }

    public function quickRepliesUpdate(Request $request, int $replyId): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'shortcut' => 'nullable|string|max:60|regex:/^[A-Za-z0-9_\-]+$/',
            'body' => 'required|string|max:4096',
        ]);

        $reply = QuickReply::findOrFail($replyId);
        $reply->update($validated);

        return response()->json(['success' => true, 'data' => $reply]);
    }

    public function quickRepliesDestroy(int $replyId): JsonResponse
    {
        QuickReply::findOrFail($replyId)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Send an image or document over WhatsApp Cloud API (upload to /media, then send by media ID).
     */
    public function sendAttachment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:16384|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv',
            'caption' => 'nullable|string|max:1024',
        ]);

        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        if ($conversation->channel !== 'whatsapp') {
            return response()->json(['success' => false, 'error' => 'Attachments are available for WhatsApp conversations only.'], 422);
        }

        $conversation = $this->resolveReplyConversation($conversation);

        if ($conversation->last_incoming_at && ! $conversation->serviceWindowOpen()) {
            return response()->json(['success' => false, 'error' => 'The 24-hour reply window has closed. Send an approved template instead.'], 422);
        }

        $connection = $this->channelRepository->findOneWhere([
            'channel' => 'whatsapp',
            'external_id' => $conversation->account_id,
            'status' => 'connected',
        ]);

        $credentials = $connection?->credentials ?? [];
        $token = (string) ($credentials['access_token'] ?? '');

        if (! $connection || ! $connection->usable() || $token === '' || str_starts_with((string) $connection->external_id, 'demo-')) {
            return response()->json(['success' => false, 'error' => 'No live WhatsApp connection is available for this conversation.'], 422);
        }

        $file = $request->file('file');
        $mime = (string) $file->getMimeType();
        $type = str_starts_with($mime, 'image/') ? 'image' : 'document';
        $filename = basename((string) $file->getClientOriginalName());
        $caption = trim((string) $request->input('caption', '')) ?: null;
        $phoneNumberId = (string) ($credentials['phone_number_id'] ?? $connection->external_id);

        // Keep a tenant-local copy so the agent sees the file in the thread.
        $stored = $file->store('unified-inbox/'.$conversation->id, 'local');

        $message = $this->messageRepository->create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'body' => trim(($type === 'image' ? '[Image]' : '[Document] '.$filename).($caption ? ' '.$caption : '')),
            'status' => 'pending',
            'attachments' => [array_filter(['type' => $type, 'path' => $stored, 'mime_type' => $mime, 'filename' => $filename, 'caption' => $caption])],
            'occurred_at' => now(),
        ]);

        try {
            $mediaId = $this->metaClient->uploadWhatsAppMedia($phoneNumberId, $file->getRealPath(), $filename, $mime, $token);
            $result = $this->metaClient->sendWhatsAppMedia($phoneNumberId, $conversation->external_id, $type, $mediaId, $caption, $filename, $token);
            $externalMessageId = (string) ($result['messages'][0]['id'] ?? '');

            if ($externalMessageId === '') {
                throw new \RuntimeException('The messaging provider did not confirm the message ID.');
            }
        } catch (MetaGraphException $e) {
            $message->update(['status' => 'failed']);

            return response()->json(['success' => false, 'error' => $this->metaSendErrorMessage($e), 'message' => $message->fresh()], 502);
        } catch (Exception $e) {
            $message->update(['status' => 'unknown']);
            Log::warning('Unified Inbox attachment delivery could not be confirmed.', ['conversation_id' => $conversation->id, 'exception' => get_class($e)]);

            return response()->json(['success' => false, 'error' => 'Delivery could not be confirmed. Check the conversation on WhatsApp before retrying.', 'message' => $message->fresh()], 502);
        }

        $attachments = $message->attachments;
        $attachments[0]['id'] = $mediaId;
        $message->update(['external_id' => $externalMessageId, 'status' => 'sent', 'attachments' => $attachments]);
        $conversation->update(['last_message_preview' => $message->body, 'last_message_at' => $message->occurred_at]);

        return response()->json(['success' => true, 'message' => $message->fresh()]);
    }

    /**
     * Stream an attachment: local copy for outbound files, Meta media (by ID) for inbound ones.
     */
    public function attachment(Request $request, int $id, int $messageId, int $index)
    {
        $conversation = $this->conversationRepository->find($id);
        abort_unless($conversation, 404);

        $message = $conversation->messages()->whereKey($messageId)->firstOrFail();
        $attachment = ($message->attachments ?? [])[$index] ?? null;
        abort_unless(is_array($attachment), 404);

        $filename = (string) ($attachment['filename'] ?? (($attachment['type'] ?? 'file').'-'.$message->id));
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        if (! empty($attachment['path']) && \Illuminate\Support\Facades\Storage::disk('local')->exists($attachment['path'])) {
            return \Illuminate\Support\Facades\Storage::disk('local')->response($attachment['path'], $filename, [
                'Content-Type' => $attachment['mime_type'] ?? 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ], $disposition);
        }

        $mediaId = (string) ($attachment['id'] ?? '');
        abort_unless($conversation->channel === 'whatsapp' && preg_match('/^\d+$/', $mediaId), 404);

        $connection = $this->channelRepository->findOneWhere(['channel' => 'whatsapp', 'external_id' => $conversation->account_id]);
        $token = (string) (($connection?->credentials ?? [])['access_token'] ?? '');
        abort_if($token === '', 404);

        try {
            $media = $this->metaClient->downloadWhatsAppMedia($mediaId, $token);
        } catch (\Throwable $e) {
            abort(502, 'Could not fetch media from WhatsApp.');
        }

        $mime = $media['mime'];
        // Only render images/audio/video/pdf inline; everything else downloads.
        if (! preg_match('#^(image/(png|jpe?g|webp|gif)|audio/|video/|application/pdf)#', $mime)) {
            $disposition = 'attachment';
        }

        return response($media['body'], 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.addcslashes($filename, '"\\').'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Show a specific conversation.
     */
    public function show(int $id, Request $request): View
    {
        $request->merge(['conversation_id' => $id]);

        return $this->index($request);
    }

    /**
     * Return messages list for a conversation via JSON.
     */
    public function messages(int $id): JsonResponse
    {
        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        $messages = $conversation->messages()
            ->orderBy('occurred_at', 'asc')
            ->get();

        return response()->json([
            'conversation' => $conversation->load(['person', 'lead', 'user']),
            'messages' => $messages,
        ]);
    }

    /**
     * Send an outbound reply message.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $body = trim((string) $request->input('body'));

        $originalConversationId = $conversation->id;
        $conversation = $this->resolveReplyConversation($conversation);

        // WhatsApp 24h customer service window: free text only within 24h of the last inbound message.
        if ($conversation->channel === 'whatsapp' && $conversation->last_incoming_at && ! $conversation->serviceWindowOpen()) {
            return response()->json([
                'success' => false,
                'window_closed' => true,
                'error' => 'The 24-hour reply window has closed for this customer. Send an approved WhatsApp template instead.',
            ], 422);
        }

        // Find connection for channel
        $connection = $this->channelRepository->findOneWhere([
            'channel' => $conversation->channel,
            'external_id' => $conversation->account_id,
            'status' => 'connected',
        ]);

        if (! $connection || ! $connection->usable()) {
            return response()->json(['success' => false, 'error' => 'No active channel connection is available for this conversation.'], 422);
        }

        $credentials = $connection->credentials ?? [];
        $token = (string) ($credentials['access_token'] ?? '');

        if ($token === '') {
            return response()->json(['success' => false, 'error' => 'The channel connection has no access token.'], 422);
        }

        if (str_starts_with((string) $connection->external_id, 'demo-')) {
            return response()->json([
                'success' => false,
                'error' => 'This is a demo channel ('.$connection->name.') with placeholder credentials, so messages cannot be sent to Meta. Connect a real account in Manage Channels to send replies.',
            ], 422);
        }

        $message = $this->messageRepository->create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'body' => $body,
            'status' => 'pending',
            'occurred_at' => now(),
        ]);

        try {
            if ($conversation->channel === 'whatsapp') {
                $result = $this->metaClient->sendWhatsAppMessage(
                    (string) ($credentials['phone_number_id'] ?? $connection->external_id),
                    $conversation->external_id,
                    $body,
                    $token
                );
                $externalMessageId = $result['messages'][0]['id'] ?? null;
            } else {
                $targetId = $conversation->channel === 'instagram'
                    ? (string) ($credentials['instagram_id'] ?? $connection->external_id)
                    : (string) ($credentials['page_id'] ?? $connection->external_id);
                $result = $this->metaClient->sendPageMessage(
                    $targetId,
                    $conversation->external_id,
                    $body,
                    $token
                );
                $externalMessageId = $result['message_id'] ?? null;
            }

            if (! is_string($externalMessageId) || $externalMessageId === '') {
                throw new \RuntimeException('The messaging provider did not confirm the message ID.');
            }
        } catch (MetaGraphException $e) {
            $message->update(['status' => 'failed']);
            Log::warning('Unified Inbox outbound message rejected by Meta.', [
                'conversation_id' => $conversation->id,
                'channel' => $conversation->channel,
            ] + $e->context());

            return response()->json([
                'success' => false,
                'error' => $this->metaSendErrorMessage($e),
                'meta_error' => ['code' => $e->graphCode, 'subcode' => $e->graphSubcode, 'fbtrace_id' => $e->fbtraceId],
                'message' => $message->fresh(),
            ], 502);
        } catch (Exception $e) {
            $message->update(['status' => 'unknown']);
            Log::warning('Unified Inbox outbound delivery could not be confirmed.', [
                'conversation_id' => $conversation->id,
                'channel' => $conversation->channel,
                'exception' => get_class($e),
                'reason' => str_replace($token, '[REDACTED]', $e->getMessage()),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Delivery could not be confirmed (no response or message ID from Meta). Check the conversation on the provider before retrying, to avoid a duplicate.',
                'message' => $message->fresh(),
            ], 502);
        }

        $message->update([
            'external_id' => $externalMessageId,
            'status' => 'sent',
        ]);
        $conversation->update([
            'last_message_preview' => $body,
            'last_message_at' => $message->occurred_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'conversation' => $conversation->fresh(['person', 'lead', 'user']),
            'moved_to_current_number' => $conversation->id !== $originalConversationId,
            'url' => $conversation->id !== $originalConversationId
                ? route('admin.unified_inbox.index', ['conversation_id' => $conversation->id])
                : null,
        ]);
    }

    /**
     * Change conversation status (open, pending, closed).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:open,pending,closed',
        ]);

        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $conversation->update(['status' => $request->input('status')]);

        return response()->json([
            'success' => true,
            'status' => $conversation->status,
        ]);
    }

    /**
     * Assign conversation to an agent (User).
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $conversation->update(['user_id' => $userId]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation->fresh(['user']),
        ]);
    }

    /**
     * Convert / Link conversation to CRM Lead.
     */
    public function linkLead(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $leadId = $request->input('lead_id');

        if (! $leadId) {
            // Create a new Lead
            $pipeline = $this->pipelineRepository->getDefaultPipeline();
            $firstStage = $pipeline ? $pipeline->stages()->orderBy('sort_order')->first() : null;

            $leadTitle = $request->input('title') ?: "Chat Lead - {$conversation->customer_name} ({$conversation->channel})";

            Event::dispatch('lead.create.before');

            $lead = $this->leadRepository->create([
                'title' => $leadTitle,
                'lead_pipeline_id' => $pipeline?->id ?? 1,
                'lead_pipeline_stage_id' => $firstStage?->id ?? 1,
                'user_id' => auth()->guard('user')->id() ?: $conversation->user_id,
                'person_id' => $conversation->person_id,
                'lead_value' => $request->input('lead_value', 0),
                'status' => 1,
                'entity_type' => 'leads',
                'description' => "Created from Unified Inbox message: {$conversation->last_message_preview}",
            ]);

            Event::dispatch('lead.create.after', $lead);

            $leadId = $lead->id;
        } elseif (! $this->leadRepository->find((int) $leadId)) {
            return response()->json(['error' => 'Lead not found.'], 422);
        }

        $conversation->update(['lead_id' => (int) $leadId]);

        return response()->json([
            'success' => true,
            'lead' => $conversation->fresh(['lead'])->lead,
        ]);
    }

    /**
     * Convert / Link conversation to CRM Person.
     */
    public function linkPerson(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $personId = $request->input('person_id');

        if (! $personId) {
            // Create a new Person
            $contactNumbers = [];
            if ($conversation->channel === 'whatsapp' || is_numeric($conversation->external_id)) {
                $contactNumbers[] = ['value' => $conversation->external_id, 'label' => 'work'];
            }

            Event::dispatch('contacts.person.create.before');

            $person = $this->personRepository->create([
                'name' => $conversation->customer_name,
                'contact_numbers' => $contactNumbers,
                'emails' => [],
                'user_id' => auth()->guard('user')->id(),
                'entity_type' => 'persons',
                'organization_id' => $conversation->organization_id,
            ]);

            Event::dispatch('contacts.person.create.after', $person);

            $personId = $person->id;
        } elseif (! $this->personRepository->find((int) $personId)) {
            return response()->json(['error' => 'Contact not found.'], 422);
        }

        $conversation->update(['person_id' => (int) $personId]);

        return response()->json([
            'success' => true,
            'person' => $conversation->fresh(['person'])->person,
        ]);
    }

    /**
     * Search existing Krayin records to attach to a conversation (same for every channel).
     */
    public function crmSearch(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:person,lead,organization',
            'q' => 'nullable|string|max:100',
        ]);

        $q = trim((string) $request->input('q'));
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        $results = match ($request->input('type')) {
            'person' => $this->personRepository->getModel()->newQuery()
                ->with('organization:id,name')
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', $like)
                    ->orWhere('emails', 'like', $like)
                    ->orWhere('contact_numbers', 'like', $like)))
                ->orderBy('name')->limit(10)->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'label' => $p->name,
                    'sub' => collect([
                        collect($p->emails)->pluck('value')->filter()->first(),
                        collect($p->contact_numbers)->pluck('value')->filter()->first(),
                        $p->organization?->name,
                    ])->filter()->implode(' · '),
                ]),
            'lead' => $this->leadRepository->getModel()->newQuery()
                ->with(['person:id,name', 'stage:id,name'])
                ->when($q !== '', fn ($w) => $w->where('title', 'like', $like))
                ->orderByDesc('id')->limit(10)->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'label' => $l->title,
                    'sub' => collect([$l->person?->name, $l->stage?->name])->filter()->implode(' · '),
                ]),
            'organization' => $this->organizationRepository->getModel()->newQuery()
                ->withCount('persons')
                ->when($q !== '', fn ($w) => $w->where('name', 'like', $like))
                ->orderBy('name')->limit(10)->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'label' => $o->name,
                    'sub' => $o->persons_count.' contact(s)',
                ]),
        };

        return response()->json(['data' => $results->values()]);
    }

    /**
     * Attach (id) or detach (id = null) an existing person / lead / organization.
     */
    public function linkRecord(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:person,lead,organization',
            'record_id' => 'nullable|integer|min:1',
        ]);

        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $type = $request->input('type');
        $recordId = $request->input('record_id') ? (int) $request->input('record_id') : null;

        $repository = match ($type) {
            'person' => $this->personRepository,
            'lead' => $this->leadRepository,
            'organization' => $this->organizationRepository,
        };

        if ($recordId && ! $repository->find($recordId)) {
            return response()->json(['error' => ucfirst($type).' not found.'], 422);
        }

        $conversation->update([$type.'_id' => $recordId]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation->fresh(['person', 'lead', 'organization']),
        ]);
    }

    /**
     * Create a Krayin Organization from the chat and link it.
     */
    public function createOrganization(Request $request, int $id): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:191']);

        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        Event::dispatch('contacts.organization.create.before');

        $organization = $this->organizationRepository->create([
            'name' => trim((string) $request->input('name')),
            'address' => [],
            'user_id' => auth()->guard('user')->id(),
            'entity_type' => 'organizations',
        ]);

        Event::dispatch('contacts.organization.create.after', $organization);

        $conversation->update(['organization_id' => $organization->id]);

        // Keep Krayin's own graph consistent: a linked contact without an org joins this one.
        if ($conversation->person && ! $conversation->person->organization_id) {
            $conversation->person->update(['organization_id' => $organization->id]);
        }

        return response()->json(['success' => true, 'organization' => $organization]);
    }

    /**
     * Internal note: a Krayin "note" Activity, attached to linked lead/person and to this conversation.
     * Never sent to Meta.
     */
    public function storeNote(Request $request, int $id): JsonResponse
    {
        $request->validate(['comment' => 'required|string|max:5000']);

        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $label = config('unified-inbox.channels.'.$conversation->channel.'.label', ucfirst($conversation->channel));

        $activity = DB::transaction(function () use ($request, $conversation, $label) {
            Event::dispatch('activity.create.before');

            $activity = $this->activityRepository->create([
                'title' => "Inbox note · {$label} · {$conversation->customer_name}",
                'type' => 'note',
                'comment' => trim((string) $request->input('comment')),
                'is_done' => 1,
                'user_id' => auth()->guard('user')->id(),
                'additional' => json_encode([
                    'source' => 'unified_inbox',
                    'conversation_id' => $conversation->id,
                    'channel' => $conversation->channel,
                ]),
            ]);

            $conversation->activities()->syncWithoutDetaching([$activity->id]);

            if ($conversation->lead_id) {
                $activity->leads()->syncWithoutDetaching([$conversation->lead_id]);
            }

            if ($conversation->person_id) {
                $activity->persons()->syncWithoutDetaching([$conversation->person_id]);
            }

            return $activity;
        });

        // Attach first, then fire: Krayin's listener only re-attaches when missing.
        Event::dispatch('activity.create.after', $activity);

        return response()->json(['success' => true, 'activity' => $activity->fresh('user')]);
    }

    /**
     * Related CRM graph via real Krayin relations: lead -> person -> organization, person -> leads.
     */
    /**
     * Human-readable reason for a Meta send failure (common Graph error codes).
     */
    public function composeTemplates(Request $request): JsonResponse
    {
        $connection = $this->connectedChannel('whatsapp');

        if (! $connection || ! $connection->usable()) {
            return response()->json(['templates' => [], 'error' => 'Connect WhatsApp before choosing a template.'], 422);
        }

        $credentials = $connection->credentials ?? [];
        $wabaId = (string) ($credentials['waba_id'] ?? '');
        $token = (string) ($credentials['access_token'] ?? '');

        if ($wabaId === '' || $token === '') {
            return response()->json(['templates' => [], 'error' => 'The WhatsApp connection has no business account token.'], 422);
        }

        try {
            $rows = $this->metaClient->getWhatsAppTemplates($wabaId, $token);
        } catch (MetaGraphException $e) {
            return response()->json(['templates' => [], 'error' => $this->metaSendErrorMessage($e)], 502);
        }

        $templates = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $components = is_array($row['components'] ?? null) ? $row['components'] : [];
            $slots = WhatsAppTemplateComposer::slots($components);
            $templates[] = [
                'name' => (string) ($row['name'] ?? ''),
                'language' => (string) ($row['language'] ?? ''),
                'category' => (string) ($row['category'] ?? ''),
                'media' => WhatsAppTemplateComposer::requiresMedia($components),
                'slots' => array_map(fn ($slot) => [
                    'component' => $slot['component'],
                    'index' => $slot['index'],
                    'media' => $slot['media'] ?? null,
                ], $slots),
            ];
        }

        return response()->json(['templates' => $templates]);
    }

    public function compose(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => 'required|in:whatsapp,messenger,instagram',
            'recipient' => 'required|string|max:40',
            'name' => 'nullable|string|max:191',
            'body' => 'nullable|string|max:4096',
            'template' => 'nullable|string|max:512',
            'language' => 'nullable|string|max:16',
            'parameters' => 'nullable|array|max:20',
            'parameters.*' => 'nullable|string|max:1000',
        ]);

        $channel = $validated['channel'];
        $connection = $this->connectedChannel($channel);

        if (! $connection || ! $connection->usable()) {
            return response()->json(['success' => false, 'error' => 'Connect '.$channel.' before starting a conversation.'], 422);
        }

        $credentials = $connection->credentials ?? [];
        $token = (string) ($credentials['access_token'] ?? '');

        if ($token === '' || str_starts_with((string) $connection->external_id, 'demo-')) {
            return response()->json(['success' => false, 'error' => 'This channel is not a live Meta account.'], 422);
        }

        try {
            if ($channel === 'whatsapp') {
                [$recipient, $body, $externalMessageId] = $this->sendWhatsAppTemplate($connection, $credentials, $token, $validated);
            } else {
                [$recipient, $body, $externalMessageId] = $this->sendContactMessage($connection, $credentials, $token, $validated);
            }
        } catch (MetaGraphException $e) {
            return response()->json(['success' => false, 'error' => $this->metaSendErrorMessage($e)], 502);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        $conversation = $this->conversationRepository->getModel()->newQuery()->firstOrCreate(
            [
                'channel' => $channel,
                'account_id' => (string) $connection->external_id,
                'external_id' => $recipient,
            ],
            [
                'customer_name' => trim((string) ($validated['name'] ?? '')) ?: $recipient,
                'status' => 'open',
                'unread_count' => 0,
            ]
        );

        $message = $this->messageRepository->create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'external_id' => $externalMessageId,
            'body' => $body,
            'status' => 'sent',
            'occurred_at' => now(),
        ]);

        $conversation->update([
            'last_message_preview' => $body,
            'last_message_at' => $message->occurred_at,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'url' => route('admin.unified_inbox.index', [
                'channel' => $channel,
                'conversation_id' => $conversation->id,
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: string, 2: string}
     */
    protected function sendWhatsAppTemplate($connection, array $credentials, string $token, array $validated): array
    {
        $recipient = WhatsAppTemplateComposer::phone((string) $validated['recipient']);
        $templateName = (string) ($validated['template'] ?? '');
        $language = (string) ($validated['language'] ?? '');

        if (! preg_match('/^\d{8,15}$/', $recipient)) {
            throw new \InvalidArgumentException('Enter the WhatsApp number with country code, digits only.');
        }

        if (! preg_match('/^[a-z0-9_]+$/', $templateName) || $language === '') {
            throw new \InvalidArgumentException('Choose an approved WhatsApp template.');
        }

        $wabaId = (string) ($credentials['waba_id'] ?? '');
        $templates = $wabaId === '' ? [] : $this->metaClient->getWhatsAppTemplates($wabaId, $token);
        $template = collect($templates)->first(fn ($row) => is_array($row)
            && ($row['name'] ?? '') === $templateName
            && ($row['language'] ?? '') === $language);

        if (! is_array($template)) {
            throw new \InvalidArgumentException('That template is not approved on the connected WhatsApp account.');
        }

        $components = is_array($template['components'] ?? null) ? $template['components'] : [];

        if (WhatsAppTemplateComposer::requiresMedia($components)) {
            throw new \InvalidArgumentException('This template needs a media header. Choose a text template.');
        }

        $slots = array_values(array_filter(
            WhatsAppTemplateComposer::slots($components),
            fn ($slot) => empty($slot['media'])
        ));
        $parameters = array_values((array) ($validated['parameters'] ?? []));

        if (count($parameters) < count($slots) || collect($parameters)->contains(fn ($value) => trim((string) $value) === '')) {
            throw new \InvalidArgumentException('Fill every template variable before sending.');
        }

        $result = $this->metaClient->sendWhatsAppTemplate(
            (string) ($credentials['phone_number_id'] ?? $connection->external_id),
            $recipient,
            $templateName,
            $language,
            WhatsAppTemplateComposer::components($slots, $parameters),
            $token
        );
        $externalMessageId = (string) ($result['messages'][0]['id'] ?? '');

        if ($externalMessageId === '') {
            throw new \InvalidArgumentException('WhatsApp did not confirm the template message.');
        }

        return [$recipient, WhatsAppTemplateComposer::preview($templateName, $parameters), $externalMessageId];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: string, 2: string}
     */
    protected function sendContactMessage($connection, array $credentials, string $token, array $validated): array
    {
        $recipient = trim((string) $validated['recipient']);
        $body = trim((string) ($validated['body'] ?? ''));

        if (! preg_match('/^\d{5,32}$/', $recipient)) {
            throw new \InvalidArgumentException('Enter the contact ID Meta assigned after they messaged this account.');
        }

        if ($body === '') {
            throw new \InvalidArgumentException('Write the message to send.');
        }

        $targetId = $connection->channel === 'instagram'
            ? (string) ($credentials['instagram_id'] ?? $connection->external_id)
            : (string) ($credentials['page_id'] ?? $connection->external_id);

        $result = $this->metaClient->sendPageMessage($targetId, $recipient, $body, $token);
        $externalMessageId = (string) ($result['message_id'] ?? '');

        if ($externalMessageId === '') {
            throw new \InvalidArgumentException('Meta did not confirm the message.');
        }

        return [$recipient, $body, $externalMessageId];
    }

    protected function connectedChannel(string $channel): mixed
    {
        return $this->channelRepository->preferredForChannel(
            $this->channelRepository->all(),
            $channel
        );
    }

    private function metaSendErrorMessage(MetaGraphException $e): string
    {
        $code = $e->graphCode;
        $sub = $e->graphSubcode;
        $suffix = ' (Meta error '.($code ?? '?').($sub ? '/'.$sub : '').($e->fbtraceId ? ', trace '.$e->fbtraceId : '').')';

        return match (true) {
            $code === 190 => 'The channel access token is invalid or expired. Reconnect this channel in Manage Channels.'.$suffix,
            $code === 131047, $code === 10 && $sub === 2018278, $sub === 2018278 => 'The 24-hour reply window has closed for this customer. They must message you again (or use an approved WhatsApp template).'.$suffix,
            in_array($code, [131026, 131030], true), $code === 100 && $sub === 2018001 => 'Meta could not deliver to this recipient (invalid or not on the allowed list).'.$suffix,
            in_array($code, [10, 200, 3], true) => 'The connected app lacks permission to send on this channel. Reconnect and grant messaging permissions.'.$suffix,
            in_array($code, [4, 80007, 130429, 131048, 131056], true) => 'Meta rate limit reached. Wait a moment before retrying.'.$suffix,
            default => 'Meta rejected the message: '.$e->getMessage().$suffix,
        };
    }

    /**
     * AI reply suggestions for a conversation (click-to-insert only; never sent to Meta).
     */
    public function aiSuggestions(int $id): JsonResponse
    {
        $conversation = $this->conversationRepository->find($id);

        if (! $conversation) {
            return response()->json(['suggestions' => [], 'error' => 'Conversation not found.'], 404);
        }

        $service = app(ReplySuggestionService::class);

        if (! $service->isConfigured()) {
            return response()->json(['suggestions' => [], 'configured' => false, 'error' => 'AI not configured']);
        }

        try {
            return response()->json(['suggestions' => $service->suggest($conversation), 'configured' => true]);
        } catch (\Throwable $e) {
            Log::warning('Unified Inbox AI suggestions failed', ['conversation_id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['suggestions' => [], 'configured' => true, 'error' => 'Could not get suggestions right now.']);
        }
    }

    protected function relatedRecords($conversation): array
    {
        $person = $conversation->person ?? $conversation->lead?->person;
        $organization = $conversation->organization ?? $person?->organization;

        $leads = collect();
        if ($person) {
            $leads = $person->leads()->with('stage:id,name')->latest('id')->limit(5)->get();
        }
        if ($conversation->lead_id) {
            $leads = $leads->reject(fn ($l) => $l->id === $conversation->lead_id)->values();
        }

        $orgContacts = $organization
            ? $organization->persons()->when($person, fn ($q) => $q->where('id', '!=', $person->id))->orderBy('name')->limit(5)->get(['id', 'name'])
            : collect();

        return [
            'person' => $person,
            'personInferred' => ! $conversation->person && $person,
            'organization' => $organization,
            'organizationInferred' => ! $conversation->organization && $organization,
            'leads' => $leads,
            'orgContacts' => $orgContacts,
        ];
    }

    /**
     * Krayin activities for the linked lead/person plus notes written in this conversation, newest first.
     */
    protected function timeline($conversation)
    {
        $personId = $conversation->person_id ?? $conversation->lead?->person_id;

        return ActivityProxy::modelClass()::query()
            ->with('user:id,name')
            ->where(function ($q) use ($conversation, $personId) {
                $q->whereIn('id', DB::table('unified_inbox_conversation_activities')->select('activity_id')->where('conversation_id', $conversation->id));

                if ($conversation->lead_id) {
                    $q->orWhereIn('id', DB::table('lead_activities')->select('activity_id')->where('lead_id', $conversation->lead_id));
                }

                if ($personId) {
                    $q->orWhereIn('id', DB::table('person_activities')->select('activity_id')->where('person_id', $personId));
                }
            })
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
    }

    /**
     * Internal notes written in this conversation only (Krayin note activities), newest first.
     */
    protected function chatNotes($conversation)
    {
        return ActivityProxy::modelClass()::query()
            ->with('user:id,name')
            ->where('type', 'note')
            ->whereIn('id', DB::table('unified_inbox_conversation_activities')->select('activity_id')->where('conversation_id', $conversation->id))
            ->orderByDesc('created_at')
            ->get();
    }
}
