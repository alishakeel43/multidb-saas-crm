<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\ResultStores\ResultStore;
use Throwable;

class SystemHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static string $view = 'filament.pages.system-health';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'System health';

    protected static ?string $title = 'System health';

    protected static ?int $navigationSort = 10;

    protected ?string $heading = 'System health';

    protected ?string $subheading = 'Operational checks for database, disk, environment, and billing configuration.';

    public array $results = [];

    public function mount(ResultStore $resultStore): void
    {
        $this->loadResults($resultStore);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runChecks')
                ->label('Run health checks')
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->action(fn () => $this->runChecks()),
        ];
    }

    public function runChecks(): void
    {
        try {
            Artisan::call(RunHealthChecksCommand::class);
        } catch (Throwable) {
            Artisan::call('health:check');
        }

        $this->loadResults(app(ResultStore::class));

        Notification::make()
            ->title('Health checks completed')
            ->body('Latest results are shown below.')
            ->success()
            ->send();
    }

    private function loadResults(ResultStore $resultStore): void
    {
        $latest = $resultStore->latestResults();

        $this->results = $latest
            ? $latest->storedCheckResults
                ->map(fn ($result) => [
                    'label' => $result->label,
                    'status' => (string) $result->status,
                    'shortSummary' => $result->shortSummary,
                    'notificationMessage' => $result->notificationMessage,
                ])
                ->all()
            : [];
    }
}
