@php
    $cards = [
        'open_leads' => ['label' => 'Open leads', 'tone' => 'bg-amber-50 text-amber-900 dark:bg-amber-900/30 dark:text-amber-100'],
        'won_leads' => ['label' => 'Won leads', 'tone' => 'bg-emerald-50 text-emerald-900 dark:bg-emerald-900/30 dark:text-emerald-100'],
        'lost_leads' => ['label' => 'Lost leads', 'tone' => 'bg-red-50 text-red-900 dark:bg-red-900/30 dark:text-red-100'],
        'conversion_rate' => ['label' => 'Win rate %', 'tone' => 'bg-sky-50 text-sky-900 dark:bg-sky-900/30 dark:text-sky-100', 'suffix' => '%'],
        'lead_value_open' => ['label' => 'Open pipeline value', 'tone' => 'bg-indigo-50 text-indigo-900 dark:bg-indigo-900/30 dark:text-indigo-100', 'money' => true],
        'avg_lead_score' => ['label' => 'Avg open lead score', 'tone' => 'bg-violet-50 text-violet-900 dark:bg-violet-900/30 dark:text-violet-100'],
        'quotes_pending_approval' => ['label' => 'Quotes pending approval', 'tone' => 'bg-orange-50 text-orange-900 dark:bg-orange-900/30 dark:text-orange-100'],
        'quotes_approved' => ['label' => 'Quotes approved', 'tone' => 'bg-emerald-50 text-emerald-900 dark:bg-emerald-900/30 dark:text-emerald-100'],
        'quotes_converted' => ['label' => 'Quotes converted to orders', 'tone' => 'bg-teal-50 text-teal-900 dark:bg-teal-900/30 dark:text-teal-100'],
        'bookings_upcoming' => ['label' => 'Upcoming bookings', 'tone' => 'bg-indigo-50 text-indigo-900 dark:bg-indigo-900/30 dark:text-indigo-100'],
        'consent_opt_ins' => ['label' => 'Consent opt-ins', 'tone' => 'bg-violet-50 text-violet-900 dark:bg-violet-900/30 dark:text-violet-100'],
    ];
@endphp

<x-admin::layouts>
    <x-slot:title>Sales reports</x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Sales reports</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    CRM metrics snapshot · generated {{ $generatedAt->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                </p>
            </div>

            <a href="{{ route('admin.sales-reports.export') }}" class="primary-button">Export CSV</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($cards as $key => $card)
                <div class="rounded-lg border border-gray-200 p-5 dark:border-gray-800 {{ $card['tone'] }}">
                    <p class="text-sm font-medium opacity-80">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight">
                        @if (! empty($card['money']))
                            {{ number_format((float) ($metrics[$key] ?? 0), 2) }}
                        @else
                            {{ number_format((float) ($metrics[$key] ?? 0), ! empty($card['suffix']) ? 1 : 0) }}{{ $card['suffix'] ?? '' }}
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Leads by source</h2>
            <div class="mt-3 space-y-2 text-sm dark:text-gray-200">
                @forelse ($sources as $source)
                    <div class="flex items-center justify-between rounded border border-gray-100 px-3 py-2 dark:border-gray-800">
                        <span>{{ $source['name'] }}</span>
                        <span class="font-semibold">{{ number_format($source['count']) }}</span>
                    </div>
                @empty
                    <p class="text-gray-500">No lead source data yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin::layouts>
