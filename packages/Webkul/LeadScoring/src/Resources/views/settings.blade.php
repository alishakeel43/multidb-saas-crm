<x-admin::layouts>
    <x-slot:title>Lead scoring</x-slot>

    <p class="mb-4 text-2xl font-semibold dark:text-white">Lead scoring rules</p>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <x-admin::form :action="route('admin.settings.lead-scoring.store')">
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Name</label>
                    <input class="w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="name" required>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Field</label>
                    <select class="w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="field" required>
                        @foreach ($fields as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Operator</label>
                    <select class="w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="operator" required>
                        @foreach ($operators as $operator)
                            <option value="{{ $operator }}">{{ $operator }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Value</label>
                    <input class="w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="value">
                </div>
                <div class="mb-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-sm dark:text-white">Points</label>
                        <input class="w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" type="number" name="points" value="10" required>
                    </div>
                    <div>
                        <label class="text-sm dark:text-white">Priority</label>
                        <input class="w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" type="number" name="priority" value="0">
                    </div>
                </div>
                <label class="mb-3 flex items-center gap-2 text-sm dark:text-white">
                    <input type="checkbox" name="is_active" value="1" checked> Active
                </label>
                <button class="primary-button" type="submit">Save rule</button>
            </x-admin::form>
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <div class="space-y-2 text-sm dark:text-white">
                @forelse ($rules as $rule)
                    <div class="flex items-center justify-between rounded border p-2 dark:border-gray-700">
                        <div>
                            <div class="font-medium">{{ $rule->name }} (+{{ $rule->points }})</div>
                            <div class="text-gray-500">{{ $rule->field }} {{ $rule->operator }} {{ $rule->value }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.lead-scoring.destroy', $rule->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="transparent-button" type="submit">Delete</button>
                        </form>
                    </div>
                @empty
                    <p class="text-gray-500">No scoring rules yet. Example: lead_value gte 1000 → +20 points.</p>
                @endforelse
            </div>
        </div>
    </div>

    {!! view_render_event('lead_scoring.settings.after') !!}
</x-admin::layouts>
