<x-filament-panels::page>
    <div class="saas-page-intro">
        <p>
            Protect Super Admin access with TOTP. Scan the QR code in your authenticator app,
            confirm with a one-time code, and store recovery codes offline.
        </p>
    </div>

    <form wire:submit="confirmSetup" class="space-y-6">
        {{ $this->form }}

        @if ($qrSvg)
            <div class="saas-mfa-qr">
                {!! $qrSvg !!}
            </div>
        @endif

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="button" wire:click="beginSetup" color="gray" icon="heroicon-m-qr-code">
                Start setup
            </x-filament::button>

            <x-filament::button type="submit" icon="heroicon-m-shield-check">
                Confirm code
            </x-filament::button>

            <x-filament::button
                type="button"
                color="danger"
                icon="heroicon-m-x-mark"
                wire:click="disable"
                wire:confirm="Disable two-factor authentication for this Super Admin account?"
            >
                Disable MFA
            </x-filament::button>
        </div>
    </form>

    @if ($recoveryCodes)
        <div class="saas-recovery">
            <h3>Recovery codes — store these securely</h3>
            <ul>
                @foreach ($recoveryCodes as $code)
                    <li>{{ $code }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-panels::page>
