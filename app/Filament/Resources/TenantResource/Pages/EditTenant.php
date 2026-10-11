<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Services\TenantSeedService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('seedBaseline')
                ->label('Seed CRM baseline')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Seed CRM baseline')
                ->modalDescription('Runs TenantCrmSeeder for this tenant (lookups only). Safe to re-run.')
                ->action(fn () => $this->runSeed(false)),
            Action::make('seedDemo')
                ->label('Seed demo + P0 data')
                ->icon('heroicon-m-beaker')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Seed demo CRM data')
                ->modalDescription('Adds sample orgs, contacts, leads, bookings, consents, quote approvals, scoring rules, and notifications. Does not delete existing data.')
                ->action(fn () => $this->runSeed(true)),
        ];
    }

    private function runSeed(bool $demo): void
    {
        try {
            app(TenantSeedService::class)->seed($this->record, $demo);

            Notification::make()
                ->title($demo
                    ? "Demo seed completed for {$this->record->slug}"
                    : "Baseline seed completed for {$this->record->slug}")
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title("Seed failed for {$this->record->slug}")
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
