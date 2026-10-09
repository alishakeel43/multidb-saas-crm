{!! view_render_event('admin.tasks.index.view_switcher.before') !!}

<div class="flex items-center gap-4 max-md:w-full max-md:!justify-between">
    <!-- Pipeline Dropdown -->
    <x-admin::dropdown>
        <x-slot:toggle>
            {!! view_render_event('admin.tasks.index.view_switcher.pipeline.button.before') !!}

            <button
                type="button"
                class="flex cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-[7px] text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
            >
                <span class="whitespace-nowrap">
                    {{ $pipeline->name ?? trans('task::app.title', ['default' => 'Tasks Pipeline']) }}
                </span>

                <span class="icon-down-arrow text-2xl"></span>
            </button>

            {!! view_render_event('admin.tasks.index.view_switcher.pipeline.button.after') !!}
        </x-slot>

        <x-slot:content class="!p-0">
            {!! view_render_event('admin.tasks.index.view_switcher.pipeline.content.header.before') !!}

            <div class="flex items-center justify-between px-3 py-2.5">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-300">
                    @lang('task::app.title', ['default' => 'Tasks Pipeline'])
                </span>
            </div>

            {!! view_render_event('admin.tasks.index.view_switcher.pipeline.content.header.after') !!}

            <a
                href="{{ route('admin.tasks.index', array_diff_key(request()->query(), ['view_type' => ''])) }}"
                class="block px-3 py-2.5 pl-4 text-gray-600 transition-all hover:bg-gray-100 dark:hover:bg-gray-950 dark:text-gray-300 {{ !request('view_type') || request('view_type') === 'kanban' ? 'bg-gray-100 dark:bg-gray-950 font-medium' : '' }}"
            >
                @lang('task::app.title', ['default' => 'Tasks Pipeline'])
            </a>
        </x-slot>
    </x-admin::dropdown>

    <!-- View Switcher (Kanban vs Table) -->
    <div class="flex items-center gap-0.5">
        {!! view_render_event('admin.tasks.index.view_switcher.pipeline.view_type.before') !!}

        @if ((request()->view_type ?? 'kanban') == 'table')
            <a
                class="flex cursor-pointer text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                href="{{ route('admin.tasks.index', array_diff_key(request()->query(), ['view_type' => ''])) }}"
                title="@lang('task::app.kanban.board-view', ['default' => 'Kanban Board'])"
            >
                <span class="icon-kanban p-2 text-2xl"></span>
            </a>

            <span
                class="icon-list rounded-md bg-gray-100 p-2 text-2xl dark:bg-gray-950 text-brandColor"
                title="@lang('task::app.kanban.table-view', ['default' => 'Table View'])"
            ></span>
        @else
            <span
                class="icon-kanban rounded-md bg-white p-2 text-2xl dark:bg-gray-900 text-brandColor shadow-xs"
                title="@lang('task::app.kanban.board-view', ['default' => 'Kanban Board'])"
            ></span>

            <a
                href="{{ route('admin.tasks.index', array_merge(request()->query(), ['view_type' => 'table'])) }}"
                class="flex cursor-pointer text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                title="@lang('task::app.kanban.table-view', ['default' => 'Table View'])"
            >
                <span class="icon-list p-2 text-2xl"></span>
            </a>
        @endif

        {!! view_render_event('admin.tasks.index.view_switcher.pipeline.view_type.after') !!}
    </div>
</div>

{!! view_render_event('admin.tasks.index.view_switcher.after') !!}
