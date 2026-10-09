<x-admin::layouts>
    <x-slot:title>
        {{ $task->title }}
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs
                    name="tasks.view"
                    :entity="$task"
                />

                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-bold dark:text-white {{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                        {{ $task->title }}
                    </h1>

                    <!-- Status Badge -->
                    @if ($task->status === 'pending')
                        <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">@lang('admin::app.tasks.status.pending')</span>
                    @elseif ($task->status === 'in_progress')
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">@lang('admin::app.tasks.status.in_progress')</span>
                    @elseif ($task->status === 'completed')
                        <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/50 dark:text-green-300">@lang('admin::app.tasks.status.completed')</span>
                    @elseif ($task->status === 'cancelled')
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">@lang('admin::app.tasks.status.cancelled')</span>
                    @endif

                    <!-- Priority Badge -->
                    @if ($task->priority === 'urgent')
                        <span class="rounded px-2 py-0.5 text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">@lang('admin::app.tasks.priority.urgent')</span>
                    @elseif ($task->priority === 'high')
                        <span class="rounded px-2 py-0.5 text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300">@lang('admin::app.tasks.priority.high')</span>
                    @elseif ($task->priority === 'medium')
                        <span class="rounded px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">@lang('admin::app.tasks.priority.medium')</span>
                    @else
                        <span class="rounded px-2 py-0.5 text-xs font-normal bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">@lang('admin::app.tasks.priority.low')</span>
                    @endif

                    @if ($task->is_overdue)
                        <span class="inline-flex items-center gap-1 rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700 dark:bg-red-900/50 dark:text-red-300">
                            <i class="icon-warning"></i> @lang('admin::app.tasks.overdue')
                        </span>
                    @endif
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2">
                @if (bouncer()->hasPermission('tasks.edit'))
                    @if ($task->status !== 'completed')
                        <form
                            action="{{ route('admin.tasks.status', $task->id) }}"
                            method="POST"
                            class="inline"
                        >
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="completed">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700"
                            >
                                <i class="icon-tick"></i> @lang('admin::app.tasks.actions.complete')
                            </button>
                        </form>

                        @if ($task->status !== 'in_progress')
                            <form
                                action="{{ route('admin.tasks.status', $task->id) }}"
                                method="POST"
                                class="inline"
                            >
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="in_progress">
                                <button
                                    type="submit"
                                    class="secondary-button"
                                >
                                    @lang('admin::app.tasks.actions.start')
                                </button>
                            </form>
                        @endif
                    @else
                        <form
                            action="{{ route('admin.tasks.status', $task->id) }}"
                            method="POST"
                            class="inline"
                        >
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="pending">
                            <button
                                type="submit"
                                class="secondary-button"
                            >
                                @lang('admin::app.tasks.actions.reopen')
                            </button>
                        </form>
                    @endif

                    <a
                        href="{{ route('admin.tasks.edit', $task->id) }}"
                        class="secondary-button"
                    >
                        @lang('admin::app.tasks.datagrid.edit')
                    </a>
                @endif

                @if (bouncer()->hasPermission('tasks.delete'))
                    <form
                        action="{{ route('admin.tasks.delete', $task->id) }}"
                        method="POST"
                        onsubmit="return confirm('{{ trans('admin::app.common.confirm-delete') }}')"
                        class="inline"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="transparent-button text-red-600 hover:bg-red-50 dark:hover:bg-red-950"
                        >
                            <i class="icon-delete text-xl"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Content Grid -->
        <div class="flex gap-4 max-xl:flex-wrap">
            <!-- Left Panel (Description & Related Record) -->
            <div class="flex flex-1 flex-col gap-4 max-xl:flex-auto">
                <!-- Description Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.tasks.fields.description')
                    </h2>

                    @if ($task->description)
                        <div class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                            {{ $task->description }}
                        </div>
                    @else
                        <p class="italic text-sm text-gray-400">
                            No description provided for this task.
                        </p>
                    @endif
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('task::app.fields.participants')
                    </h2>

                    @if ($task->participants->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($task->participants as $participant)
                                @php($name = $participant->user?->name ?? $participant->person?->name)
                                @if ($name)
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-sm text-blue-800 dark:bg-gray-800 dark:text-blue-200">
                                        {{ $name }}
                                        <span class="text-xs text-blue-600 dark:text-blue-300">
                                            ({{ $participant->user ? trans('task::app.fields.users') : trans('task::app.fields.persons') }})
                                        </span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="italic text-sm text-gray-400">@lang('task::app.fields.no-participants')</p>
                    @endif
                </div>

                <!-- Related CRM Record Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.tasks.view.relationships')
                    </h2>

                    @if ($task->entity_type && $task->entity_id)
                        <div class="flex items-center justify-between rounded-md border border-gray-200 p-3.5 dark:border-gray-800">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brandColor/15 text-brandColor">
                                    <i class="icon-leads text-xl"></i>
                                </span>

                                <div>
                                    <span class="text-xs font-semibold uppercase text-gray-500">
                                        {{ ucfirst($task->entity_type) }}
                                    </span>
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">
                                        {{ $task->entity_title ?? ('#'.$task->entity_id) }}
                                    </div>
                                </div>
                            </div>

                            @if ($task->entity_url)
                                <a
                                    href="{{ $task->entity_url }}"
                                    class="secondary-button"
                                >
                                    Open Record <i class="icon-forward ml-1"></i>
                                </a>
                            @endif
                        </div>
                    @else
                        <p class="italic text-sm text-gray-400">
                            This task is not linked to any CRM entity.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Right Panel (Metadata & History) -->
            <div class="flex w-[380px] max-w-full flex-col gap-4 max-xl:w-full">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.tasks.view.info')
                    </h2>

                    <div class="flex flex-col gap-3.5 text-sm">
                        <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">@lang('task::app.fields.schedule_from')</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $task->schedule_from?->format('M j, Y g:i A') ?? '—' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">@lang('task::app.fields.schedule_to')</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $task->schedule_to?->format('M j, Y g:i A') ?? '—' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">@lang('task::app.fields.location')</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $task->location ?: '—' }}</span>
                        </div>

                        <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">@lang('task::app.fields.reminder')</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                @if ($task->reminder_minutes === null)
                                    @lang('task::app.fields.no-reminder')
                                @elseif ($task->reminder_minutes === 1440)
                                    @lang('task::app.fields.reminder-day')
                                @else
                                    @lang('task::app.fields.reminder-option', ['minutes' => $task->reminder_minutes])
                                @endif
                            </span>
                        </div>

                        <!-- Due Date -->
                        <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">@lang('admin::app.tasks.fields.due_date')</span>
                            <span class="font-medium {{ $task->is_overdue ? 'text-red-600 font-bold' : 'text-gray-900 dark:text-white' }}">
                                {{ $task->due_date ? core()->formatDate($task->due_date) : '—' }}
                                {{ $task->due_time ? ' at '.substr($task->due_time, 0, 5) : '' }}
                            </span>
                        </div>

                        <!-- Assignee -->
                        <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">@lang('admin::app.tasks.fields.assigned_to')</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $task->assignedUser?->name ?? trans('admin::app.tasks.unassigned') }}
                            </span>
                        </div>

                        <!-- Creator -->
                        <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">@lang('admin::app.tasks.fields.created_by')</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $task->creator?->name ?? 'System' }}
                            </span>
                        </div>

                        <!-- Created At -->
                        <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400">@lang('admin::app.tasks.fields.created_at')</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ core()->formatDate($task->created_at) }}
                            </span>
                        </div>

                        <!-- Completed Info (if completed) -->
                        @if ($task->status === 'completed')
                            <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">@lang('admin::app.tasks.fields.completed_by')</span>
                                <span class="font-medium text-green-700 dark:text-green-400">
                                    {{ $task->completedBy?->name ?? 'User' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">@lang('admin::app.tasks.fields.completed_at')</span>
                                <span class="font-medium text-green-700 dark:text-green-400">
                                    {{ $task->completed_at ? core()->formatDate($task->completed_at) : '—' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
