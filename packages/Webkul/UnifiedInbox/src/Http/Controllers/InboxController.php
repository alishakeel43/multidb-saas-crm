<?php

namespace Webkul\UnifiedInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;
use Webkul\UnifiedInbox\Repositories\ConversationRepository;
use Webkul\UnifiedInbox\Repositories\MessageRepository;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\UnifiedInbox\Services\MetaGraphClient;
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
        protected CentralWebhookRouter $router
    ) {}

    public function index(Request $request): View
    {
        $channelFilter = $request->input('channel');
        $statusFilter = $request->input('status');
        $search = $request->input('search');
        $unreadFilter = $request->boolean('unread');
        $activeId = $request->input('conversation_id');

        $query = $this->conversationRepository->getModel()->newQuery()
            ->with(['person', 'lead', 'user'])
            ->orderByDesc('last_message_at');

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

        $users = UserProxy::modelClass()::query()->where('status', 1)->get(['id', 'name', 'email']);
        $pipelines = $this->pipelineRepository->all(['id', 'name']);
        $connections = $this->channelRepository->all();

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
                $targetId = (string) ($credentials['page_id'] ?? $credentials['instagram_id'] ?? $connection->external_id);
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
        } catch (Exception) {
            $message->update(['status' => 'unknown']);
            Log::warning('Unified Inbox outbound delivery could not be confirmed.', [
                'conversation_id' => $conversation->id,
                'channel' => $conversation->channel,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Delivery could not be confirmed. Check the provider before retrying.',
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

            $leadId = $lead->id;
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

            $person = $this->personRepository->create([
                'name' => $conversation->customer_name,
                'contact_numbers' => $contactNumbers,
                'emails' => [],
                'user_id' => auth()->guard('user')->id(),
                'entity_type' => 'persons',
            ]);

            $personId = $person->id;
        }

        $conversation->update(['person_id' => (int) $personId]);

        return response()->json([
            'success' => true,
            'person' => $conversation->fresh(['person'])->person,
        ]);
    }
}
