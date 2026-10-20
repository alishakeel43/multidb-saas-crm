<?php

namespace Webkul\UnifiedInbox\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * One Meta OAuth redirect URI for every tenant and channel (same idea as the central webhook).
 */
class MetaOAuthCoordinator
{
    public function __construct(
        protected CentralWebhookRouter $router
    ) {}

    /**
     * Register this URL once in Meta → Valid OAuth Redirect URIs.
     */
    public function getCentralOAuthRedirectUrl(): string
    {
        $explicit = config('unified-inbox.oauth_redirect_url');

        if (filled($explicit)) {
            return rtrim((string) $explicit, '/');
        }

        return preg_replace('#/api/meta/webhook$#', '/api/meta/oauth/callback', $this->router->getCentralWebhookUrl())
            ?: rtrim((string) config('app.url'), '/').'/api/meta/oauth/callback';
    }

    public function tenantChannelsReturnUrl(Tenant $tenant): string
    {
        $domain = $tenant->domains()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->value('domain');

        $host = $domain ?: request()?->getHost();
        $scheme = request()?->getScheme() ?: parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return rtrim("{$scheme}://{$host}", '/').'/admin/settings/channels';
    }

    /**
     * @param  array{tenant_id:int,user_id:int,channel:string,return_url:string}  $payload
     */
    public function putOAuthState(string $state, array $payload, int $minutes = 10): void
    {
        Cache::put($this->oauthKey($state), array_merge($payload, [
            'expires_at' => now()->addMinutes($minutes)->timestamp,
        ]), now()->addMinutes($minutes));
    }

    public function pullOAuthState(string $state): ?array
    {
        $payload = Cache::pull($this->oauthKey($state));

        if (! is_array($payload)) {
            return null;
        }

        if ((int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $assets
     */
    public function putSelectionTicket(array $payload, int $minutes = 10): string
    {
        $ticket = Str::random(64);

        Cache::put($this->selectionKey($ticket), array_merge($payload, [
            'expires_at' => now()->addMinutes($minutes)->timestamp,
        ]), now()->addMinutes($minutes));

        return $ticket;
    }

    public function pullSelectionTicket(string $ticket): ?array
    {
        $payload = Cache::pull($this->selectionKey($ticket));

        if (! is_array($payload)) {
            return null;
        }

        if ((int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array{id: string, name: string, page_id: string, access_token: string, instagram_id?: string}>
     */
    public function buildSelectableAssets(string $channel, array $pages, string $fallbackToken): array
    {
        $assets = [];

        foreach ($pages as $page) {
            $pageId = (string) ($page['id'] ?? '');
            $pageName = (string) ($page['name'] ?? 'Facebook Page');
            $pageToken = (string) ($page['access_token'] ?? $fallbackToken);

            if ($pageId === '' || $pageToken === '') {
                continue;
            }

            if ($channel === 'messenger') {
                $assets[] = [
                    'id' => $pageId,
                    'name' => $pageName,
                    'page_id' => $pageId,
                    'access_token' => $pageToken,
                ];

                continue;
            }

            $igAccount = $page['instagram_business_account'] ?? null;

            if (! is_array($igAccount) || empty($igAccount['id'])) {
                continue;
            }

            $igId = (string) $igAccount['id'];
            $igUsername = (string) ($igAccount['username'] ?? $pageName);

            $assets[] = [
                'id' => $igId,
                'name' => '@'.$igUsername,
                'page_id' => $pageId,
                'access_token' => $pageToken,
                'instagram_id' => $igId,
            ];
        }

        return $assets;
    }

    protected function oauthKey(string $state): string
    {
        return 'unified_inbox.meta_oauth.'.hash('sha256', $state);
    }

    protected function selectionKey(string $ticket): string
    {
        return 'unified_inbox.meta_selection.'.hash('sha256', $ticket);
    }
}
