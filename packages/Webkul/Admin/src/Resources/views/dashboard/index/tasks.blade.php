@php
    $dashboardTaskRepo = app(\Webkul\Task\Repositories\TaskRepository::class);
    $dashboardUserId = auth()->id();
    $dashboardStats = $dashboardTaskRepo->getTaskStats($dashboardUserId);
    $dashboardTodayTasks = $dashboardTaskRepo->getTodayTasks($dashboardUserId, 5);
    $dashboardOverdueTasks = $dashboardTaskRepo->getOverdueTasks($dashboardUserId, 5);
@endphp

<div
    class="flex flex-col rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
    id="dashboard-tasks-card"
>
    <!-- Card Header -->
    <div class="flex items-center justify-between border-b pb-3 dark:border-gray-800">
        <div class="flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-brandColor/15 text-brandColor">
                <i class="icon-tick text-base"></i>
            </span>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                @lang('admin::app.tasks.dashboard.title')
            </h3>
        </div>

        <a
            href="{{ route('admin.tasks.index') }}"
            class="text-xs font-semibold text-brandColor hover:underline"
        >
            @lang('admin::app.tasks.dashboard.view-all') &rarr;
        </a>
    </div>

    <!-- Quick Metrics Strip -->
    <div class="mt-3 grid grid-cols-4 gap-2">
        <a
            href="{{ route('admin.tasks.index', ['view' => 'today']) }}"
            class="flex flex-col items-center rounded bg-blue-50 py-1.5 text-center transition hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50"
        >
            <span class="text-xs text-blue-700 dark:text-blue-300">@lang('admin::app.tasks.dashboard.today')</span>
            <span class="text-base font-bold text-blue-800 dark:text-blue-200">{{ $dashboardStats['due_today'] }}</span>
        </a>

        <a
            href="{{ route('admin.tasks.index', ['view' => 'overdue']) }}"
            class="flex flex-col items-center rounded bg-red-50 py-1.5 text-center transition hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50"
        >
            <span class="text-xs text-red-700 dark:text-red-300">@lang('admin::app.tasks.dashboard.overdue')</span>
            <span class="text-base font-bold text-red-800 dark:text-red-200">{{ $dashboardStats['overdue'] }}</span>
        </a>

        <a
            href="{{ route('admin.tasks.index', ['status[in]' => 'in_progress']) }}"
            class="flex flex-col items-center rounded bg-purple-50 py-1.5 text-center transition hover:bg-purple-100 dark:bg-purple-900/30 dark:hover:bg-purple-900/50"
        >
            <span class="text-xs text-purple-700 dark:text-purple-300">@lang('admin::app.tasks.dashboard.pending')</span>
            <span class="text-base font-bold text-purple-800 dark:text-purple-200">{{ $dashboardStats['in_progress'] + $dashboardStats['pending'] }}</span>
        </a>

        <a
            href="{{ route('admin.tasks.index', ['view' => 'completed']) }}"
            class="flex flex-col items-center rounded bg-green-50 py-1.5 text-center transition hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50"
        >
            <span class="text-xs text-green-700 dark:text-green-300">@lang('admin::app.tasks.status.completed')</span>
            <span class="text-base font-bold text-green-800 dark:text-green-200">{{ $dashboardStats['completed'] }}</span>
        </a>
    </div>

    <!-- Today & Overdue Task Lists -->
    <div class="mt-4 flex flex-col gap-3">
        <!-- Overdue Tasks (if any) -->
        @if ($dashboardOverdueTasks->isNotEmpty())
            <div class="flex flex-col gap-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400">
                    <i class="icon-warning"></i> @lang('admin::app.tasks.dashboard.overdue') ({{ $dashboardOverdueTasks->count() }})
                </span>

                <div class="flex flex-col divide-y divide-gray-100 rounded-md border border-red-200 bg-red-50/40 dark:divide-gray-800 dark:border-red-900/50 dark:bg-red-950/20">
                    @foreach ($dashboardOverdueTasks as $task)
                        <div
                            class="flex items-center justify-between p-2.5 text-sm"
                            id="task-row-{{ $task->id }}"
                        >
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    onclick="toggleTaskStatus({{ $task->id }}, 'completed')"
                                    class="h-4 w-4 rounded border border-gray-400 hover:border-green-600 hover:bg-green-50 dark:border-gray-600"
                                    title="@lang('admin::app.tasks.dashboard.mark-done')"
                                ></button>

                                <a
                                    href="{{ route('admin.tasks.view', $task->id) }}"
                                    class="font-medium text-gray-900 hover:text-brandColor dark:text-white"
                                >
                                    {{ $task->title }}
                                </a>

                                @if ($task->priority === 'urgent')
                                    <span class="rounded bg-red-200 px-1.5 py-0.2 text-[10px] font-bold text-red-800 dark:bg-red-900 dark:text-red-200">URGENT</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 text-xs text-red-600 dark:text-red-400 font-semibold">
                                {{ $task->due_date ? core()->formatDate($task->due_date) : '' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Today's Tasks -->
        <div class="flex flex-col gap-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                @lang('admin::app.tasks.dashboard.today') ({{ $dashboardTodayTasks->count() }})
            </span>

            @if ($dashboardTodayTasks->isEmpty())
                <div class="rounded-md border border-dashed border-gray-200 p-4 text-center text-xs text-gray-400 dark:border-gray-800">
                    @lang('admin::app.tasks.dashboard.no-tasks')
                </div>
            @else
                <div class="flex flex-col divide-y divide-gray-100 rounded-md border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
                    @foreach ($dashboardTodayTasks as $task)
                        <div
                            class="flex items-center justify-between p-2.5 text-sm"
                            id="task-row-{{ $task->id }}"
                        >
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    onclick="toggleTaskStatus({{ $task->id }}, 'completed')"
                                    class="h-4 w-4 rounded border border-gray-400 hover:border-green-600 hover:bg-green-50 dark:border-gray-600"
                                    title="@lang('admin::app.tasks.dashboard.mark-done')"
                                ></button>

                                <a
                                    href="{{ route('admin.tasks.view', $task->id) }}"
                                    class="font-medium text-gray-900 hover:text-brandColor dark:text-white"
                                >
                                    {{ $task->title }}
                                </a>

                                @if ($task->entity_title)
                                    <span class="text-xs text-gray-400">({{ $task->entity_title }})</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                @if ($task->due_time)
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ substr($task->due_time, 0, 5) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function toggleTaskStatus(taskId, status) {
        const url = "{{ url(config('app.admin_path')) }}/tasks/status/" + taskId;
        const csrfToken = "{{ csrf_token() }}";

        fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            const row = document.getElementById('task-row-' + taskId);
            if (row) {
                row.style.opacity = '0.4';
                row.style.textDecoration = 'line-through';
            }
        })
        .catch(err => console.error(err));
    }
</script>
