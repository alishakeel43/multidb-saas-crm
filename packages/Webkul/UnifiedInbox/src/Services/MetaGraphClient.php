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
     */
    public function exchangeCodeForToken(string $code, ?string $redirectUri = null): array
    {
        $appId = (string) config('unified-inbox.meta.app_id');
        $appSecret = (string) config('unified-inbox.meta.app_secret');

        $params = [
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'code' => $code,
        ];

        if ($redirectUri) {
            $params['redirect_uri'] = $redirectUri;
        }

        return $this->request('get', 'oauth/access_token', $params);
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
     */
    public function getFacebookPages(string $userToken): array
    {
        return $this->rows('me/accounts', [
            'fields' => 'id,name,access_token,tasks,instagram_business_account{id,username,name,profile_picture_url}',
        ], $userToken);
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
