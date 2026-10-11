<x-filament-panels::page>
    <div class="saas-page-intro">
        <p>
            <strong>Baseline</strong> reseeds CRM lookups (pipelines, attributes, workflows).
            <strong>Demo + P0</strong> also adds sample organizations, contacts, leads, bookings,
            consents, quote approvals, lead scoring rules, and notifications for process testing.
            Both modes are idempotent and do not delete existing records.
        </p>
    </div>

    <div class="saas-table-wrap">
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $tenant)
                    <tr wire:key="tenant-seeder-{{ $tenant['id'] }}">
                        <td>
                            <div class="font-semibold">{{ $tenant['name'] }}</div>
                            <div class="saas-mono text-xs opacity-70">{{ $tenant['slug'] }}</div>
                        </td>
                        <td>
                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30"
                                style="--c-50:var(--gray-50);--c-400:var(--gray-400);--c-600:var(--gray-600);">
                                {{ $tenant['status'] }}
                            </span>
                        </td>
                        <td style="text-align:right">
                            <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                <x-filament::button
                                    color="gray"
                                    size="sm"
                                    wire:click="seedTenant({{ $tenant['id'] }}, false)"
                                    wire:confirm="Run CRM baseline seed for {{ $tenant['slug'] }}?"
                                >
                                    Baseline
                                </x-filament::button>
                                <x-filament::button
                                    color="primary"
                                    size="sm"
                                    wire:click="seedTenant({{ $tenant['id'] }}, true)"
                                    wire:confirm="Run demo + P0 seed for {{ $tenant['slug'] }}? This adds sample CRM records if missing."
                                >
                                    Demo + P0
                                </x-filament::button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <div class="saas-empty" style="border:0;box-shadow:none;background:transparent;padding:2rem 1rem">
                                No tenants registered yet. Create a workspace first, then seed from here.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
