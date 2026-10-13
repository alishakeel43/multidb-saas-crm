<x-admin::layouts>
    <x-slot:title>Audiences</x-slot>

    @include('marketing_automation::partials.nav')

    <div class="grid gap-4 lg:grid-cols-2">
        <form class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900" method="POST" action="{{ route('admin.marketing_automation.audiences.store') }}">
            @csrf
            <label class="mb-3 block text-sm dark:text-white">Name
                <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="name" required>
            </label>
            @for ($i = 0; $i < 3; $i++)
                <div class="mb-2 grid grid-cols-3 gap-2">
                    <select class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="rules[{{ $i }}][attribute]">
                        <option value="">Any</option>
                        @foreach ($fields as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="rules[{{ $i }}][operator]">
                        @foreach ($operators as $operator)
                            <option value="{{ $operator }}" @selected($operator === 'contains')>{{ $operator }}</option>
                        @endforeach
                    </select>
                    <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="rules[{{ $i }}][value]" placeholder="value">
                </div>
            @endfor
            <button class="primary-button" type="submit">Save audience</button>
        </form>
        <div class="space-y-3">
            @forelse ($audiences as $audience)
                <div class="box-shadow flex items-start justify-between rounded-lg bg-white p-4 dark:bg-gray-900">
                    <div>
                        <div class="font-medium dark:text-white">{{ $audience->name }}</div>
                        @forelse ($audience->rules ?? [] as $rule)
                            <div class="text-sm text-gray-500">{{ $rule['attribute'] }} {{ $rule['operator'] }} {{ $rule['value'] }}</div>
                        @empty
                            <div class="text-sm text-gray-500">Everyone</div>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('admin.marketing_automation.audiences.destroy', $audience->id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="transparent-button" type="submit">Delete</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">No audiences yet. A sequence without an audience enrolls every contact that has an email and has not opted out.</p>
            @endforelse
        </div>
    </div>
</x-admin::layouts>
