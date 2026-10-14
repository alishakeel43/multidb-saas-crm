@php
    use Illuminate\Support\Facades\Schema;
    use Webkul\Task\Models\Task;

    $entityType = null;
    $entityId = null;

    if (! empty($lead)) {
        $entityType = 'leads';
        $entityId = $lead->id;
    } elseif (! empty($person)) {
        $entityType = 'persons';
        $entityId = $person->id;
    }

    $tasks = ($entityType && $entityId && Schema::hasTable('tasks'))
        ? Task::query()->where('entity_type', $entityType)->where('entity_id', $entityId)->latest('id')->limit(8)->get()
        : collect();
@endphp

@if ($entityType)
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800 dark:text-white">@lang('task::app.related.title')</p>
            @if (bouncer()->hasPermission('tasks.create'))
                <a class="text-xs text-brandColor" href="{{ route('admin.tasks.create', ['entity_type' => $entityType, 'entity_id' => $entityId]) }}">
                    @lang('task::app.related.add')
                </a>
            @endif
        </div>
        @forelse ($tasks as $task)
            <a class="mb-2 block text-sm text-gray-700 hover:text-brandColor dark:text-gray-200" href="{{ route('admin.tasks.view', $task->id) }}">
                {{ $task->title }}
            </a>
        @empty
            <p class="text-xs text-gray-500">@lang('task::app.related.empty')</p>
        @endforelse
    </div>
@endif
