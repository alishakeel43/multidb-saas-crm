@props([
    'users' => [],
    'persons' => [],
    'participants' => ['users' => [], 'persons' => []],
])

@php
    $selectedUserIds = collect($participants['users'] ?? [])
        ->map(fn ($participant) => (int) (is_array($participant) ? $participant['id'] : $participant))
        ->all();
    $selectedPersonIds = collect($participants['persons'] ?? [])
        ->map(fn ($participant) => (int) (is_array($participant) ? $participant['id'] : $participant))
        ->all();
@endphp

<input type="hidden" name="participants_present" value="1" />

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <x-admin::form.control-group class="!mb-0">
        <x-admin::form.control-group.label>
            @lang('task::app.fields.users')
        </x-admin::form.control-group.label>

        <select
            name="participants[users][]"
            multiple
            size="5"
            class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
        >
            @foreach ($users as $user)
                <option value="{{ $user['id'] }}" {{ in_array((int) $user['id'], $selectedUserIds, true) ? 'selected' : '' }}>
                    {{ $user['name'] }}
                </option>
            @endforeach
        </select>
    </x-admin::form.control-group>

    <x-admin::form.control-group class="!mb-0">
        <x-admin::form.control-group.label>
            @lang('task::app.fields.persons')
        </x-admin::form.control-group.label>

        <select
            name="participants[persons][]"
            multiple
            size="5"
            class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
        >
            @foreach ($persons as $person)
                <option value="{{ $person['id'] }}" {{ in_array((int) $person['id'], $selectedPersonIds, true) ? 'selected' : '' }}>
                    {{ $person['name'] }}
                </option>
            @endforeach
        </select>
    </x-admin::form.control-group>
</div>

<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
    @lang('task::app.fields.participant-selection-help')
</p>
