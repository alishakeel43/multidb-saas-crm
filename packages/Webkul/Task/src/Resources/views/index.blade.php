<x-admin::layouts>
    <x-slot:title>
        {{ menu()->getLabel('tasks', 'admin::app.tasks.index.title') }}
    </x-slot>

    <!-- Header -->
    {!! view_render_event('admin.tasks.index.header.before') !!}

    <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
        {!! view_render_event('admin.tasks.index.header.left.before') !!}

        <div class="flex flex-col gap-2">
            <!-- Breadcrumbs -->
            <x-admin::breadcrumbs name="tasks" />

            <div class="text-xl font-bold dark:text-white">
                {{ menu()->getLabel('tasks', 'admin::app.tasks.index.title') }}
            </div>
        </div>

        {!! view_render_event('admin.tasks.index.header.left.after') !!}

        {!! view_render_event('admin.tasks.index.header.right.before') !!}

        <div class="flex items-center gap-x-2.5">
            @if ((request()->view_type ?? "kanban") == "table")
                <!-- Export Modal -->
                <x-admin::datagrid.export :src="route('admin.tasks.index')" />
            @endif

            <!-- Action buttons for Tasks -->
            <div class="flex items-center gap-x-2.5">
                @if (bouncer()->hasPermission('tasks.create'))
                    <button
                        type="button"
                        class="secondary-button"
                        @click="$emitter.emit('open-create-task-stage-modal')"
                    >
                        <span class="icon-add text-lg"></span>
                        @lang('task::app.kanban.new-list-btn')
                    </button>

                    <a
                        href="{{ route('admin.tasks.create', request()->query()) }}"
                        class="primary-button"
                    >
                        @lang('admin::app.tasks.index.create-btn')
                    </a>
                @endif
            </div>
        </div>

        {!! view_render_event('admin.tasks.index.header.right.after') !!}
    </div>

    {!! view_render_event('admin.tasks.index.header.after') !!}

    {!! view_render_event('admin.tasks.index.content.before') !!}

    <!-- Content -->
    <div class="[&>*>*>*.toolbarRight]:max-lg:w-full [&>*>*>*.toolbarRight]:max-lg:justify-between [&>*>*>*.toolbarRight]:max-md:gap-y-2 [&>*>*>*.toolbarRight]:max-md:flex-wrap mt-3.5 [&>*>*:nth-child(1)]:max-lg:!flex-wrap">
        @if ((request()->view_type ?? "kanban") == "table")
            @include('task::index.table')
        @else
            @include('task::index.kanban')
        @endif
    </div>

    {!! view_render_event('admin.tasks.index.content.after') !!}
</x-admin::layouts>
