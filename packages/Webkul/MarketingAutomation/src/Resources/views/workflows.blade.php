<x-admin::layouts>
    <x-slot:title>Workflows</x-slot>

    @include('marketing_automation::partials.nav')

    <div class="grid gap-4 lg:grid-cols-2">
        <form class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900" method="POST" action="{{ route('admin.marketing_automation.workflows.store') }}">
            @csrf
            <h2 class="mb-3 text-lg font-semibold dark:text-white">New workflow</h2>
            <label class="mb-2 block text-sm dark:text-white">Name
                <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="name" required>
            </label>
            <label class="mb-2 block text-sm dark:text-white">Trigger
                <select class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="event" required>
                    @foreach ($events as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="mb-2 block text-sm dark:text-white">Conditions
                <select class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="condition_type">
                    <option value="and">Match all</option>
                    <option value="or">Match any</option>
                </select>
            </label>
            @for ($i = 0; $i < 3; $i++)
                <div class="mb-2 grid grid-cols-3 gap-2">
                    <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="conditions[{{ $i }}][attribute]" placeholder="lead_value">
                    <select class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="conditions[{{ $i }}][operator]">
                        @foreach ($operators as $operator)
                            <option value="{{ $operator }}">{{ $operator }}</option>
                        @endforeach
                    </select>
                    <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="conditions[{{ $i }}][value]" placeholder="1000">
                </div>
            @endfor
            <p class="mb-3 text-xs text-gray-500">Empty conditions always take the match branch. Attributes: @foreach ($attributes as $entity => $fields) {{ $entity }} ({{ implode(', ', array_keys($fields)) }})@if (! $loop->last); @endif @endforeach.</p>

            @foreach (['when_match' => 'When conditions match', 'when_else' => 'Otherwise'] as $branch => $heading)
                <h3 class="mb-2 mt-4 font-medium dark:text-white">{{ $heading }}</h3>
                @for ($i = 0; $i < 2; $i++)
                    <div class="mb-3 rounded border p-2 dark:border-gray-700">
                        <input type="hidden" name="actions[{{ $branch }}_{{ $i }}][branch]" value="{{ $branch }}">
                        <div class="grid gap-2 md:grid-cols-2">
                            <select class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="actions[{{ $branch }}_{{ $i }}][type]">
                                <option value="">Skip</option>
                                <option value="delay">Delay</option>
                                <option value="task">Task</option>
                                <option value="notification">Notification</option>
                                <option value="email">Email</option>
                                <option value="update">Update record</option>
                            </select>
                            <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="actions[{{ $branch }}_{{ $i }}][minutes]" placeholder="Delay minutes" type="number" min="1">
                            <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="actions[{{ $branch }}_{{ $i }}][title]" placeholder="Task or notification title">
                            <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="actions[{{ $branch }}_{{ $i }}][subject]" placeholder="Email subject">
                            <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="actions[{{ $branch }}_{{ $i }}][field]" placeholder="Update field">
                            <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="actions[{{ $branch }}_{{ $i }}][value]" placeholder="Update value">
                            <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white md:col-span-2" name="actions[{{ $branch }}_{{ $i }}][body]" placeholder="Body or comment">
                            <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="actions[{{ $branch }}_{{ $i }}][link_url]" placeholder="https://example.com">
                            <input class="rounded border px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="actions[{{ $branch }}_{{ $i }}][link_label]" placeholder="Link label">
                        </div>
                    </div>
                @endfor
            @endforeach

            <button class="primary-button" type="submit">Save workflow</button>
        </form>

        <div class="space-y-3">
            @forelse ($workflows as $workflow)
                <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium dark:text-white">{{ $workflow->name }}</div>
                            <div class="text-sm text-gray-500">{{ $workflow->event }} · {{ $workflow->condition_type }}</div>
                            @foreach ($workflow->actions as $action)
                                <div class="text-sm text-gray-600 dark:text-gray-300">{{ $action->branch }} → {{ $action->type }}</div>
                            @endforeach
                        </div>
                        <form method="POST" action="{{ route('admin.marketing_automation.workflows.destroy', $workflow->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="transparent-button" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No workflows yet.</p>
            @endforelse
        </div>
    </div>
</x-admin::layouts>
