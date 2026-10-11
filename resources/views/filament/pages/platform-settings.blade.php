<x-filament-panels::page>
    <div class="saas-page-intro">
        <p>
            Global defaults for branding, billing tax/trial, subscription enforcement, and
            notification sender identity. Changes apply platform-wide for new tenant provisioning.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" icon="heroicon-m-check">
                Save platform settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
