<x-admin::layouts>
    <x-slot:title>{{ $campaign->name }}</x-slot>

    @include('marketing_automation::partials.nav')

    <div class="grid gap-4 lg:grid-cols-2">
        <form class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900" method="POST" action="{{ route('admin.marketing_automation.campaigns.update', $campaign->id) }}">
            @csrf
            @method('PUT')
            @include('marketing_automation::partials.campaign-fields', ['campaign' => $campaign, 'audiences' => $audiences])
            <div class="flex gap-2">
                <button class="primary-button" type="submit">Update</button>
            </div>
        </form>
        <form method="POST" action="{{ route('admin.marketing_automation.campaigns.destroy', $campaign->id) }}">
            @csrf
            @method('DELETE')
            <button class="transparent-button" type="submit">Delete campaign</button>
        </form>
    </div>

    <h2 class="mb-3 mt-6 text-lg font-semibold dark:text-white">Results</h2>
    <div class="grid gap-3 md:grid-cols-4">
        @foreach (['leads', 'budget', 'spent', 'remaining', 'sent', 'opened', 'clicked', 'bounced', 'failed', 'unsubscribed', 'suppressed'] as $key)
            <div class="box-shadow rounded-lg bg-white p-3 dark:bg-gray-900">
                <div class="text-xs uppercase text-gray-500">{{ $key }}</div>
                <div class="text-xl font-semibold dark:text-white">{{ $report[$key] }}</div>
            </div>
        @endforeach
    </div>
</x-admin::layouts>
