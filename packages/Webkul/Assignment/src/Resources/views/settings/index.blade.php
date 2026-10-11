<x-admin::layouts>
    <x-slot:title>
        @lang('assignment::app.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-2xl font-semibold dark:text-white">
            @lang('assignment::app.index.title')
        </p>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 text-lg font-medium dark:text-white">@lang('assignment::app.index.create-btn')</p>

            <x-admin::form :action="route('admin.settings.assignment.store')">
                <div class="mb-3">
                    <x-admin::form.control-group.label class="required">
                        @lang('assignment::app.index.name')
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" name="name" rules="required" />
                </div>

                <div class="mb-3">
                    <x-admin::form.control-group.label class="required">
                        @lang('assignment::app.index.strategy')
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="select"
                        name="strategy"
                        :value="'round_robin'"
                    >
                        @foreach (trans('assignment::app.index.strategies') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-admin::form.control-group.control>
                </div>

                <div class="mb-3">
                    <x-admin::form.control-group.label>
                        @lang('assignment::app.index.priority')
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" name="priority" value="0" />
                </div>

                <div class="mb-3">
                    <label class="flex items-center gap-2 text-sm dark:text-white">
                        <input type="checkbox" name="only_when_empty" value="1" checked>
                        @lang('assignment::app.index.only-when-empty')
                    </label>
                </div>

                <div class="mb-3">
                    <x-admin::form.control-group.label class="required">
                        @lang('assignment::app.index.users')
                    </x-admin::form.control-group.label>
                    <div class="max-h-48 space-y-1 overflow-y-auto rounded border p-2 dark:border-gray-700">
                        @foreach ($users as $user)
                            <label class="flex items-center gap-2 text-sm dark:text-white">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}">
                                {{ $user->name }} ({{ $user->email }})
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="primary-button">
                    @lang('assignment::app.index.create-btn')
                </button>
            </x-admin::form>
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 text-lg font-medium dark:text-white">Rules</p>

            <div class="space-y-3">
                @forelse ($rules as $rule)
                    <div class="rounded border p-3 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium dark:text-white">{{ $rule->name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ trans('assignment::app.index.strategies.'.$rule->strategy) }}
                                    · priority {{ $rule->priority }}
                                    · {{ $rule->is_active ? 'active' : 'inactive' }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $rule->users->pluck('name')->join(', ') }}
                                </p>
                            </div>

                            <form method="POST" action="{{ route('admin.settings.assignment.destroy', $rule->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="transparent-button text-red-600">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No assignment rules yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin::layouts>
