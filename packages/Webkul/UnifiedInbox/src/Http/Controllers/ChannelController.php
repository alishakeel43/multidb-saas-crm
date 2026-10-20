<?php

namespace Webkul\UnifiedInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\UnifiedInbox\Models\Conversation;
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\UnifiedInbox\Services\InboxHistorySync;
use Webkul\UnifiedInbox\Services\MetaGraphClient;
use Webkul\UnifiedInbox\Services\MetaOAuthCoordinator;

class ChannelController extends Controller
{
    public function __construct(
        protected ChannelConnectionRepository $channelRepository,
        protected MetaGraphClient $metaClient,
        protected CentralWebhookRouter $router,
        protected InboxHistorySync $historySync,
        protected MetaOAuthCoordinator $oauth
    ) {}

    public function index(Request $request): View
    {
        $currentTenant = $this->requireCurrentTenant();
        $this->redeemMetaTicket($request, $currentTenant);

        if ($request->filled('meta_error')) {
            session()->flash('error', 'Failed to connect Meta channels: '.(string) $request->query('meta_error'));
        }

        $oauthStates = [];
        $returnUrl = $this->oauth->tenantChannelsReturnUrl($currentTenant);

        foreach (['whatsapp', 'messenger', 'instagram'] as $channel) {
            $state = Str::random(64);
            $oauthStates[$channel] = $state;
            session()->put('unified_inbox.meta_oauth_states.'.$channel, [
                'hash' => hash('sha256', $state),
                'tenant_id' => $currentTenant->id,
                'user_id' => auth()->guard('user')->id(),
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);
            $this->oauth->putOAuthState($state, [
                'tenant_id' => (int) $currentTenant->id,
                'user_id' => (int) auth()->guard('user')->id(),
                'channel' => $channel,
                'return_url' => $returnUrl,
            ]);
        }

        $connections = $this->channelRepository->all();

        $channels = [
            'whatsapp' => [
                'key' => 'whatsapp',
                'title' => trans('unified_inbox::app.channels.whatsapp-title'),
                'desc' => trans('unified_inbox::app.channels.whatsapp-desc'),
                'icon' => 'icon-whatsapp',
                'color' => '#25D366',
                'connection' => $this->channelRepository->preferredForChannel($connections, 'whatsapp'),
            ],
            'messenger' => [
                'key' => 'messenger',
                'title' => trans('unified_inbox::app.channels.messenger-title'),
                'desc' => trans('unified_inbox::app.channels.messenger-desc'),
                'icon' => 'icon-messenger',
                'color' => '#0084FF',
                'connection' => $this->channelRepository->preferredForChannel($connections, 'messenger'),
            ],
            'instagram' => [
                'key' => 'instagram',
                'title' => trans('unified_inbox::app.channels.instagram-title'),
                'desc' => trans('unified_inbox::app.channels.instagram-desc'),
                'icon' => 'icon-instagram',
                'color' => '#E1306C',
                'connection' => $this->channelRepository->preferredForChannel($connections, 'instagram'),
            ],
        ];

        return view('unified_inbox::admin.channels.index', [
            'channels' => $channels,
            'centralWebhookUrl' => $this->router->getCentralWebhookUrl(),
            'centralOAuthRedirectUrl' => $this->oauth->getCentralOAuthRedirectUrl(),
            'oauthStates' => $oauthStates,
            'pendingSelection' => $this->pendingSelectionForView($currentTenant),
            'allowManualConnect' => (bool) config('unified-inbox.meta.allow_manual_connect'),
            'metaConfig' => [
                'app_id' => (string) config('unified-inbox.meta.app_id'),
                'version' => (string) config('unified-inbox.meta.version', 'v21.0'),
                'whatsapp_config_id' => (string) config('unified-inbox.meta.whatsapp_config_id'),
                'messaging_config_id' => (string) config('unified-inbox.meta.messaging_config_id'),
            ],
        ]);
    }

    /**
     * Handle Meta OAuth / Embedded Signup callback.
     */
    public function callback(Request $request): RedirectResponse
    {
        $currentTenant = $this->requireCurrentTenant();
        $channel = $this->consumeOAuthState($request, $currentTenant);
        $code = (string) $request->input('code');

        if (! $code) {
            session()->flash('error', 'Authorization code not provided by Meta.');

            return redirect()->route('admin.unified_inbox.channels.index');
        }

        try {
            // Prefer the single central OAuth redirect URI (same for every tenant/channel).
            $tokenData = $this->metaClient->exchangeCodeForToken(
                $code,
                $this->oauth->getCentralOAuthRedirectUrl()
            );
            $accessToken = (string) ($tokenData['access_token'] ?? '');

            if (! $accessToken) {
                throw new Exception('No access token returned from Meta.');
            }

            return $this->finishAuthorizedConnect($request, $currentTenant, $channel, $accessToken);
        } catch (Exception $e) {
            session()->flash('error', 'Failed to connect Meta channels: '.$e->getMessage());
        }

        return redirect()->route('admin.unified_inbox.channels.index');
    }

    /**
     * Development / App Review path: FB.login returns a short-lived user token (no code/redirect_uri).
     */
    public function acceptUserToken(Request $request): RedirectResponse
    {
        abort_unless((bool) config('unified-inbox.meta.allow_manual_connect'), 403);

        $currentTenant = $this->requireCurrentTenant();
        $channel = $this->consumeOAuthState($request, $currentTenant);
        $shortToken = (string) $request->input('access_token');

        if ($shortToken === '' || $channel === 'whatsapp') {
            session()->flash('error', 'Meta authorization token was missing.');

            return redirect()->route('admin.unified_inbox.channels.index');
        }

        try {
            $tokenData = $this->metaClient->exchangeShortLivedToken($shortToken);
            $accessToken = (string) ($tokenData['access_token'] ?? $shortToken);

            return $this->finishAuthorizedConnect($request, $currentTenant, $channel, $accessToken);
        } catch (Exception $e) {
            session()->flash('error', 'Failed to connect Meta channels: '.$e->getMessage());
        }

        return redirect()->route('admin.unified_inbox.channels.index');
    }

    protected function finishAuthorizedConnect(Request $request, Tenant $currentTenant, string $channel, string $accessToken): RedirectResponse
    {
        try {
            $this->ensureInboundDelivery();

            $wabaId = (string) $request->input('waba_id');
            $phoneNumberId = (string) $request->input('phone_number_id');
            $connectedChannel = false;

            if ($channel === 'whatsapp' && ($wabaId === '' || $phoneNumberId === '')) {
                throw new Exception('Meta did not return the WhatsApp signup account details.');
            }

            // 1. WhatsApp Connection if WABA / phone number present
            if ($wabaId || $phoneNumberId) {
                if ($wabaId) {
                    $phoneNumbers = $this->metaClient->getWhatsAppPhoneNumbers($wabaId, $accessToken);
                    $targetPhone = $phoneNumberId
                        ? collect($phoneNumbers)->firstWhere('id', $phoneNumberId)
                        : ($phoneNumbers[0] ?? null);
                } else {
                    $targetPhone = $this->metaClient->getWhatsAppPhoneNumber($phoneNumberId, $accessToken);
                }

                if (! is_array($targetPhone) || empty($targetPhone['id'])) {
                    throw new Exception('Meta did not return an authorized WhatsApp phone number.');
                }

                $extId = (string) ($targetPhone['id'] ?? $phoneNumberId);
                $channelName = (string) ($targetPhone['verified_name'] ?? $targetPhone['display_phone_number'] ?? 'WhatsApp');

                $this->channelRepository->updateOrCreate(
                    ['channel' => 'whatsapp', 'external_id' => $extId],
                    [
                        'name' => $channelName,
                        'status' => 'connected',
                        'credentials' => [
                            'access_token' => $accessToken,
                            'waba_id' => $wabaId,
                            'phone_number_id' => $extId,
                            'display_phone_number' => $targetPhone['display_phone_number'] ?? '',
                        ],
                        'metadata' => [
                            'quality_rating' => $targetPhone['quality_rating'] ?? 'GREEN',
                        ],
                    ]
                );

                // Register with central landlord router
                $this->router->registerRoute($currentTenant->id, 'whatsapp', $extId, $wabaId, $channelName, (string) ($targetPhone['display_phone_number'] ?? '') ?: null);
                $this->retireOtherConnections($currentTenant, 'whatsapp', [$extId]);
                $connectedChannel = true;

                if ($wabaId) {
                    try {
                        $this->metaClient->subscribeWabaApps(
                            $wabaId,
                            $accessToken,
                            $this->router->getMetaWebhookUrl(),
                            (string) config('unified-inbox.meta.verify_token')
                        );
                    } catch (Exception $e) {
                        Log::warning('Unified Inbox could not subscribe the WhatsApp account to webhooks.', [
                            'reason' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // 2. Messenger / Instagram: list assets, then let the user pick from a dropdown.
            if (in_array($channel, ['messenger', 'instagram'], true)) {
                $preferredPageIds = collect($request->input('page_ids', []))
                    ->merge([(string) $request->input('page_id', '')])
                    ->map(fn ($id) => (string) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                try {
                    $pages = $this->metaClient->getFacebookPages($accessToken, $preferredPageIds);
                } catch (Exception $e) {
                    Log::warning('Unified Inbox could not list Facebook Pages after Meta signup.', [
                        'channel' => $channel,
                        'reason' => $e->getMessage(),
                    ]);

                    throw new Exception('Could not load Facebook Pages from Meta: '.$e->getMessage(), 0, $e);
                }

                $assets = $this->oauth->buildSelectableAssets($channel, $pages, $accessToken);

                if ($assets === []) {
                    $hint = $this->metaPageDiscoveryHint($accessToken);

                    throw new Exception($channel === 'messenger'
                        ? 'No Facebook Pages with messaging access were found for this login. Confirm pages_show_list, pages_messaging, pages_manage_metadata (and business_management if needed) are granted.'.$hint
                        : 'No Instagram professional accounts linked to a Facebook Page were found for this login. Link IG in Meta Business Suite, then reconnect.'.$hint);
                }

                $this->storePendingSelection($currentTenant, $channel, $assets);
                session()->flash('success', 'Select a '.($channel === 'instagram' ? 'Instagram account' : 'Facebook Page').' to finish connecting.');

                return redirect()->route('admin.unified_inbox.channels.index');
            }

            if (! $connectedChannel) {
                throw new Exception('Meta did not return an account for the selected channel.');
            }

            session()->flash('success', 'Meta channels connected successfully via Embedded Signup.');
        } catch (Exception $e) {
            session()->flash('error', 'Failed to connect Meta channels: '.$e->getMessage());
        }

        return redirect()->route('admin.unified_inbox.channels.index');
    }

    /**
     * Bind the Page / Instagram asset chosen from the post-OAuth dropdown.
     */
    public function completeSelection(Request $request): RedirectResponse
    {
        $currentTenant = $this->requireCurrentTenant();
        $validated = $request->validate([
            'selection_id' => 'required|string|size:64',
            'asset_id' => 'required|string|max:64',
        ]);

        $pending = $this->pullPendingSelection($currentTenant, $validated['selection_id']);

        if ($pending === null) {
            session()->flash('error', 'This Meta connection session expired. Click Connect and try again.');

            return redirect()->route('admin.unified_inbox.channels.index');
        }

        $selected = collect($pending['assets'])->firstWhere('id', (string) $validated['asset_id']);

        if (! is_array($selected)) {
            session()->flash('error', 'Selected account is not available. Click Connect and try again.');

            return redirect()->route('admin.unified_inbox.channels.index');
        }

        try {
            $this->ensureInboundDelivery();
            $this->activateSelectedAsset($currentTenant, (string) $pending['channel'], $selected);
            session()->flash('success', ucfirst((string) $pending['channel']).' channel connected successfully.');
        } catch (Exception $e) {
            session()->flash('error', 'Failed to connect Meta channels: '.$e->getMessage());
        }

        return redirect()->route('admin.unified_inbox.channels.index');
    }

    public function cancelSelection(Request $request): RedirectResponse
    {
        $currentTenant = $this->requireCurrentTenant();
        $pending = session()->get('unified_inbox.meta_pending_selection');

        if (is_array($pending) && (int) ($pending['tenant_id'] ?? 0) === (int) $currentTenant->id) {
            session()->forget('unified_inbox.meta_pending_selection');
        }

        session()->flash('success', 'Meta account selection cancelled.');

        return redirect()->route('admin.unified_inbox.channels.index');
    }

    /**
     * Manual / Sandbox credential submission for Development mode and App Review demos.
     */
    public function manualConnect(Request $request): RedirectResponse
    {
        abort_unless((bool) config('unified-inbox.meta.allow_manual_connect'), 403);

        $currentTenant = $this->requireCurrentTenant();

        $validated = $request->validate([
            'channel' => 'required|in:whatsapp,messenger,instagram',
            'external_id' => 'required|string|max:100',
            'name' => 'required|string|max:191',
            'access_token' => 'required|string',
            'extra_id' => 'nullable|string|max:100', // e.g., WABA ID or Page ID
        ]);

        $credentials = [
            'access_token' => $validated['access_token'],
        ];

        if ($validated['channel'] === 'whatsapp') {
            $credentials['phone_number_id'] = $validated['external_id'];
            $credentials['waba_id'] = $validated['extra_id'] ?? '';
        } elseif ($validated['channel'] === 'messenger') {
            $credentials['page_id'] = $validated['external_id'];
        } elseif ($validated['channel'] === 'instagram') {
            $credentials['instagram_id'] = $validated['external_id'];
            $credentials['page_id'] = $validated['extra_id'] ?? '';
        }

        $this->channelRepository->updateOrCreate(
            ['channel' => $validated['channel'], 'external_id' => $validated['external_id']],
            [
                'name' => $validated['name'],
                'status' => 'connected',
                'credentials' => $credentials,
                'metadata' => ['mode' => 'manual'],
            ]
        );

        $this->router->registerRoute(
            $currentTenant->id,
            $validated['channel'],
            $validated['external_id'],
            $validated['extra_id'] ?? null,
            $validated['name']
        );

        $this->retireOtherConnections($currentTenant, $validated['channel'], [$validated['external_id']]);

        $this->subscribeManualConnection($validated['channel'], $credentials, $validated['external_id']);

        session()->flash('success', ucfirst($validated['channel']).' channel connected successfully.');

        return redirect()->route('admin.unified_inbox.channels.index');
    }

    /**
     * Dedicated per-channel logs page: number/account history plus recent
     * message and delivery-status events stored in the tenant database.
     */
    public function logs(string $channel): View
    {
        $this->requireCurrentTenant();

        $connections = $this->channelRepository->all();

        $history = array_values(array_filter(
            $this->numberHistory($connections),
            fn ($row) => $row['channel'] === $channel
        ));

        $events = \Webkul\UnifiedInbox\Models\Message::query()
            ->with('conversation:id,channel,account_id,customer_name')
            ->whereHas('conversation', fn ($q) => $q->where('channel', $channel))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(50);

        $stats = \Webkul\UnifiedInbox\Models\Message::query()
            ->whereHas('conversation', fn ($q) => $q->where('channel', $channel))
            ->selectRaw('direction, status, COUNT(*) as total')
            ->groupBy('direction', 'status')
            ->get();

        return view('unified_inbox::admin.channels.logs', [
            'channel' => $channel,
            'title' => trans('unified_inbox::app.channels.'.$channel.'-title'),
            'history' => $history,
            'events' => $events,
            'stats' => $stats,
            'webhookUrl' => $this->router->getCentralWebhookUrl(),
        ]);
    }

    /**
     * Current + archived numbers/accounts with dates and conversation counts (never deleted).
     */
    protected function numberHistory($connections): array
    {
        $counts = Conversation::query()
            ->selectRaw('channel, account_id, COUNT(*) as total')
            ->groupBy('channel', 'account_id')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->channel.'|'.$r->account_id => (int) $r->total]);

        return $connections
            ->reject(fn ($c) => str_starts_with((string) $c->external_id, 'demo-') && $c->status !== 'connected' && ! $counts->has($c->channel.'|'.$c->external_id))
            ->sortBy([['channel', 'asc'], [fn ($c) => $c->status === 'connected' ? 0 : 1, 'asc'], ['id', 'desc']])
            ->map(fn ($c) => [
                'channel' => $c->channel,
                'label' => $c->numberLabel(),
                'external_id' => $c->external_id,
                'waba_id' => ($c->credentials ?? [])['waba_id'] ?? null,
                'current' => $c->status === 'connected',
                'connected_at' => $c->connected_at ?? $c->created_at,
                'disconnected_at' => $c->status === 'connected' ? null : ($c->disconnected_at ?? null),
                'conversations' => $counts[$c->channel.'|'.$c->external_id] ?? 0,
            ])
            ->values()
            ->all();
    }

    /**
     * Manual connect: subscribe the app to webhooks the same way Embedded Signup does
     * (WABA subscribed_apps for WhatsApp; Page subscribed_apps for Messenger/Instagram). Best effort.
     */
    protected function subscribeManualConnection(string $channel, array $credentials, string $externalId): void
    {
        $token = (string) ($credentials['access_token'] ?? '');

        if ($token === '' || str_starts_with($externalId, 'demo-')) {
            return;
        }

        try {
            if ($channel === 'whatsapp' && filled($credentials['waba_id'] ?? null)) {
                $this->metaClient->subscribeWabaApps(
                    (string) $credentials['waba_id'],
                    $token,
                    $this->router->getMetaWebhookUrl(),
                    (string) config('unified-inbox.meta.verify_token')
                );
            } elseif (in_array($channel, ['messenger', 'instagram'], true) && filled($credentials['page_id'] ?? null)) {
                $this->metaClient->subscribePageApps((string) $credentials['page_id'], $token);
            }
        } catch (Exception $e) {
            Log::warning('Unified Inbox could not subscribe a manually connected channel to webhooks.', [
                'channel' => $channel,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    public function disconnect(int $id): RedirectResponse
    {
        $currentTenant = $this->requireCurrentTenant();
        $connection = $this->channelRepository->find($id);

        if ($connection) {
            $this->router->deactivateRoute($currentTenant->id, $connection->channel, $connection->external_id);

            $connection->update([
                'status' => 'disconnected',
            ]);
            session()->flash('success', 'Channel disconnected.');
        }

        return redirect()->route('admin.unified_inbox.channels.index');
    }

    /**
     * One account is shown per channel. Older connections for that channel are disconnected.
     */
    protected function retireOtherConnections(Tenant $tenant, string $channel, array $keepExternalIds): void
    {
        $others = $this->channelRepository->getModel()->newQuery()
            ->where('channel', $channel)
            ->whereNotIn('external_id', $keepExternalIds)
            ->where('status', 'connected')
            ->get();

        foreach ($others as $other) {
            $this->router->deactivateRoute($tenant->id, $other->channel, $other->external_id);

            $other->update([
                'status' => 'disconnected',
            ]);
        }
    }

    protected function ensureInboundDelivery(): void
    {
        try {
            $this->metaClient->registerPlatformWebhooks(
                $this->router->getCentralWebhookUrl(),
                (string) config('unified-inbox.meta.verify_token')
            );
        } catch (Exception $e) {
            Log::warning('Unified Inbox could not register the Meta app webhook.', [
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<int, string>  $externalIds
     */
    protected function syncConnected(string $channel, array $externalIds): void
    {
        $connections = $this->channelRepository->getModel()->newQuery()
            ->where('channel', $channel)
            ->whereIn('external_id', $externalIds)
            ->where('status', 'connected')
            ->get();

        foreach ($connections as $connection) {
            $this->historySync->sync($connection);
        }
    }

    protected function metaPageDiscoveryHint(string $accessToken): string
    {
        try {
            $info = $this->metaClient->describeTokenAssets($accessToken);
            $scopes = $info['scopes'] !== [] ? implode(', ', $info['scopes']) : 'none';
            $granular = $info['granular'] !== []
                ? collect($info['granular'])->map(fn ($row) => $row['scope'].'('.$row['targets'].')')->implode(', ')
                : 'none';

            Log::warning('Unified Inbox Messenger connect found no Pages.', [
                'scopes' => $info['scopes'],
                'granular' => $info['granular'],
            ]);

            return " Token scopes: {$scopes}. Granular targets: {$granular}.";
        } catch (Exception $e) {
            return ' Token inspection failed: '.$e->getMessage();
        }
    }

    protected function redeemMetaTicket(Request $request, Tenant $tenant): void
    {
        $ticket = (string) $request->query('meta_ticket', '');

        if ($ticket === '') {
            return;
        }

        $payload = $this->oauth->pullSelectionTicket($ticket);

        if (! is_array($payload)
            || (int) ($payload['tenant_id'] ?? 0) !== (int) $tenant->id
            || (int) ($payload['user_id'] ?? 0) !== (int) auth()->guard('user')->id()
            || empty($payload['assets'])
        ) {
            session()->flash('error', 'Meta account selection expired. Click Connect and try again.');

            return;
        }

        $this->storePendingSelection($tenant, (string) $payload['channel'], $payload['assets']);
        session()->flash('success', 'Select a '.(((string) $payload['channel'] === 'instagram') ? 'Instagram account' : 'Facebook Page').' to finish connecting.');
    }

    /**
     * @param  list<array{id: string, name: string, page_id: string, access_token: string, instagram_id?: string}>  $assets
     */
    protected function storePendingSelection(Tenant $tenant, string $channel, array $assets): void
    {
        session()->put('unified_inbox.meta_pending_selection', [
            'selection_id' => bin2hex(random_bytes(32)),
            'channel' => $channel,
            'tenant_id' => $tenant->id,
            'user_id' => auth()->guard('user')->id(),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'assets' => $assets,
        ]);
    }

    /**
     * @return array{selection_id: string, channel: string, assets: list<array{id: string, name: string}>}|null
     */
    protected function pendingSelectionForView(Tenant $tenant): ?array
    {
        $pending = session()->get('unified_inbox.meta_pending_selection');

        if (! is_array($pending)
            || (int) ($pending['tenant_id'] ?? 0) !== (int) $tenant->id
            || (int) ($pending['user_id'] ?? 0) !== (int) auth()->guard('user')->id()
            || (int) ($pending['expires_at'] ?? 0) < now()->timestamp
            || empty($pending['assets'])
            || empty($pending['selection_id'])
        ) {
            if (is_array($pending)) {
                session()->forget('unified_inbox.meta_pending_selection');
            }

            return null;
        }

        return [
            'selection_id' => (string) $pending['selection_id'],
            'channel' => (string) $pending['channel'],
            'assets' => collect($pending['assets'])
                ->map(fn ($asset) => [
                    'id' => (string) ($asset['id'] ?? ''),
                    'name' => (string) ($asset['name'] ?? $asset['id'] ?? ''),
                ])
                ->filter(fn ($asset) => $asset['id'] !== '')
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{selection_id: string, channel: string, assets: list<array<string, mixed>>}|null
     */
    protected function pullPendingSelection(Tenant $tenant, string $selectionId): ?array
    {
        $pending = session()->pull('unified_inbox.meta_pending_selection');

        if (! is_array($pending)
            || (string) ($pending['selection_id'] ?? '') !== $selectionId
            || (int) ($pending['tenant_id'] ?? 0) !== (int) $tenant->id
            || (int) ($pending['user_id'] ?? 0) !== (int) auth()->guard('user')->id()
            || (int) ($pending['expires_at'] ?? 0) < now()->timestamp
        ) {
            return null;
        }

        return $pending;
    }

    /**
     * @param  array{id: string, name: string, page_id: string, access_token: string, instagram_id?: string}  $selected
     */
    protected function activateSelectedAsset(Tenant $tenant, string $channel, array $selected): void
    {
        $token = (string) ($selected['access_token'] ?? '');
        $pageId = (string) ($selected['page_id'] ?? '');
        $name = (string) ($selected['name'] ?? $selected['id']);

        if ($token === '' || $pageId === '') {
            throw new Exception('Selected Meta account is missing credentials.');
        }

        if ($channel === 'messenger') {
            $externalId = (string) $selected['id'];

            $this->channelRepository->updateOrCreate(
                ['channel' => 'messenger', 'external_id' => $externalId],
                [
                    'name' => $name,
                    'status' => 'connected',
                    'credentials' => [
                        'access_token' => $token,
                        'page_id' => $pageId,
                    ],
                    'metadata' => [
                        'page_name' => $name,
                        'mode' => 'oauth_selection',
                    ],
                ]
            );

            $this->router->registerRoute($tenant->id, 'messenger', $externalId, null, $name);
            $this->retireOtherConnections($tenant, 'messenger', [$externalId]);

            try {
                $this->metaClient->subscribePageApps($pageId, $token);
            } catch (Exception $e) {
                Log::warning('Unified Inbox could not subscribe the Facebook Page to webhooks.', [
                    'reason' => $e->getMessage(),
                ]);
            }

            $this->syncConnected('messenger', [$externalId]);

            return;
        }

        $igId = (string) ($selected['instagram_id'] ?? $selected['id']);

        $this->channelRepository->updateOrCreate(
            ['channel' => 'instagram', 'external_id' => $igId],
            [
                'name' => $name,
                'status' => 'connected',
                'credentials' => [
                    'access_token' => $token,
                    'instagram_id' => $igId,
                    'page_id' => $pageId,
                ],
                'metadata' => [
                    'username' => ltrim($name, '@'),
                    'mode' => 'oauth_selection',
                ],
            ]
        );

        $this->router->registerRoute($tenant->id, 'instagram', $igId, null, $name);
        $this->retireOtherConnections($tenant, 'instagram', [$igId]);

        try {
            $this->metaClient->subscribePageApps($pageId, $token);
        } catch (Exception $e) {
            Log::warning('Unified Inbox could not subscribe the Facebook Page to webhooks.', [
                'reason' => $e->getMessage(),
            ]);
        }

        $this->syncConnected('instagram', [$igId]);
    }

    protected function requireCurrentTenant(): Tenant
    {
        $tenant = Tenant::current();

        abort_unless($tenant, 404);

        return $tenant;
    }

    protected function consumeOAuthState(Request $request, Tenant $tenant): string
    {
        $validated = $request->validate([
            'channel' => 'required|in:whatsapp,messenger,instagram',
            'state' => 'required|string|max:128',
        ]);
        $storedState = $request->session()->pull('unified_inbox.meta_oauth_states.'.$validated['channel']);

        abort_unless(
            is_array($storedState)
                && isset($storedState['hash'], $storedState['tenant_id'], $storedState['user_id'], $storedState['expires_at'])
                && (int) $storedState['tenant_id'] === (int) $tenant->id
                && (int) $storedState['user_id'] === (int) auth()->guard('user')->id()
                && (int) $storedState['expires_at'] >= now()->timestamp
                && hash_equals((string) $storedState['hash'], hash('sha256', $validated['state'])),
            403
        );

        return $validated['channel'];
    }
}
