<x-admin::layouts>
    <x-slot:title>Marketing and Automation</x-slot>

    @include('marketing_automation::partials.nav')

    <h1 class="mb-2 text-2xl font-semibold dark:text-white">Marketing and Automation</h1>
    <p class="mb-4 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
        Branching workflows, campaign budgets and results, web-form attribution, scheduled email sequences, unsubscribe handling, and engagement scoring. Krayin workflows, web forms, and email templates stay in place; this module completes the missing marketing behavior beside them.
    </p>

    @unless ($ready)
        <p class="rounded bg-amber-50 px-3 py-2 text-sm text-amber-900">Run tenant migrations before using this module.</p>
    @endunless

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ([
            'Active workflows' => $workflows,
            'Active campaigns' => $campaigns,
            'Attributed leads' => $attributions,
            'Emails sent' => $sent,
            'Engagement points' => $engagement,
        ] as $label => $value)
            <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
                <div class="text-sm text-gray-500">{{ $label }}</div>
                <div class="text-2xl font-semibold dark:text-white">{{ $value }}</div>
            </div>
        @endforeach
    </div>
</x-admin::layouts>
