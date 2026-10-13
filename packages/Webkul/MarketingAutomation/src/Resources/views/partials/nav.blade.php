@php
    $links = [
        'admin.marketing_automation.index' => 'Overview',
        'admin.marketing_automation.workflows' => 'Workflows',
        'admin.marketing_automation.campaigns' => 'Campaigns',
        'admin.marketing_automation.audiences' => 'Audiences',
        'admin.marketing_automation.sequences' => 'Sequences',
        'admin.marketing_automation.attributions' => 'Attribution',
        'admin.marketing_automation.engagement' => 'Engagement scoring',
        'admin.marketing_automation.deliverability' => 'Deliverability',
    ];
@endphp

<div class="mb-4 flex flex-wrap gap-2">
    @foreach ($links as $route => $label)
        <a
            class="rounded border px-3 py-1 text-sm dark:border-gray-700 dark:text-white {{ request()->routeIs($route) ? 'bg-blue-600 text-white' : '' }}"
            href="{{ route($route) }}"
        >{{ $label }}</a>
    @endforeach
</div>

@if (session('success'))
    <p class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('success') }}</p>
@endif

@if ($errors->any())
    <div class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-800">
        {{ $errors->first() }}
    </div>
@endif
