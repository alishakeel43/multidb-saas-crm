{!! view_render_event('admin.tasks.index.kanban.filter.before') !!}

<v-tasks-kanban-filter
    :is-loading="isLoading"
    :assignable-users="assignableUsers"
    @apply-filters="filter"
>
</v-tasks-kanban-filter>

{!! view_render_event('admin.tasks.index.kanban.filter.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-tasks-kanban-filter-template"
    >
        {!! view_render_event('admin.tasks.index.kanban.filter.drawer.before') !!}

        <x-admin::drawer
            width="350px"
            ref="tasksFilterDrawer"
        >
            <!-- Drawer Toggler -->
            <x-slot:toggle>
                {!! view_render_event('admin.tasks.index.kanban.filter.drawer.toggle_button.before') !!}

                <div class="relative flex cursor-pointer items-center rounded-md bg-sky-100 px-4 py-[9px] font-semibold text-sky-600 dark:bg-brandColor dark:text-white">
                    @lang('task::app.kanban.toolbar.filters.filter')

                    <span
                        class="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-sky-600 dark:bg-white"
                        v-if="hasActiveFilters"
                    >
                    </span>
                </div>

                {!! view_render_event('admin.tasks.index.kanban.filter.drawer.toggle_button.after') !!}
            </x-slot>

            <!-- Drawer Header -->
            <x-slot:header class="p-3.5">
                <div class="flex items-center justify-between w-full">
                    <p class="text-xl font-semibold dark:text-white">
                        @lang('task::app.kanban.toolbar.filters.filters')
                    </p>

                    <button
                        type="button"
                        v-if="hasActiveFilters"
                        class="text-xs font-semibold text-brandColor hover:underline cursor-pointer"
                        @click="clearAll"
                    >
                        @lang('task::app.kanban.toolbar.filters.clear-all')
                    </button>
                </div>
            </x-slot>

            <!-- Drawer Content -->
            <x-slot:content>
                <div class="flex flex-col gap-5 p-1">
                    <!-- Priority Filter -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-semibold text-gray-800 dark:text-white uppercase tracking-wider">
                            @lang('task::app.fields.priority', ['default' => 'Priority'])
                        </label>

                        <div class="grid grid-cols-2 gap-2">
                            <label
                                v-for="p in priorityOptions"
                                :key="p.value"
                                class="flex items-center gap-2 rounded-lg border p-2 cursor-pointer transition text-xs font-medium"
                                :class="selectedPriorities.includes(p.value)
                                    ? 'border-brandColor bg-brandColor/10 text-brandColor dark:border-brandColor dark:text-white'
                                    : 'border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-400'"
                            >
                                <input
                                    type="checkbox"
                                    :value="p.value"
                                    v-model="selectedPriorities"
                                    class="hidden"
                                />
                                <span
                                    class="h-2 w-2 rounded-full"
                                    :style="{ backgroundColor: p.color }"
                                ></span>
                                <span>@{{ p.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Assigned User Filter -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-semibold text-gray-800 dark:text-white uppercase tracking-wider">
                            @lang('task::app.fields.assigned_to', ['default' => 'Assigned User'])
                        </label>

                        <select
                            v-model="selectedUser"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        >
                            <option value="">
                                @lang('task::app.kanban.all-users', ['default' => 'All Users'])
                            </option>
                            <option value="0">
                                @lang('task::app.unassigned')
                            </option>
                            <option
                                v-for="user in assignableUsers"
                                :key="user.id"
                                :value="user.id"
                            >
                                @{{ user.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Due Date Preset / Range -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-semibold text-gray-800 dark:text-white uppercase tracking-wider">
                            @lang('task::app.fields.due_date', ['default' => 'Due Date'])
                        </label>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11px] text-gray-500 dark:text-gray-400">@lang('task::app.kanban.toolbar.filters.from')</label>
                            <input
                                type="date"
                                v-model="dateFrom"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11px] text-gray-500 dark:text-gray-400">@lang('task::app.kanban.toolbar.filters.to')</label>
                            <input
                                type="date"
                                v-model="dateTo"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                    </div>
                </div>
            </x-slot>

            <!-- Drawer Footer -->
            <x-slot:footer>
                <div class="flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        class="transparent-button"
                        @click="clearAll"
                    >
                        @lang('task::app.kanban.toolbar.filters.clear-all')
                    </button>

                    <button
                        type="button"
                        class="primary-button"
                        @click="applyFilters"
                    >
                        @lang('task::app.kanban.toolbar.filters.apply-filters')
                    </button>
                </div>
            </x-slot>
        </x-admin::drawer>

        {!! view_render_event('admin.tasks.index.kanban.filter.drawer.after') !!}
    </script>

    <script type="module">
        app.component('v-tasks-kanban-filter', {
            template: '#v-tasks-kanban-filter-template',

            props: ['isLoading', 'assignableUsers'],

            emits: ['apply-filters'],

            data() {
                return {
                    selectedPriorities: [],
                    selectedUser: '',
                    dateFrom: '',
                    dateTo: '',
                    priorityOptions: [
                        { value: 'urgent', label: '@lang('task::app.priority.urgent')', color: '#DC2626' },
                        { value: 'high', label: '@lang('task::app.priority.high')', color: '#EA580C' },
                        { value: 'medium', label: '@lang('task::app.priority.medium')', color: '#CA8A04' },
                        { value: 'low', label: '@lang('task::app.priority.low')', color: '#2563EB' },
                    ],
                };
            },

            computed: {
                hasActiveFilters() {
                    return this.selectedPriorities.length > 0
                        || this.selectedUser !== ''
                        || this.dateFrom !== ''
                        || this.dateTo !== '';
                },
            },

            methods: {
                applyFilters() {
                    let filters = {
                        columns: [],
                    };

                    if (this.selectedPriorities.length) {
                        filters.columns.push({
                            index: 'priority',
                            value: this.selectedPriorities,
                        });
                    }

                    if (this.selectedUser !== '') {
                        filters.columns.push({
                            index: 'user_id',
                            value: this.selectedUser,
                        });
                    }

                    if (this.dateFrom || this.dateTo) {
                        filters.columns.push({
                            index: 'due_date',
                            type: 'date',
                            value: [this.dateFrom || '', this.dateTo || ''],
                        });
                    }

                    this.$emit('apply-filters', filters);

                    this.$refs.tasksFilterDrawer.close();
                },

                clearAll() {
                    this.selectedPriorities = [];
                    this.selectedUser = '';
                    this.dateFrom = '';
                    this.dateTo = '';

                    this.$emit('apply-filters', { columns: [] });

                    this.$refs.tasksFilterDrawer.close();
                },
            },
        });
    </script>
@endPushOnce
