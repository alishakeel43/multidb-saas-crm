<?php

namespace Webkul\UnifiedInbox\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

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

        $payload = $response->json() ?? [];

        if ($response->failed() || isset($payload['error'])) {
            $errorMsg = (string) ($payload['error']['message'] ?? 'Meta Graph request failed.');
            throw new RuntimeException($errorMsg);
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
    public function subscribeWabaApps(string $wabaId, string $token): array
    {
        return $this->request('post', $wabaId.'/subscribed_apps', [], $token);
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
}
