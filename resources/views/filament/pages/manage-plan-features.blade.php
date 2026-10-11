<x-filament-panels::page>
    <div class="saas-page-intro">
        <p>
            Pick a plan, then enable or disable CRM modules with checkboxes — the same idea as
            Krayin role permissions. Use the category checkbox to toggle every module in that group at once.
            Save is blocked if a module stays on while a required dependency (for example Leads for Quotes) is off.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        @forelse ($groups as $categoryKey => $group)
            <section class="saas-feature-category" wire:key="plan-cat-{{ $categoryKey }}">
                <header class="saas-feature-category__header">
                    <label class="saas-feature-check saas-feature-check--category">
                        <input
                            type="checkbox"
                            wire:key="plan-cat-check-{{ $categoryKey }}-{{ $this->categoryChecked($categoryKey) ? 'on' : 'off' }}"
                            wire:click.prevent="toggleCategory('{{ $categoryKey }}')"
                            @checked($this->categoryChecked($categoryKey))
                        >
                        <span>{{ $group['label'] }}</span>
                    </label>
                    <span class="saas-feature-category__meta">
                        {{ count($group['features']) }} modules
                    </span>
                </header>

                <div class="saas-feature-grid">
                    @foreach ($group['features'] as $featureKey => $feature)
                        <label class="saas-feature-check" wire:key="plan-feat-{{ $featureKey }}">
                            <input
                                type="checkbox"
                                wire:model.live="enabled.{{ $featureKey }}"
                            >
                            <span>
                                <span class="saas-feature-check__label">{{ $feature['label'] ?? $featureKey }}</span>
                                @if (! empty($feature['description']))
                                    <span class="saas-feature-check__hint">
                                        {{ $feature['description'] }}
                                    </span>
                                @elseif (! empty($feature['includes']))
                                    <span class="saas-feature-check__hint">
                                        Includes {{ implode(', ', $feature['includes']) }}
                                    </span>
                                @endif
                                @if (! empty($feature['depends_on']))
                                    <span class="saas-feature-check__hint">
                                        Requires {{ implode(', ', $feature['depends_on']) }}
                                    </span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="saas-empty">No features are configured.</div>
        @endforelse
    </form>
</x-filament-panels::page>
