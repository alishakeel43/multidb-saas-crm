<?php

namespace Webkul\UnifiedInbox\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Webkul\UnifiedInbox\Exceptions\MetaGraphException;

class MetaGraphClient
{
    public function request(string $method, string $path, array $params = [], ?string $token = null): array
    {
        $version = (string) config('unified-inbox.meta.version', 'v21.0');
        $url = 'https://graph.facebook.com/'.$version.'/'.ltrim($path, '/');

        $request = Http::acceptJson()->asJson();

        if ($token) {
            $request = $request->withToken($token);
        }

        $response = $method === 'get'
            ? $request->get($url, $params)
            : $request->send(strtoupper($method), $url, ['json' => $params]);

        return $this->payload($response);
    }

    /**
     * Form-encoded Graph call. Webhook subscription endpoints ignore a JSON body.
     */
    public function sendForm(string $method, string $path, array $params = [], ?string $token = null): array
    {
        $version = (string) config('unified-inbox.meta.version', 'v21.0');
        $url = 'https://graph.facebook.com/'.$version.'/'.ltrim($path, '/');
        $request = Http::acceptJson()->asForm();

        if ($token) {
            $request = $request->withToken($token);
        }

        $response = strtolower($method) === 'get'
            ? $request->get($url, $params)
            : $request->post($url, $params);

        return $this->payload($response);
    }

    protected function payload(Response $response): array
    {
        $payload = $response->json() ?? [];

        if ($response->failed() || isset($payload['error'])) {
            $error = is_array($payload['error'] ?? null) ? $payload['error'] : [];

            throw new MetaGraphException(
                (string) ($error['error_user_msg'] ?? $error['message'] ?? 'Meta Graph request failed.'),
                isset($error['code']) ? (int) $error['code'] : null,
                isset($error['error_subcode']) ? (int) $error['error_subcode'] : null,
                isset($error['type']) ? (string) $error['type'] : null,
                isset($error['fbtrace_id']) ? (string) $error['fbtrace_id'] : null,
                $response->status(),
            );
        }

        return is_array($payload) ? $payload : [];
    }

    public function rows(string $path, array $params = [], ?string $token = null): array
    {
        $payload = $this->request('get', $path, $params, $token);

        return is_array($payload['data'] ?? null) ? $payload['data'] : [];
    }

    /**
     * Exchange short-lived auth code from Facebook Login / Embedded Signup for an access token.
     *
     * Meta treats an omitted redirect_uri (null) and an explicit empty one ('') differently.
     */
    public function exchangeCodeForToken(string $code, ?string $redirectUri = ''): array
    {
        $params = [
            'client_id' => (string) config('unified-inbox.meta.app_id'),
            'client_secret' => (string) config('unified-inbox.meta.app_secret'),
            'code' => $code,
        ];

        if ($redirectUri !== null) {
            $params['redirect_uri'] = $redirectUri;
        }

        return $this->request('get', 'oauth/access_token', $params);
    }

    /**
     * WhatsApp Embedded Signup codes need redirect_uri omitted; Messenger/Instagram popup codes are
     * bound to the fallback_redirect_uri. Each candidate is tried only on a redirect_uri mismatch.
     */
    public function exchangeSignupCode(string $code, string $fallbackRedirectUri): array
    {
        $candidates = $fallbackRedirectUri === '' ? [null, ''] : [null, '', $fallbackRedirectUri];
        $last = array_key_last($candidates);

        foreach ($candidates as $index => $redirectUri) {
            try {
                return $this->exchangeCodeForToken($code, $redirectUri);
            } catch (MetaGraphException $e) {
                if ($index === $last || ($e->graphSubcode !== 36008 && ! str_contains($e->getMessage(), 'redirect_uri'))) {
                    throw $e;
                }
            }
        }

        return [];
    }

