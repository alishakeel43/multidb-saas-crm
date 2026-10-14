<x-admin::layouts>
    <x-slot:title>{{ $task->title }}</x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2.5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <div class="text-xl font-bold dark:text-white">{{ $task->title }}</div>
                <p class="text-xs text-gray-500">
                    @lang('task::app.priority.'.$task->priority)
                    @if ($task->is_overdue)
                        · <span class="text-red-600">@lang('task::app.view.overdue')</span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if (bouncer()->hasPermission('tasks.edit'))
                    <a href="{{ route('admin.tasks.edit', $task->id) }}" class="secondary-button">@lang('task::app.view.edit')</a>
                @endif
                @if (bouncer()->hasPermission('tasks.delete'))
                    <form method="POST" action="{{ route('admin.tasks.delete', $task->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="transparent-button">@lang('task::app.view.delete')</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $task->description ?: '—' }}</p>
                <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gray-500">@lang('task::app.form.due-date')</dt>
                        <dd>{{ optional($task->due_date)->format('Y-m-d') ?: '—' }} {{ $task->due_time ? substr((string) $task->due_time, 0, 5) : '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">@lang('task::app.form.assignee')</dt>
                        <dd>{{ $task->assignedUser->name ?? trans('task::app.index.unassigned') }}</dd>
                    </div>
                    @if ($linked)
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-500">@lang('task::app.entities.'.$linked['type'])</dt>
                            <dd>
                                @if ($linked['url'])
                                    <a class="text-brandColor hover:underline" href="{{ $linked['url'] }}">{{ $linked['title'] }}</a>
                                @else
                                    {{ $linked['title'] }}
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">@lang('task::app.view.move')</p>
                @foreach ($stages as $stage)
                    <form method="POST" action="{{ route('admin.tasks.status', $task->id) }}" class="mb-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ $stage->code }}">
                        <button type="submit" class="{{ $task->status === $stage->code ? 'primary-button' : 'secondary-button' }} w-full" @disabled($task->status === $stage->code)>
                            {{ $stage->name }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        @if ($related->isNotEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-3 text-sm font-semibold">@lang('task::app.view.related')</p>
                <ul class="flex flex-col gap-2 text-sm">
                    @foreach ($related as $item)
                        <li>
                            <a class="text-brandColor hover:underline" href="{{ route('admin.tasks.view', $item->id) }}">{{ $item->title }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-admin::layouts>
