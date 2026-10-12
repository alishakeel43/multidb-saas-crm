<?php

namespace Webkul\UnifiedInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\UnifiedInbox\Services\MetaGraphClient;

class ChannelController extends Controller
{
    public function __construct(
        protected ChannelConnectionRepository $channelRepository,
        protected MetaGraphClient $metaClient,
        protected CentralWebhookRouter $router
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
                'connection' => $connections->firstWhere('channel', 'whatsapp'),
            ],
            'messenger' => [
                'key' => 'messenger',
                'title' => trans('unified_inbox::app.channels.messenger-title'),
                'desc' => trans('unified_inbox::app.channels.messenger-desc'),
                'icon' => 'icon-messenger',
                'color' => '#0084FF',
                'connection' => $connections->firstWhere('channel', 'messenger'),
            ],
            'instagram' => [
                'key' => 'instagram',
                'title' => trans('unified_inbox::app.channels.instagram-title'),
                'desc' => trans('unified_inbox::app.channels.instagram-desc'),
                'icon' => 'icon-instagram',
                'color' => '#E1306C',
                'connection' => $connections->firstWhere('channel', 'instagram'),
            ],
        ];

        return view('unified_inbox::admin.channels.index', [
            'channels' => $channels,
            'centralWebhookUrl' => $this->router->getCentralWebhookUrl(),
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
                $this->router->registerRoute($currentTenant->id, 'whatsapp', $extId, $wabaId, $channelName);
                $connectedChannel = true;

                if ($wabaId) {
                    try {
                        $this->metaClient->subscribeWabaApps($wabaId, $accessToken);
                    } catch (Exception) {
                        // Keep connection even if auto-subscription needs app review
                    }
                }
            }

            // 2. Facebook Pages & Instagram Accounts
            if ($channel !== 'whatsapp') {
                try {
                    $pages = $this->metaClient->getFacebookPages($accessToken);

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
                            $connectedChannel = true;

                            try {
                                $this->metaClient->subscribePageApps($pageId, $pageToken);
                            } catch (Exception) {
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
                                $connectedChannel = true;
                            }
                        }
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

        session()->flash('success', ucfirst($validated['channel']).' channel connected successfully.');

        return redirect()->route('admin.unified_inbox.channels.index');
    }

    public function disconnect(int $id): RedirectResponse
    {
        $currentTenant = $this->requireCurrentTenant();
        $connection = $this->channelRepository->find($id);

        if ($connection) {
            $this->router->removeRoute($currentTenant->id, $connection->channel, $connection->external_id);

            $connection->update([
                'status' => 'disconnected',
            ]);
            session()->flash('success', 'Channel disconnected.');
        }

        return redirect()->route('admin.unified_inbox.channels.index');
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
