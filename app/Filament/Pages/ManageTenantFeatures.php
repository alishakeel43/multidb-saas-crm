<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\FeatureMatrixService;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageTenantFeatures extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string $view = 'filament.pages.manage-tenant-features';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?string $navigationLabel = 'Feature overrides';

    protected static ?string $slug = 'feature-overrides';

    protected static ?string $title = 'Tenant feature overrides';

    protected static ?int $navigationSort = 15;

    protected ?string $heading = 'Tenant feature overrides';

    protected ?string $subheading = 'Override CRM modules for a specific tenant workspace with category checkboxes.';

    /**
     * @var array{tenantId: int|null}
     */
    public ?array $data = [];

    /**
     * @var array<string, bool>
     */
    public array $enabled = [];

    /**
     * @var array<string, array{label: string, features: array<string, array<string, mixed>>}>
     */
    public array $groups = [];

    public function mount(): void
    {
        $matrix = app(FeatureMatrixService::class);
        $this->groups = $matrix->groupedFeatures();
        $tenantId = Tenant::query()->orderBy('name')->value('id');

        $this->form->fill([
            'tenantId' => $tenantId,
        ]);

        $this->loadEnabledState();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tenantId')
                    ->label('Tenant workspace')
                    ->options(fn () => Tenant::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadEnabledState())
                    ->helperText(fn (): string => Tenant::query()->exists()
                        ? 'Need a workspace that is not listed? Create a new tenant (opens in a new tab), then refresh this list.'
                        : 'No tenants yet. Create a new tenant, then come back and select it here.')
                    ->hintAction(
                        FormAction::make('createTenant')
                            ->label('Create new tenant')
                            ->icon('heroicon-m-plus')
                            ->url(fn (): string => TenantResource::getUrl('create'))
                            ->openUrlInNewTab()
                    ),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save tenant modules')
                ->icon('heroicon-m-check')
                ->color('primary')
                ->action(fn () => $this->save()),
        ];
    }

    public function toggleCategory(string $categoryKey): void
    {
        $matrix = app(FeatureMatrixService::class);
        $enable = ! $matrix->categoryIsFullyEnabled($this->enabled, $categoryKey);
        $this->enabled = $matrix->applyCategoryState($this->enabled, $categoryKey, $enable);
    }

    public function categoryChecked(string $categoryKey): bool
    {
        return app(FeatureMatrixService::class)->categoryIsFullyEnabled($this->enabled, $categoryKey);
    }

    public function save(): void
    {
        $tenantId = $this->data['tenantId'] ?? null;

        if (! $tenantId) {
            Notification::make()->title('Select a tenant first')->warning()->send();

            return;
        }

        $matrix = app(FeatureMatrixService::class);
        $errors = $matrix->dependencyErrors($this->enabled);

        if ($errors !== []) {
            Notification::make()
                ->title('Required modules are missing')
                ->body(implode("\n", $errors))
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $tenant = Tenant::query()->findOrFail($tenantId);
        $matrix->syncTenant($tenant, $this->enabled);

        Notification::make()
            ->title('Tenant modules saved')
            ->body("Module overrides for {$tenant->name} were updated.")
            ->success()
            ->send();
    }

    private function loadEnabledState(): void
    {
        $matrix = app(FeatureMatrixService::class);
        $tenantId = $this->data['tenantId'] ?? null;

        if (! $tenantId) {
            $this->enabled = collect(config('features', []))
                ->mapWithKeys(fn (array $definition, string $key): array => [
                    $key => (bool) ($definition['default_enabled'] ?? false),
                ])
                ->all();

            return;
        }

        $tenant = Tenant::query()->find($tenantId);
        $this->enabled = $tenant ? $matrix->enabledMapForTenant($tenant) : [];
    }
}
