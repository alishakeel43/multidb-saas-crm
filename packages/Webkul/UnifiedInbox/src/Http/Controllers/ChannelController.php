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
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\UnifiedInbox\Services\InboxHistorySync;
use Webkul\UnifiedInbox\Services\MetaGraphClient;

class ChannelController extends Controller
{
    public function __construct(
        protected ChannelConnectionRepository $channelRepository,
        protected MetaGraphClient $metaClient,
        protected CentralWebhookRouter $router,
        protected InboxHistorySync $historySync
    ) {}

    public function index(): View
    {
        $currentTenant = $this->requireCurrentTenant();
        $oauthStates = [];

        foreach (['whatsapp', 'messenger', 'instagram'] as $channel) {
            $state = Str::random(64);
            $oauthStates[$channel] = $state;
            session()->put('unified_inbox.meta_oauth_states.'.$channel, [
                'hash' => hash('sha256', $state),
                'tenant_id' => $currentTenant->id,
                'user_id' => auth()->guard('user')->id(),
                'expires_at' => now()->addMinutes(10)->timestamp,
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
            'numberHistory' => $this->numberHistory($connections),
            'oauthStates' => $oauthStates,
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
            $tokenData = $this->metaClient->exchangeCodeForToken($code);
            $accessToken = (string) ($tokenData['access_token'] ?? '');

            if (! $accessToken) {
                throw new Exception('No access token returned from Meta.');
            }

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

            // 2. Facebook Pages & Instagram Accounts
            if ($channel !== 'whatsapp') {
                try {
                    $pages = $this->metaClient->getFacebookPages($accessToken);
                    $messengerIds = [];
                    $instagramIds = [];

                    foreach ($pages as $page) {
                        $pageId = (string) ($page['id'] ?? '');
                        $pageName = (string) ($page['name'] ?? 'Facebook Page');
                        $pageToken = (string) ($page['access_token'] ?? $accessToken);

                        if ($pageId) {
                            $this->channelRepository->updateOrCreate(
                                ['channel' => 'messenger', 'external_id' => $pageId],
                                [
                                    'name' => $pageName,
                                    'status' => 'connected',
                                    'credentials' => [
                                        'access_token' => $pageToken,
                                        'page_id' => $pageId,
                                    ],
                                    'metadata' => [
                                        'page_name' => $pageName,
                                    ],
                                ]
                            );

                            $this->router->registerRoute($currentTenant->id, 'messenger', $pageId, null, $pageName);
                            $messengerIds[] = $pageId;
                            $connectedChannel = true;

                            try {
                                $this->metaClient->subscribePageApps($pageId, $pageToken);
                            } catch (Exception $e) {
                                Log::warning('Unified Inbox could not subscribe the Facebook Page to webhooks.', [
                                    'reason' => $e->getMessage(),
                                ]);
                            }

                            // Check connected Instagram business account
                            $igAccount = $page['instagram_business_account'] ?? null;
                            if (is_array($igAccount) && ! empty($igAccount['id'])) {
                                $igId = (string) $igAccount['id'];
                                $igUsername = (string) ($igAccount['username'] ?? $pageName);

                                $this->channelRepository->updateOrCreate(
                                    ['channel' => 'instagram', 'external_id' => $igId],
                                    [
                                        'name' => '@'.$igUsername,
                                        'status' => 'connected',
                                        'credentials' => [
                                            'access_token' => $pageToken,
                                            'instagram_id' => $igId,
                                            'page_id' => $pageId,
                                        ],
                                        'metadata' => [
                                            'username' => $igUsername,
                                            'name' => $igAccount['name'] ?? $igUsername,
                                        ],
                                    ]
                                );

                                $this->router->registerRoute($currentTenant->id, 'instagram', $igId, null, '@'.$igUsername);
                                $instagramIds[] = $igId;
                                $connectedChannel = true;
                            }
                        }
                    }

                    if ($messengerIds !== []) {
                        $this->retireOtherConnections($currentTenant, 'messenger', $messengerIds);
                        $this->syncConnected('messenger', $messengerIds);
                    }

                    if ($instagramIds !== []) {
                        $this->retireOtherConnections($currentTenant, 'instagram', $instagramIds);
                        $this->syncConnected('instagram', $instagramIds);
                    }
                } catch (Exception) {
                }
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
     * Manual / Sandbox credential submission for dev / QA environments.
     */
    public function manualConnect(Request $request): RedirectResponse
    {
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
     * Current + archived numbers/accounts with dates and conversation counts (never deleted).
     */
    protected function numberHistory($connections): array
    {
        $counts = \Webkul\UnifiedInbox\Models\Conversation::query()
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
