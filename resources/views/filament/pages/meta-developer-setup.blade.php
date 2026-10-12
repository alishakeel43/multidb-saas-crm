<x-filament-panels::page>
    <x-filament::section>
        <p class="text-sm text-gray-500">
            Configure this single central URL in your Meta App Dashboard. All tenant channels (WhatsApp, Messenger, Instagram)
            are resolved and routed to their isolated tenant databases via landlord routing.
        </p>
        <dl class="mt-4 grid gap-3 text-sm">
            <div>
                <dt class="font-semibold">Callback URL</dt>
                <dd><code class="font-mono">{{ $centralWebhookUrl }}</code></dd>
            </div>
            <div>
                <dt class="font-semibold">Verify Token</dt>
                <dd><code class="font-mono">{{ $verifyToken ?? 'Not configured' }}</code></dd>
            </div>
        </dl>
    </x-filament::section>
</x-filament-panels::page>
