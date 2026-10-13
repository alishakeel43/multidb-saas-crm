<x-admin::layouts>
    <x-slot:title>Engagement scoring</x-slot>

    @include('marketing_automation::partials.nav')

    <p class="mb-4 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
        Field rules in Settings → Lead scoring still set the lead score. These rules add engagement points for opens, clicks, form submissions, and unsubscribes without replacing that score. Total engagement points: {{ $total }}.
    </p>

    <div class="grid gap-4 lg:grid-cols-2">
        <form class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900" method="POST" action="{{ route('admin.marketing_automation.engagement.store') }}">
            @csrf
            <label class="mb-2 block text-sm dark:text-white">Name
                <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="name" required>
            </label>
            <label class="mb-2 block text-sm dark:text-white">Event
                <select class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="event_type">
                    @foreach ($eventTypes as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="mb-3 block text-sm dark:text-white">Points
                <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="points" type="number" value="5" required>
            </label>
            <button class="primary-button" type="submit">Save rule</button>
        </form>
        <div>
            @foreach ($rules as $rule)
                <div class="mb-2 flex items-center justify-between rounded border p-3 dark:border-gray-700">
                    <div class="text-sm dark:text-white">{{ $rule->name }} · {{ $rule->event_type }} · {{ $rule->points }}</div>
                    <form method="POST" action="{{ route('admin.marketing_automation.engagement.destroy', $rule->id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="transparent-button" type="submit">Delete</button>
                    </form>
                </div>
            @endforeach
            <h2 class="mb-2 mt-4 font-medium dark:text-white">Recent events</h2>
            @forelse ($events as $event)
                <div class="text-sm text-gray-600 dark:text-gray-300">Lead {{ $event->lead_id }} · {{ $event->event_type }} · {{ $event->points }}</div>
            @empty
                <p class="text-sm text-gray-500">No engagement events yet.</p>
            @endforelse
        </div>
    </div>
</x-admin::layouts>
