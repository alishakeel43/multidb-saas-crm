<x-admin::layouts>
    <x-slot:title>@lang('task::app.index.title')</x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <div class="text-xl font-bold dark:text-white">
                    @lang('task::app.index.title')
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @lang('task::app.index.subtitle')
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tasks.index') }}" class="{{ $layout === 'table' ? 'primary-button' : 'transparent-button' }}">
                    @lang('task::app.index.table')
                </a>
                <a href="{{ route('admin.tasks.index', ['layout' => 'kanban']) }}" class="{{ $layout === 'kanban' ? 'primary-button' : 'transparent-button' }}">
                    @lang('task::app.index.kanban')
                </a>
                @if (bouncer()->hasPermission('tasks.create'))
                    <a href="{{ route('admin.tasks.create') }}" class="primary-button">
                        @lang('task::app.index.create')
                    </a>
                @endif
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-4">
            @foreach (['total' => 'total', 'due_today' => 'due_today', 'overdue' => 'overdue', 'upcoming' => 'upcoming'] as $key => $label)
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs text-gray-500 dark:text-gray-400">@lang('task::app.stats.'.$label)</p>
                    <p class="text-2xl font-semibold text-gray-800 dark:text-white">{{ $stats[$key] ?? 0 }}</p>
                </div>
            @endforeach
        </div>

        @if ($layout === 'kanban')
            <div class="flex gap-4 overflow-x-auto pb-2">
                @foreach ($columns as $column)
                    <div class="w-72 shrink-0 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                        <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-800">
                            <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ $column['name'] }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs text-white" style="background: {{ $column['color'] }}">{{ $column['total'] }}</span>
                        </div>
                        <div class="flex flex-col gap-2 p-2">
                            @forelse ($column['tasks'] as $task)
                                <a href="{{ route('admin.tasks.view', $task->id) }}" class="block rounded-lg border border-gray-200 bg-white p-3 text-sm dark:border-gray-800 dark:bg-gray-900">
                                    <p class="font-medium text-gray-800 dark:text-white">{{ $task->title }}</p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        @lang('task::app.priority.'.$task->priority)
                                        @if ($task->due_date)
                                            · {{ $task->due_date->format('Y-m-d') }}
                                        @endif
                                        @if ($task->is_overdue)
                                            · <span class="text-red-600">@lang('task::app.view.overdue')</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $task->assignedUser->name ?? trans('task::app.index.unassigned') }}</p>
                                </a>
                            @empty
                                <p class="px-2 py-4 text-center text-xs text-gray-500">@lang('task::app.index.empty')</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-admin::datagrid :src="route('admin.tasks.index')" />
        @endif

        @if (bouncer()->hasPermission('tasks.create') || bouncer()->hasPermission('tasks.edit') || bouncer()->hasPermission('tasks.delete'))
            <details class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-800 dark:text-white">
                    @lang('task::app.index.stages')
                </summary>
                <div class="flex flex-col gap-3 border-t border-gray-200 p-4 dark:border-gray-800">
                    @foreach ($stages as $stage)
                        <div class="flex flex-wrap items-end gap-2">
                            @if (bouncer()->hasPermission('tasks.edit'))
                                <form method="POST" action="{{ route('admin.tasks.stages.update', $stage->code) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $stage->name }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">
                                    <input type="color" name="color" value="{{ $stage->color }}" class="h-10 w-12">
                                    <input type="number" name="sort_order" value="{{ $stage->sort_order }}" min="1" class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">
                                    <button class="secondary-button" type="submit">@lang('task::app.acl.edit')</button>
                                </form>
                            @endif
                            @if (bouncer()->hasPermission('tasks.delete'))
                                <form method="POST" action="{{ route('admin.tasks.stages.delete', $stage->code) }}" class="flex items-end gap-2">
                                    @csrf
                                    @method('DELETE')
                                    <select name="reassign_to" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">
                                        <option value="">@lang('task::app.form.none')</option>
                                        @foreach ($stages as $target)
                                            @if ($target->code !== $stage->code)
                                                <option value="{{ $target->code }}">{{ $target->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button class="transparent-button" type="submit">@lang('task::app.acl.delete')</button>
                                </form>
                            @endif
                        </div>
                    @endforeach

                    @if (bouncer()->hasPermission('tasks.create'))
                        <form method="POST" action="{{ route('admin.tasks.stages.store') }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <input type="text" name="name" required placeholder="@lang('task::app.index.stage-name')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">
                            <input type="color" name="color" value="#2563EB" class="h-10 w-12">
                            <button class="primary-button" type="submit">@lang('task::app.index.add-stage')</button>
                        </form>
                    @endif
                </div>
            </details>
        @endif
    </div>
</x-admin::layouts>
