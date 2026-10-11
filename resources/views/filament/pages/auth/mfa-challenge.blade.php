<x-filament-panels::page.simple>
    <div class="saas-page-intro" style="margin-bottom:1.25rem;text-align:center;max-width:none">
        <p>
            Enter the 6-digit code from your authenticator app, or use a one-time recovery code.
        </p>
    </div>

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="[
                \Filament\Actions\Action::make('authenticate')
                    ->label('Verify and continue')
                    ->icon('heroicon-m-lock-closed')
                    ->submit('authenticate'),
            ]"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>