    /**
     * Upgrade a short-lived user token from FB.login to a long-lived token.
     */
    public function exchangeShortLivedToken(string $shortLivedToken): array
    {
        $appId = (string) config('unified-inbox.meta.app_id');
        $appSecret = (string) config('unified-inbox.meta.app_secret');

        return $this->request('get', 'oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);
    }

    /**
     * Inspect token details and granted permissions/targets.
     */
    public function debugToken(string $inputToken, ?string $appToken = null): array
    {
        $appId = (string) config('unified-inbox.meta.app_id');
        $appSecret = (string) config('unified-inbox.meta.app_secret');
        $appToken ??= $appId.'|'.$appSecret;

        $response = $this->request('get', 'debug_token', [
            'input_token' => $inputToken,
            'access_token' => $appToken,
        ]);

        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }

    /**
     * Retrieve WhatsApp phone numbers attached to a WABA.
     */
    public function getWhatsAppPhoneNumbers(string $wabaId, string $token): array
    {
        return $this->rows($wabaId.'/phone_numbers', [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status',
        ], $token);
    }

    /**
     * Retrieve a WhatsApp phone number directly to verify the authorized account.
     */
    public function getWhatsAppPhoneNumber(string $phoneNumberId, string $token): array
    {
        return $this->request('get', $phoneNumberId, [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status',
        ], $token);
    }

    /**
     * Subscribe app to WhatsApp Business Account webhooks.
     */
    public function subscribeWabaApps(string $wabaId, string $token, ?string $callbackUrl = null, ?string $verifyToken = null): array
    {
        $params = [];

        if ($callbackUrl) {
            $params['override_callback_uri'] = $callbackUrl;
            $params['verify_token'] = (string) $verifyToken;
        }

        return $this->sendForm('post', $wabaId.'/subscribed_apps', $params, $token);
    }

    /**
     * Point this Meta app's WhatsApp, Page, and Instagram webhooks at the CRM.
     */
    public function registerPlatformWebhooks(string $callbackUrl, string $verifyToken): void
    {
        $appId = (string) config('unified-inbox.meta.app_id');
        $secret = (string) config('unified-inbox.meta.app_secret');

        if ($appId === '' || $secret === '' || $callbackUrl === '' || $verifyToken === '') {
            throw new RuntimeException('Meta app webhook settings are incomplete.');
        }

        $objects = [
            'whatsapp_business_account' => 'messages',
            'page' => 'messages,messaging_postbacks,message_deliveries,message_reads,message_echoes',
            'instagram' => 'messages,messaging_postbacks,messaging_seen,message_reactions',
        ];

        foreach ($objects as $object => $fields) {
            $this->sendForm('post', $appId.'/subscriptions', [
                'object' => $object,
                'callback_url' => $callbackUrl,
                'verify_token' => $verifyToken,
                'fields' => $fields,
                'access_token' => $appId.'|'.$secret,
            ]);
        }
    }

    public function getWhatsAppTemplates(string $wabaId, string $token): array
    {
        return $this->rows($wabaId.'/message_templates', [
            'status' => 'APPROVED',
            'limit' => 100,
            'fields' => 'name,language,status,category,components',
        ], $token);
    }

    public function sendWhatsAppTemplate(string $phoneNumberId, string $to, string $name, string $language, array $components, string $token): array
    {
        $template = [
            'name' => $name,
            'language' => ['code' => $language],
        ];

        if ($components !== []) {
            $template['components'] = $components;
        }

        return $this->request('post', $phoneNumberId.'/messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => $template,
        ], $token);
    }

    public function getPageConversations(string $pageId, string $platform, string $token): array
    {
        return $this->rows($pageId.'/conversations', [
            'platform' => $platform,
            'limit' => 25,
            'fields' => 'id,updated_time,participants,messages.limit(20){id,message,from,created_time}',
        ], $token);
    }

