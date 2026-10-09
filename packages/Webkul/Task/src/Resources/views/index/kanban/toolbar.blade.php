{!! view_render_event('admin.tasks.index.kanban.toolbar.before') !!}

<div class="flex flex-col gap-2">
    <div class="flex justify-between gap-2 max-md:flex-wrap">
        <div class="flex w-full items-center gap-x-1.5 max-md:justify-between">
            {!! view_render_event('admin.tasks.index.kanban.toolbar.search.before') !!}

            <!-- Search Panel -->
            @include('task::index.kanban.search')

            {!! view_render_event('admin.tasks.index.kanban.toolbar.search.after') !!}

            {!! view_render_event('admin.tasks.index.kanban.toolbar.filter.before') !!}

            <!-- Filter -->
            @include('task::index.kanban.filter')

            {!! view_render_event('admin.tasks.index.kanban.toolbar.filter.after') !!}

            {!! view_render_event('admin.tasks.index.kanban.toolbar.card_settings.before') !!}

            <!-- Card Settings -->
            @include('task::index.kanban.card-settings')

            {!! view_render_event('admin.tasks.index.kanban.toolbar.card_settings.after') !!}

            <div class="z-10 hidden w-full divide-y divide-gray-100 rounded bg-white shadow dark:bg-gray-900"></div>
        </div>

        {!! view_render_event('admin.tasks.index.kanban.toolbar.switcher.before') !!}

        <!-- View Switcher -->
        @include('task::index.view-switcher')

        {!! view_render_event('admin.tasks.index.kanban.toolbar.switcher.after') !!}
    </div>

    <!-- User filter tabs -->
    <div class="flex flex-wrap items-center gap-1.5 overflow-x-auto pb-0.5">
        <button
            type="button"
            class="whitespace-nowrap rounded-full border px-3 py-1 text-xs font-medium transition-all"
            :class="selectedUserFilter === null
                ? 'border-brandColor bg-brandColor text-white shadow-xs'
                : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
            @click="filterByUser(null)"
        >
            @lang('task::app.kanban.all-users')
        </button>

        <button
            type="button"
            class="whitespace-nowrap rounded-full border px-3 py-1 text-xs font-medium transition-all"
            :class="selectedUserFilter === 0
                ? 'border-brandColor bg-brandColor text-white shadow-xs'
                : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
            @click="filterByUser(0)"
        >
            @lang('task::app.kanban.unassigned')
        </button>

        <button
            type="button"
            class="whitespace-nowrap rounded-full border px-3 py-1 text-xs font-medium transition-all"
            :class="selectedUserFilter === user.id
                ? 'border-brandColor bg-brandColor text-white shadow-xs'
                : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
            v-for="user in assignableUsers"
            :key="user.id"
            @click="filterByUser(user.id)"
        >
            @{{ user.name }}
        </button>
    </div>
</div>

{!! view_render_event('admin.tasks.index.kanban.toolbar.after') !!}
