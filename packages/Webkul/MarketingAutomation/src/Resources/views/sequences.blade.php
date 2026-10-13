<x-admin::layouts>
    <x-slot:title>Email sequences</x-slot>

    @include('marketing_automation::partials.nav')

    <div class="grid gap-4 lg:grid-cols-2">
        <form class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900" method="POST" action="{{ route('admin.marketing_automation.sequences.store') }}">
            @csrf
            <label class="mb-2 block text-sm dark:text-white">Name
                <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="name" required>
            </label>
            <label class="mb-2 block text-sm dark:text-white">Status
                <select class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="status">
                    <option value="draft">draft</option>
                    <option value="active">active</option>
                    <option value="paused">paused</option>
                </select>
            </label>
            <label class="mb-2 block text-sm dark:text-white">Campaign
                <select class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="campaign_id">
                    <option value="">None</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="mb-3 block text-sm dark:text-white">Audience
                <select class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="audience_id">
                    <option value="">Use the campaign audience, or everyone</option>
                    @foreach ($audiences as $audience)
                        <option value="{{ $audience->id }}">{{ $audience->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="primary-button" type="submit">Save sequence</button>
        </form>
        <div class="space-y-3">
            @forelse ($sequences as $sequence)
                <a class="box-shadow block rounded-lg bg-white p-4 dark:bg-gray-900" href="{{ route('admin.marketing_automation.sequences.show', $sequence->id) }}">
                    <div class="font-medium dark:text-white">{{ $sequence->name }}</div>
                    <div class="text-sm text-gray-500">{{ $sequence->status }} · {{ $sequence->campaign?->name ?? 'No campaign' }} · {{ $sequence->audience?->name ?? 'Default audience' }}</div>
                </a>
            @empty
                <p class="text-sm text-gray-500">No sequences yet.</p>
            @endforelse
        </div>
    </div>
</x-admin::layouts>
