<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use App\Services\TenantSeedService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class TenantSeeders extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static string $view = 'filament.pages.tenant-seeders';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Tenant seeders';

    protected static ?string $title = 'Tenant seeders';

    protected static ?int $navigationSort = 21;

    protected ?string $heading = 'Tenant seeders';

    protected ?string $subheading = 'Run CRM baseline lookups, demo/P0 samples, or linked dummy data for every feature.';

    /** @var array<int, array{id: int, name: string, slug: string, status: string}> */
    public array $tenants = [];

    public function mount(): void
    {
        $this->refreshTenants();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('seedAllBaseline')
                ->label('Seed all (baseline)')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Seed CRM baseline for all tenants')
                ->modalDescription('Runs TenantCrmSeeder on every registered tenant (lookups only, repeatable). Continue?')
                ->action(fn () => $this->seedAll('baseline')),
            Action::make('seedAllDemo')
                ->label('Seed all (demo + P0)')
                ->icon('heroicon-m-beaker')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Seed demo CRM data for all tenants')
                ->modalDescription('Runs TenantDemoSeeder (orgs, contacts, leads, activities) plus P0 package samples. Safe to re-run; does not delete data. Continue?')
                ->action(fn () => $this->seedAll('demo')),
            Action::make('seedAllFeatures')
                ->label('Seed all (every feature)')
                ->icon('heroicon-m-squares-2x2')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Seed every feature for all tenants')
                ->modalDescription('Adds linked dummy data for products, inventory, mail, marketing, automation, web forms, telephony, knowledge, and unified inbox channels. Safe to re-run; does not delete data. Continue?')
                ->action(fn () => $this->seedAll('features')),
        ];
    }

    public function seedTenant(int $tenantId, string $mode = 'baseline'): void
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $demo = $mode === 'demo';
        $allFeatures = $mode === 'features';

        try {
            app(TenantSeedService::class)->seed($tenant, $demo, $allFeatures);

            Notification::make()
                ->title($this->successTitle($mode, $tenant->slug))
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title("Seed failed for {$tenant->slug}")
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function seedAll(string $mode = 'baseline'): void
    {
        $demo = $mode === 'demo';
        $allFeatures = $mode === 'features';
        $result = app(TenantSeedService::class)->seedAll($demo, $allFeatures);

        foreach ($result['failed'] as $failure) {
            Notification::make()
                ->title("Seed failed for {$failure['slug']}")
                ->body($failure['error'])
                ->danger()
                ->send();
        }

        Notification::make()
            ->title(match ($mode) {
                'features' => "All-features seed finished for {$result['succeeded']} tenant(s)",
                'demo' => "Demo seed finished for {$result['succeeded']} tenant(s)",
                default => "Baseline seed finished for {$result['succeeded']} tenant(s)",
            })
            ->success()
            ->send();

        $this->refreshTenants();
    }

    private function successTitle(string $mode, string $slug): string
    {
        return match ($mode) {
            'features' => "All-features seed completed for {$slug}",
            'demo' => "Demo seed completed for {$slug}",
            default => "Baseline seed completed for {$slug}",
        };
    }

    private function refreshTenants(): void
    {
        $this->tenants = Tenant::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status'])
            ->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status,
            ])
            ->all();
    }
}
