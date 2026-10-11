<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\MfaChallenge;
use App\Filament\Pages\ManagePlanFeatures;
use App\Filament\Pages\ManageTenantFeatures;
use App\Filament\Pages\ManageTwoFactorAuthentication;
use App\Filament\Pages\PlatformSettings;
use App\Filament\Pages\SuperAdminDashboard;
use App\Filament\Pages\SystemHealth;
use App\Filament\Pages\TenantBackups;
use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\RecentTenantsTable;
use App\Filament\Widgets\TenantGrowthChart;
use App\Http\Middleware\AuthenticateSuperAdmin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('super-admin')
            ->path('super-admin')
            ->authGuard('platform')
            ->login(Login::class)
            ->brandName('ZARIS')
            ->brandLogo(fn (): HtmlString => new HtmlString(
                '<div style="display:flex;align-items:center;gap:.65rem;line-height:1.1">'
                .'<span style="display:inline-flex;height:2rem;width:2rem;align-items:center;justify-content:center;border-radius:.65rem;background:linear-gradient(135deg,#0f766e,#115e59);color:#fff;font-size:.75rem;font-weight:800;letter-spacing:.04em">Z</span>'
                .'<span style="display:flex;flex-direction:column">'
                .'<span style="font-size:.95rem;font-weight:700;letter-spacing:-.03em;color:inherit">ZARIS</span>'
                .'<span style="font-size:.68rem;font-weight:500;letter-spacing:.04em;text-transform:uppercase;opacity:.65">Platform Console</span>'
                .'</span></div>'
            ))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'primary' => Color::Teal,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Figtree')
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::Full)
            ->navigationGroups([
                NavigationGroup::make('Operations')
                    ->label('Operations')
                    ->collapsed(false),
                NavigationGroup::make('Tenants & billing')
                    ->label('Tenants & billing')
                    ->collapsed(false),
                NavigationGroup::make('Platform')
                    ->label('Platform')
                    ->collapsed(false),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                SuperAdminDashboard::class,
                SystemHealth::class,
                PlatformSettings::class,
                TenantBackups::class,
                ManagePlanFeatures::class,
                ManageTenantFeatures::class,
                ManageTwoFactorAuthentication::class,
            ])
            ->routes(function (): void {
                Route::get('/mfa-challenge', MfaChallenge::class)
                    ->middleware(['throttle:10,1'])
                    ->name('auth.mfa-challenge');
            })
            ->widgets([
                PlatformStatsOverview::class,
                TenantGrowthChart::class,
                RecentTenantsTable::class,
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.hooks.saas-styles')->render(),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateSuperAdmin::class,
            ]);
    }
}
