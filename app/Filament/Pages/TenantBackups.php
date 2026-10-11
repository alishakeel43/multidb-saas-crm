<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use App\Services\TenantBackupService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class TenantBackups extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static string $view = 'filament.pages.tenant-backups';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Tenant backups';

    protected static ?string $title = 'Tenant backups';

    protected static ?int $navigationSort = 20;

    protected ?string $heading = 'Tenant backups';

    protected ?string $subheading = 'Database and storage restore points for every provisioned workspace.';

    public array $backups = [];

    public function mount(TenantBackupService $backups): void
    {
        $this->backups = $backups->listBackups();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backupAll')
                ->label('Backup all tenants')
                ->icon('heroicon-m-cloud-arrow-up')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Backup all tenants')
                ->modalDescription('This creates a fresh database dump and storage snapshot for every tenant. Continue?')
                ->action(fn () => $this->backupAll(app(TenantBackupService::class))),
        ];
    }

    public function backupAll(TenantBackupService $backups): void
    {
        $count = 0;

        foreach (Tenant::query()->orderBy('id')->get() as $tenant) {
            try {
                $backups->backup($tenant);
                $count++;
            } catch (Throwable $exception) {
                Notification::make()
                    ->title("Backup failed for {$tenant->slug}")
                    ->body($exception->getMessage())
                    ->danger()
                    ->send();
            }
        }

        $this->backups = $backups->listBackups();

        Notification::make()
            ->title("Created {$count} tenant backup(s)")
            ->success()
            ->send();
    }
}
