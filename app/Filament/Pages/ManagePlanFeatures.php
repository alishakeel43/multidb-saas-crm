<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PlanResource;
use App\Models\Plan;
use App\Services\FeatureMatrixService;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManagePlanFeatures extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static string $view = 'filament.pages.manage-plan-features';

    protected static ?string $navigationGroup = 'Tenants & billing';

    protected static ?string $navigationLabel = 'Plan features';

    protected static ?string $slug = 'plan-features';

    protected static ?string $title = 'Plan features';

    protected static ?int $navigationSort = 14;

    protected ?string $heading = 'Plan features';

    protected ?string $subheading = 'Enable or disable CRM modules for a plan using category checkboxes, similar to role permissions.';

    /**
     * @var array{planId: int|null}
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
        $planId = Plan::query()->orderBy('sort_order')->orderBy('name')->value('id');

        $this->form->fill([
            'planId' => $planId,
        ]);

        $this->loadEnabledState();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('planId')
                    ->label('Plan')
                    ->options(fn () => Plan::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadEnabledState())
                    ->helperText(fn (): string => Plan::query()->exists()
                        ? 'Need a plan that is not listed? Create a new plan (opens in a new tab), then refresh this list.'
                        : 'No plans yet. Create a new plan, then come back and select it here.')
                    ->hintAction(
                        FormAction::make('createPlan')
                            ->label('Create new plan')
                            ->icon('heroicon-m-plus')
                            ->url(fn (): string => PlanResource::getUrl('create'))
                            ->openUrlInNewTab()
                    ),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save plan modules')
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
        $planId = $this->data['planId'] ?? null;

        if (! $planId) {
            Notification::make()->title('Select a plan first')->warning()->send();

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

        $plan = Plan::query()->findOrFail($planId);
        $matrix->syncPlan($plan, $this->enabled);

        Notification::make()
            ->title('Plan modules saved')
            ->body("Module access for {$plan->name} was updated.")
            ->success()
            ->send();
    }

    private function loadEnabledState(): void
    {
        $matrix = app(FeatureMatrixService::class);
        $planId = $this->data['planId'] ?? null;

        if (! $planId) {
            $this->enabled = collect(config('features', []))
                ->mapWithKeys(fn (array $definition, string $key): array => [
                    $key => (bool) ($definition['default_enabled'] ?? false),
                ])
                ->all();

            return;
        }

        $plan = Plan::query()->find($planId);
        $this->enabled = $plan ? $matrix->enabledMapForPlan($plan) : [];
    }
}
