@php
    $fieldClass = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200';
    $task = $task ?? null;
    $linked = $linked ?? [];
    $defaults = $defaults ?? [];
    $selectedType = old('entity_type', $task?->entity_type ?? ($defaults['entity_type'] ?? ''));
    $selectedId = old('entity_id', $task?->entity_id ?? ($defaults['entity_id'] ?? ''));
    $selectedTitle = $linked['title'] ?? '';
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.title') <span class="text-red-500">*</span>
        </label>
        <input type="text" name="title" value="{{ old('title', $task?->title ?? '') }}" class="{{ $fieldClass }}" required>
    </div>

    <div class="sm:col-span-2">
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.description')
        </label>
        <textarea name="description" rows="4" class="{{ $fieldClass }}">{{ old('description', $task?->description ?? '') }}</textarea>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.status')
        </label>
        <select name="status" class="{{ $fieldClass }}">
            @foreach ($stages as $stage)
                <option value="{{ $stage->code }}" @selected(old('status', $task?->status ?? ($defaults['status'] ?? '')) === $stage->code)>
                    {{ $stage->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.priority')
        </label>
        <select name="priority" class="{{ $fieldClass }}">
            @foreach (\Webkul\Task\Models\Task::PRIORITIES as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $task?->priority ?? 'medium') === $priority)>
                    @lang('task::app.priority.'.$priority)
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.due-date')
        </label>
        <input type="date" name="due_date" value="{{ old('due_date', isset($task) ? optional($task->due_date)->format('Y-m-d') : '') }}" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.due-time')
        </label>
        <input type="time" name="due_time" value="{{ old('due_time', isset($task) && $task->due_time ? substr((string) $task->due_time, 0, 5) : '') }}" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.assignee')
        </label>
        <select name="assigned_to" class="{{ $fieldClass }}">
            <option value="">@lang('task::app.form.none')</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('assigned_to', $task?->assigned_to ?? '') === (string) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.entity-type')
        </label>
        <select name="entity_type" class="{{ $fieldClass }}" data-task-entity-type>
            <option value="">@lang('task::app.form.none')</option>
            @foreach (array_keys(\Webkul\Task\Models\Task::ENTITIES) as $type)
                <option value="{{ $type }}" @selected($selectedType === $type)>
                    @lang('task::app.entities.'.$type)
                </option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2" data-task-entity-picker>
        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
            @lang('task::app.form.entity-search')
        </label>
        <input type="search" class="{{ $fieldClass }}" data-task-entity-search placeholder="@lang('task::app.form.entity-search')" value="{{ $selectedTitle }}">
        <input type="hidden" name="entity_id" value="{{ $selectedId }}" data-task-entity-id>
        <div class="mt-2 hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950" data-task-entity-results></div>
    </div>
</div>

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const typeInput = document.querySelector('[data-task-entity-type]');
            const searchInput = document.querySelector('[data-task-entity-search]');
            const idInput = document.querySelector('[data-task-entity-id]');
            const results = document.querySelector('[data-task-entity-results]');

            if (!typeInput || !searchInput || !idInput || !results) {
                return;
            }

            let timer = null;

            const render = (rows) => {
                results.innerHTML = '';

                if (!rows.length) {
                    const empty = document.createElement('p');
                    empty.className = 'px-3 py-2 text-sm text-gray-500 dark:text-gray-400';
                    empty.textContent = @json(trans('task::app.form.no-records'));
                    results.appendChild(empty);
                    results.classList.remove('hidden');
                    return;
                }

                rows.forEach((row) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-900';
                    button.textContent = row.title;
                    button.addEventListener('click', () => {
                        idInput.value = row.id;
                        searchInput.value = row.title;
                        results.classList.add('hidden');
                    });
                    results.appendChild(button);
                });

                results.classList.remove('hidden');
            };

            const lookup = () => {
                if (!typeInput.value) {
                    idInput.value = '';
                    results.classList.add('hidden');
                    return;
                }

                const params = new URLSearchParams({
                    entity_type: typeInput.value,
                    search: searchInput.value,
                });

                fetch(`{{ route('admin.tasks.search_entities') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((response) => response.json())
                    .then((payload) => render(payload.data || []))
                    .catch(() => results.classList.add('hidden'));
            };

            searchInput.addEventListener('input', () => {
                idInput.value = '';
                clearTimeout(timer);
                timer = setTimeout(lookup, 250);
            });

            typeInput.addEventListener('change', () => {
                idInput.value = '';
                searchInput.value = '';
                lookup();
            });
        });
    </script>
@endPushOnce
