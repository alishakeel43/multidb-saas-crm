<x-admin::layouts>
    <x-slot:title>Campaigns</x-slot>

    @include('marketing_automation::partials.nav')

    <div class="grid gap-4 lg:grid-cols-2">
        <form class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900" method="POST" action="{{ route('admin.marketing_automation.campaigns.store') }}">
            @csrf
            @include('marketing_automation::partials.campaign-fields', ['campaign' => null, 'audiences' => $audiences])
            <button class="primary-button" type="submit">Save campaign</button>
        </form>
        <div class="space-y-3">
            @forelse ($campaigns as $campaign)
                <a class="box-shadow block rounded-lg bg-white p-4 dark:bg-gray-900" href="{{ route('admin.marketing_automation.campaigns.show', $campaign->id) }}">
                    <div class="font-medium dark:text-white">{{ $campaign->name }}</div>
                    <div class="text-sm text-gray-500">{{ $campaign->status }} · budget {{ $campaign->budget }} {{ $campaign->currency }} · utm {{ $campaign->utm_campaign ?: '—' }}</div>
                </a>
            @empty
                <p class="text-sm text-gray-500">No campaigns yet.</p>
            @endforelse
        </div>
    </div>
</x-admin::layouts>
