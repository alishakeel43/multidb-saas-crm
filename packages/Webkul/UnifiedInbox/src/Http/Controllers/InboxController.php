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
        $activeId = $request->input('conversation_id');

        $connections = $this->channelRepository->all();
        $linkedChannels = $connections->where('status', 'connected')->pluck('channel')->unique()->values()->all();
        $activeConnections = $connections->where('status', 'connected')->values();

        // Only conversations for the account that is connected now, so an older disconnected number cannot hide the live one.
        $query = $this->conversationRepository->getModel()->newQuery()
            ->with(['person', 'lead', 'user', 'organization'])
            ->orderByDesc('last_message_at');

        if ($activeConnections->isEmpty()) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where(function ($outer) use ($activeConnections) {
                foreach ($activeConnections as $connection) {
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
