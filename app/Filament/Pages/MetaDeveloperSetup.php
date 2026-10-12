<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;

/**
 * Super-admin only: central Meta webhook (multi-tenant routing) details.
 * Hidden from navigation; reachable by direct URL at /super-admin/meta-developer-setup.
 */
class MetaDeveloperSetup extends Page
{
    protected static string $view = 'filament.pages.meta-developer-setup';

    protected static ?string $slug = 'meta-developer-setup';

    protected static ?string $title = 'Centralized Meta Developer Setup (Multi-Tenant Routing)';

    protected static bool $shouldRegisterNavigation = false;

    public string $centralWebhookUrl = '';

    public ?string $verifyToken = null;

    public function mount(CentralWebhookRouter $router): void
    {
        $this->centralWebhookUrl = $router->getCentralWebhookUrl();
        $this->verifyToken = config('unified-inbox.meta.verify_token') ?: null;
    }
}
