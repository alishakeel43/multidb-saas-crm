<x-admin::layouts>
    <x-slot:title>Attribution</x-slot>

    @include('marketing_automation::partials.nav')

    <p class="mb-4 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
        Web forms keep their existing fields and embed code. This page records UTM source, medium, campaign, content, and term from the form page or the referring URL, links an active campaign, and sets the lead source when a source with the same name already exists.
    </p>

    <div class="space-y-2">
        @forelse ($rows as $row)
            <div class="box-shadow rounded-lg bg-white p-3 text-sm dark:bg-gray-900 dark:text-white">
                Lead {{ $row->lead_id ?: '—' }} · person {{ $row->person_id ?: '—' }} · {{ $row->utm_source ?: '—' }} / {{ $row->utm_medium ?: '—' }} / {{ $row->utm_campaign ?: '—' }}
                · campaign {{ $row->campaign->name ?? '—' }}
            </div>
        @empty
            <p class="text-sm text-gray-500">No attributed leads yet.</p>
        @endforelse
    </div>
</x-admin::layouts>
