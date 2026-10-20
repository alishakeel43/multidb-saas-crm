<?php

namespace Webkul\UnifiedInbox\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\UnifiedInbox\Services\MetaGraphClient;
use Webkul\UnifiedInbox\Services\MetaOAuthCoordinator;

/**
 * Central Meta OAuth return (one URL for all tenants / channels).
 */
class MetaOAuthController extends Controller
{
    public function __construct(
        protected MetaOAuthCoordinator $oauth,
        protected MetaGraphClient $metaClient
    ) {}

    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');
        $error = (string) $request->query('error', '');

        $pending = $state !== '' ? $this->oauth->pullOAuthState($state) : null;
        $returnUrl = is_array($pending) ? (string) ($pending['return_url'] ?? '') : '';

        if ($returnUrl === '') {
            $returnUrl = rtrim((string) config('app.url'), '/');
        }

        if ($error !== '') {
            return redirect()->to($this->withQuery($returnUrl, [
                'meta_error' => (string) ($request->query('error_description') ?: $error),
            ]));
        }

        if (! is_array($pending) || $code === '') {
            return redirect()->to($this->withQuery($returnUrl, [
                'meta_error' => 'Meta authorization expired. Open Channels and click Connect again.',
            ]));
        }

        $tenant = Tenant::query()->find((int) ($pending['tenant_id'] ?? 0));

        if (! $tenant) {
            return redirect()->to($this->withQuery($returnUrl, [
                'meta_error' => 'Tenant for this Meta login could not be found.',
            ]));
        }

        try {
            $redirectUri = $this->oauth->getCentralOAuthRedirectUrl();
            $tokenData = $this->metaClient->exchangeCodeForToken($code, $redirectUri);
            $accessToken = (string) ($tokenData['access_token'] ?? '');

            if ($accessToken === '') {
                throw new Exception('No access token returned from Meta.');
            }

            $channel = (string) ($pending['channel'] ?? '');

            if (! in_array($channel, ['messenger', 'instagram'], true)) {
                throw new Exception('Use the tenant Channels page to finish WhatsApp Embedded Signup.');
            }

            $pages = $this->metaClient->getFacebookPages($accessToken);
            $assets = $this->oauth->buildSelectableAssets($channel, $pages, $accessToken);

            if ($assets === []) {
                throw new Exception('No Facebook Pages / Instagram accounts were returned for this Meta login.');
            }

            $ticket = $this->oauth->putSelectionTicket([
                'tenant_id' => $tenant->id,
                'user_id' => (int) ($pending['user_id'] ?? 0),
                'channel' => $channel,
                'assets' => $assets,
            ]);

            return redirect()->to($this->withQuery($returnUrl, [
                'meta_ticket' => $ticket,
            ]));
        } catch (Exception $e) {
            Log::warning('Central Meta OAuth callback failed.', [
                'reason' => $e->getMessage(),
            ]);

            return redirect()->to($this->withQuery($returnUrl, [
                'meta_error' => $e->getMessage(),
            ]));
        }
    }

    protected function withQuery(string $url, array $query): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($query);
    }
}