    /**
     * Retrieve Facebook pages managed by the user along with connected Instagram business accounts.
     *
     * Facebook Login for Business / New Pages Experience often returns an empty /me/accounts list.
     * Fallbacks: Embedded Signup page_ids, debug_token granular_scopes, /me/assigned_pages,
     * and Business Manager owned/client pages.
     *
     * @param  list<string>  $preferredPageIds  Optional Page IDs from Embedded Signup session info.
     */
    public function getFacebookPages(string $userToken, array $preferredPageIds = []): array
    {
        $fields = 'id,name,access_token,tasks,instagram_business_account{id,username,name,profile_picture_url}';
        $preferredPageIds = array_values(array_unique(array_filter(array_map('strval', $preferredPageIds))));
        $seen = [];
        $merged = [];

        $append = function (array $pages) use (&$merged, &$seen): void {
            foreach ($pages as $page) {
                $id = (string) ($page['id'] ?? '');

                if ($id === '' || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $merged[] = $page;
            }
        };

        if ($preferredPageIds !== []) {
            $append($this->fetchPagesByIds($preferredPageIds, $fields, $userToken));
        }

        if ($merged === []) {
            $append($this->rows('me/accounts', ['fields' => $fields], $userToken));
        }

        if ($merged === []) {
            try {
                $append($this->rows('me/assigned_pages', ['fields' => $fields], $userToken));
            } catch (MetaGraphException) {
                // Permission may be missing; continue with other fallbacks.
            }
        }

        if ($merged === []) {
            $append($this->fetchPagesByIds($this->pageIdsFromGranularScopes($userToken), $fields, $userToken));
        }

        if ($merged === []) {
            $append($this->pagesFromBusinesses($fields, $userToken));
        }

        return $merged;
    }

    /**
     * Summarize token grants for connect-error diagnostics (no secrets).
     *
     * @return array{scopes: list<string>, granular: list<array{scope: string, targets: int}>}
     */
    public function describeTokenAssets(string $userToken): array
    {
        $debug = $this->debugToken($userToken);
        $scopes = array_values(array_filter(array_map('strval', (array) ($debug['scopes'] ?? []))));
        $granular = [];

        foreach ((array) ($debug['granular_scopes'] ?? []) as $scope) {
            if (! is_array($scope)) {
                continue;
            }

            $granular[] = [
                'scope' => (string) ($scope['scope'] ?? ''),
                'targets' => count((array) ($scope['target_ids'] ?? [])),
            ];
        }

        return [
            'scopes' => $scopes,
            'granular' => $granular,
        ];
    }

    /**
     * @return list<string>
     */
    public function pageIdsFromGranularScopes(string $userToken): array
    {
        $debug = $this->debugToken($userToken);
        $pageIds = [];

        foreach ((array) ($debug['granular_scopes'] ?? []) as $scope) {
            if (! is_array($scope)) {
                continue;
            }

            $scopeName = (string) ($scope['scope'] ?? '');

            // Prefer pages_* target IDs; also accept bare target_ids on messaging configs.
            $useTargets = $scopeName === ''
                || str_starts_with($scopeName, 'pages_')
                || in_array($scopeName, ['pages_messaging', 'pages_manage_metadata', 'pages_show_list', 'pages_read_engagement'], true);

            if (! $useTargets) {
                continue;
            }

            foreach ((array) ($scope['target_ids'] ?? []) as $id) {
                $id = (string) $id;

                if ($id !== '') {
                    $pageIds[$id] = true;
                }
            }
        }

        // PHP casts numeric string keys to ints; normalize back to strings for Graph IDs.
        return array_map('strval', array_keys($pageIds));
    }

    /**
     * @param  list<string>  $pageIds
     * @return list<array<string, mixed>>
     */
    protected function fetchPagesByIds(array $pageIds, string $fields, string $userToken): array
    {
        $pages = [];

        foreach ($pageIds as $pageId) {
            try {
                $page = $this->request('get', $pageId, ['fields' => $fields], $userToken);

                if (! empty($page['id']) && isset($page['access_token'])) {
                    $pages[] = $page;
                } elseif (! empty($page['id'])) {
                    // Some tokens return the Page without an embedded page token; still usable with user token.
                    $page['access_token'] = $page['access_token'] ?? $userToken;
                    $pages[] = $page;
                }
            } catch (MetaGraphException $e) {
                // Skip non-page target IDs or assets the token cannot read.
            }
        }

        return $pages;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function pagesFromBusinesses(string $fields, string $userToken): array
    {
        $pages = [];

        try {
            $businesses = $this->rows('me/businesses', ['fields' => 'id,name'], $userToken);
        } catch (MetaGraphException) {
            return [];
        }

        foreach ($businesses as $business) {
            $businessId = (string) ($business['id'] ?? '');

            if ($businessId === '') {
                continue;
            }

            foreach (['owned_pages', 'client_pages'] as $edge) {
                try {
                    foreach ($this->rows($businessId.'/'.$edge, ['fields' => $fields], $userToken) as $page) {
                        $pages[] = $page;
                    }
                } catch (MetaGraphException) {
                    continue;
                }
            }
        }

        return $pages;
    }

    /**
     * Subscribe app to Facebook Page webhook events (messages, postbacks, reads).
     */
    public function subscribePageApps(string $pageId, string $pageToken): array
    {
        return $this->request('post', $pageId.'/subscribed_apps', [
            'subscribed_fields' => ['messages', 'messaging_postbacks', 'message_deliveries', 'message_reads', 'message_echoes'],
        ], $pageToken);
    }

    /**
     * Send an outbound WhatsApp text message.
     */
    public function sendWhatsAppMessage(string $phoneNumberId, string $to, string $body, string $token): array
    {
        return $this->request('post', $phoneNumberId.'/messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ], $token);
    }

    /**
     * Send an outbound Messenger or Instagram message.
     */
    public function sendPageMessage(string $pageIdOrAccount, string $recipientId, string $body, string $token): array
    {
        return $this->request('post', $pageIdOrAccount.'/messages', [
            'recipient' => [
                'id' => $recipientId,
            ],
            'message' => [
                'text' => $body,
            ],
            'messaging_type' => 'RESPONSE',
        ], $token);
    }

    /**
     * Upload a local file to WhatsApp Cloud API media storage and return the media ID.
     */
    public function uploadWhatsAppMedia(string $phoneNumberId, string $path, string $filename, string $mime, string $token): string
    {
        $version = (string) config('unified-inbox.meta.version', 'v21.0');
        $stream = fopen($path, 'r');

        try {
            $response = Http::acceptJson()->withToken($token)->connectTimeout(5)->timeout(60)
                ->attach('file', $stream, $filename, ['Content-Type' => $mime])
                ->post('https://graph.facebook.com/'.$version.'/'.$phoneNumberId.'/media', [
                    'messaging_product' => 'whatsapp',
                    'type' => $mime,
                ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $id = (string) ($this->payload($response)['id'] ?? '');

        if ($id === '') {
            throw new RuntimeException('Meta did not confirm the media upload.');
        }

        return $id;
    }

    /**
     * Send an uploaded image/document (by media ID) with an optional caption.
     */
    public function sendWhatsAppMedia(string $phoneNumberId, string $to, string $type, string $mediaId, ?string $caption, ?string $filename, string $token): array
    {
        $media = ['id' => $mediaId];

        if (filled($caption)) {
            $media['caption'] = $caption;
        }

        if ($type === 'document' && filled($filename)) {
            $media['filename'] = $filename;
        }

        return $this->request('post', $phoneNumberId.'/messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $type,
            $type => $media,
        ], $token);
    }

    /**
     * Resolve a Messenger PSID or Instagram-scoped ID to a display name via the Page token.
     *
     * @return array{name: string, username: string, profile_pic: string}
     */
    public function messagingUserProfile(string $userId, string $pageToken, string $channel = 'messenger'): array
    {
        $fields = $channel === 'instagram'
            ? 'name,username,profile_pic'
            : 'name,first_name,last_name,profile_pic';

        $payload = $this->request('get', $userId, ['fields' => $fields], $pageToken);

        $name = trim((string) ($payload['name'] ?? ''));

        if ($name === '') {
            $name = trim(collect([
                $payload['first_name'] ?? null,
                $payload['last_name'] ?? null,
            ])->filter()->implode(' '));
        }

        if ($name === '') {
            $name = trim((string) ($payload['username'] ?? ''));
        }

        return [
            'name' => $name,
            'username' => (string) ($payload['username'] ?? ''),
            'profile_pic' => (string) ($payload['profile_pic'] ?? ''),
        ];
    }

    /**
     * Download inbound WhatsApp media by ID (bounded to 16 MB, Meta CDN hosts only).
     *
     * @return array{body: string, mime: string}
     */
    public function downloadWhatsAppMedia(string $mediaId, string $token): array
    {
        $limit = 16 * 1024 * 1024;
        $meta = $this->request('get', $mediaId, [], $token);
        $url = (string) ($meta['url'] ?? '');
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! (str_ends_with($host, '.fbsbx.com') || $host === 'graph.facebook.com' || str_ends_with($host, '.whatsapp.net')) || parse_url($url, PHP_URL_USER) || parse_url($url, PHP_URL_PORT)) {
            throw new RuntimeException('Unexpected media host.');
        }

        if ((int) ($meta['file_size'] ?? 0) > $limit) {
            throw new RuntimeException('Attachment exceeds size limit.');
        }

        $response = Http::withToken($token)->connectTimeout(5)->timeout(30)->withoutRedirecting()->get($url);

        if (! $response->successful() || strlen($response->body()) > $limit) {
            throw new RuntimeException('Media download failed.');
        }

        return [
            'body' => $response->body(),
            'mime' => (string) ($meta['mime_type'] ?? $response->header('Content-Type') ?: 'application/octet-stream'),
        ];
    }
}
