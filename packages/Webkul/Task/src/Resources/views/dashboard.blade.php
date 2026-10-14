@php
    use Illuminate\Support\Facades\Schema;
    use Webkul\Task\Models\Task;

    $openTasks = Schema::hasTable('tasks')
        ? Task::query()->incomplete()->orderBy('due_date')->limit(5)->get()
        : collect();
@endphp

@if (Schema::hasTable('tasks') && bouncer()->hasPermission('tasks.view'))
    <div class="box-shadow w-[378px] max-w-full rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('task::app.widget.title')</p>
            <a class="text-xs text-brandColor" href="{{ route('admin.tasks.index') }}">@lang('task::app.menu.tasks')</a>
        </div>
        @forelse ($openTasks as $task)
            <a class="mb-2 block text-sm text-gray-700 hover:text-brandColor dark:text-gray-200" href="{{ route('admin.tasks.view', $task->id) }}">
                {{ $task->title }}
                @if ($task->is_overdue)
                    <span class="text-xs text-red-600">@lang('task::app.view.overdue')</span>
                @endif
            </a>
        @empty
            <p class="text-xs text-gray-500">@lang('task::app.widget.empty')</p>
        @endforelse
    </div>
@endif
