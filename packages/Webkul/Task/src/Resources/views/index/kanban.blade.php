{!! view_render_event('admin.tasks.index.kanban.before') !!}

<!-- Kanban Vue Component -->
<v-tasks-kanban ref="tasksKanban">
    <div class="flex flex-col gap-4">
        <!-- Shimmer -->
        <x-admin::shimmer.leads.index.kanban />
    </div>
</v-tasks-kanban>

{!! view_render_event('admin.tasks.index.kanban.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-tasks-kanban-template"
    >
        <template v-if="isLoading">
            <div class="flex flex-col gap-4">
                <x-admin::shimmer.leads.index.kanban />
            </div>
        </template>

        <template v-else>
            <div class="flex flex-col gap-4">
                @include('task::index.kanban.toolbar')

                {!! view_render_event('admin.tasks.index.kanban.content.before') !!}

                <div class="flex gap-2.5 overflow-x-auto max-h-[calc(100vh-300px)]">
                    <!-- Stage Cards -->
                    <div
                        class="flex min-w-[275px] max-w-[275px] flex-col gap-1 rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900"
                        v-for="(stage, index) in stageTasks"
                        :key="stage.code"
                    >
                        {!! view_render_event('admin.tasks.index.kanban.content.stage.header.before') !!}

                        <!-- Stage Header -->
                        <div class="flex flex-col px-2 py-3">
                            <!-- Stage Title and Action -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium dark:text-white">
                                    @{{ stage.name }} (@{{ stage.tasks?.meta?.total ?? 0 }})
                                </span>

                                <div class="flex items-center gap-1">
                                    @if (bouncer()->hasPermission('tasks.create'))
                                        <a
                                            :href="'{{ route('admin.tasks.create') }}' + '?status=' + stage.code"
                                            class="icon-add cursor-pointer rounded p-1 text-lg text-gray-600 transition-all hover:bg-gray-200 hover:text-gray-800 dark:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-white"
                                            target="_blank"
                                            title="@lang('task::app.actions.quick-add', ['default' => 'Add Task'])"
                                        ></a>
                                    @endif

                                    <template v-if="! stage.is_default">
                                        @if (bouncer()->hasPermission('tasks.edit'))
                                            <button
                                                type="button"
                                                class="icon-edit cursor-pointer rounded p-1 text-sm text-gray-500 transition hover:bg-gray-200 hover:text-brandColor dark:hover:bg-gray-800"
                                                title="@lang('admin::app.tasks.edit-list')"
                                                @click="openEditStageModal(stage)"
                                            ></button>
                                            <button
                                                type="button"
                                                class="icon-move cursor-pointer rounded p-1 text-sm text-gray-500 transition hover:bg-gray-200 hover:text-brandColor dark:hover:bg-gray-800"
                                                title="@lang('admin::app.tasks.reassign-list')"
                                                @click="openReassignStageModal(stage)"
                                            ></button>
                                        @endif

                                        @if (bouncer()->hasPermission('tasks.delete'))
                                            <button
                                                type="button"
                                                class="icon-delete cursor-pointer rounded p-1 text-sm text-gray-500 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950"
                                                title="@lang('admin::app.tasks.delete-list')"
                                                @click="openDeleteStageModal(stage)"
                                            ></button>
                                        @endif
                                    </template>
                                </div>
                            </div>

                            <!-- Stage Total Tasks and Progress Bar -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-medium dark:text-white">
                                    @{{ stage.tasks?.meta?.total ?? 0 }} @lang('task::app.title', ['default' => 'Tasks'])
                                </span>

                                <!-- Progress Bar -->
                                <div class="h-1 w-36 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                                    <div
                                        class="h-1"
                                        :style="{
                                            backgroundColor: stage.color || '#3B82F6',
                                            width: (totalStagesTasks > 0 ? ((stage.tasks?.meta?.total ?? 0) / totalStagesTasks) * 100 : 0) + '%'
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        {!! view_render_event('admin.tasks.index.kanban.content.stage.header.after') !!}

                        {!! view_render_event('admin.tasks.index.kanban.content.stage.body.before') !!}

                        <!-- Draggable Stage Task Cards -->
                        <draggable
                            class="flex h-[calc(100vh-317px)] flex-col gap-2 overflow-y-auto p-2"
                            :class="{ 'justify-center': !stage.tasks?.data?.length }"
                            ghost-class="draggable-ghost"
                            handle=".task-item"
                            v-bind="{animation: 200}"
                            :list="stage.tasks.data"
                            item-key="id"
                            group="tasks"
                            @scroll="handleScroll(stage, $event)"
                            @change="handleUpdate(stage, $event)"
                        >
                            <template #header>
                                <div
                                    class="flex flex-col items-center justify-center"
                                    v-if="! stage.tasks?.data?.length"
                                >
                                    <img
                                        class="dark:mix-blend-exclusion dark:invert"
                                        src="{{ vite()->asset('images/empty-placeholders/pipedrive.svg') }}"
                                    >

                                    <div class="flex flex-col items-center gap-4">
                                        <div class="flex flex-col items-center gap-2">
                                            <p class="!text-base font-semibold dark:text-white">
                                                @lang('task::app.kanban.empty-list')
                                            </p>

                                            <p class="!text-sm text-gray-400 dark:text-gray-400">
                                                @lang('task::app.kanban.empty-list-description')
                                            </p>
                                        </div>

                                        @if (bouncer()->hasPermission('tasks.create'))
                                            <a
                                                :href="'{{ route('admin.tasks.create') }}' + '?status=' + stage.code"
                                                class="secondary-button"
                                            >
                                                @lang('task::app.kanban.create-task-btn')
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </template>

                            <!-- Task Card -->
                            <template #item="{ element, index }">
                                {!! view_render_event('admin.tasks.index.kanban.content.stage.body.card.before') !!}

                                <div
                                    class="task-item flex cursor-pointer flex-col gap-3 rounded-lg border border-gray-300 bg-gray-100 p-2 shadow-xl shadow-slate-200 dark:border-gray-700 dark:bg-gray-800"
                                    role="button"
                                    tabindex="0"
                                    @click.prevent="openQuickView(element)"
                                    @keydown.enter.prevent="openQuickView(element)"
                                >
                                    {!! view_render_event('admin.tasks.index.kanban.content.stage.body.card.header.before') !!}

                                    <!-- Header -->
                                    <div class="flex items-start justify-between">
                                        <div
                                            class="flex items-center gap-1.5"
                                            v-if="cardFields.assignedUser"
                                        >
                                            <x-admin::avatar ::name="element.assigned_user ? element.assigned_user.name : 'Unassigned'" />

                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs font-medium dark:text-white">
                                                    @{{ element.assigned_user ? element.assigned_user.name : "@lang('task::app.unassigned')" }}
                                                </span>

                                                <span
                                                    class="text-[10px] text-gray-500 dark:text-gray-400 leading-normal truncate max-w-[170px]"
                                                    v-if="element.entity_title"
                                                >
                                                    @{{ element.entity_title }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Overdue Warning Icon -->
                                        <div
                                            class="group relative"
                                            v-if="cardFields.dueDate && element.is_overdue"
                                        >
                                            <span class="icon-rotten cursor-default text-xl text-rose-600"></span>

                                            <div class="absolute -top-1 right-7 hidden w-max flex-col items-center group-hover:flex z-10">
                                                <span class="whitespace-no-wrap relative rounded-md bg-black px-4 py-2 text-xs leading-none text-white shadow-lg">
                                                    @{{ "@lang('task::app.kanban.overdue-days', ['days' => 'replaceDays'])".replace('replaceDays', element.overdue_days || 1) }}
                                                </span>

                                                <div class="absolute -right-1 top-2 h-3 w-3 rotate-45 bg-black"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {!! view_render_event('admin.tasks.index.kanban.content.stage.body.card.header.after') !!}

                                    {!! view_render_event('admin.tasks.index.kanban.content.stage.body.card.title.before') !!}

                                    <!-- Task Title -->
                                    <p class="text-xs font-medium dark:text-white line-clamp-2">
                                        @{{ element.title }}
                                    </p>

                                    {!! view_render_event('admin.tasks.index.kanban.content.stage.body.card.title.after') !!}

                                    <!-- Description Snippet (if enabled) -->
                                    <p
                                        v-if="cardFields.description && element.description"
                                        class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2"
                                    >
                                        @{{ element.description }}
                                    </p>

                                    <!-- Badges row -->
                                    <div class="flex flex-wrap gap-1">
                                        <!-- Priority Badge -->
                                        <div
                                            class="rounded-xl px-2 py-1 text-xs font-medium uppercase tracking-wider"
                                            :class="getPriorityClass(element.priority)"
                                            v-if="cardFields.priority"
                                        >
                                            @{{ element.priority_label }}
                                        </div>

                                        <!-- Due Date Badge -->
                                        <div
                                            class="flex items-center gap-1 rounded-xl bg-gray-200 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                            :class="{ '!bg-rose-100 !text-rose-700 dark:!bg-rose-950 dark:!text-rose-300': element.is_overdue }"
                                            v-if="cardFields.dueDate && element.formatted_due_date"
                                        >
                                            <span class="icon-calendar text-xs"></span>
                                            <span>@{{ element.formatted_due_date }}</span>
                                        </div>

                                        <div
                                            v-if="element.formatted_schedule_from"
                                            class="flex items-center gap-1 rounded-xl bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200"
                                        >
                                            <span class="icon-calendar text-xs"></span>
                                            <span>@{{ element.formatted_schedule_from }}</span>
                                        </div>

                                        <!-- Related Entity Badge -->
                                        <div
                                            class="flex items-center gap-1 rounded-xl bg-sky-100 px-2 py-1 text-xs font-medium text-sky-800 dark:bg-sky-950 dark:text-sky-200"
                                            v-if="cardFields.entity && element.entity_title"
                                        >
                                            <span class="icon-attachment text-xs"></span>
                                            <span class="truncate max-w-[140px]">@{{ element.entity_title }}</span>
                                        </div>
                                    </div>
                                </div>

                                {!! view_render_event('admin.tasks.index.kanban.content.stage.body.card.after') !!}
                            </template>
                        </draggable>

                        {!! view_render_event('admin.tasks.index.kanban.content.stage.body.after') !!}
                    </div>

                    <!-- + Add List Column Card (Trello Style) -->
                    @if (bouncer()->hasPermission('tasks.create'))
                        <div class="flex min-w-[275px] max-w-[275px] shrink-0 flex-col rounded-lg border border-dashed border-gray-300 bg-gray-50/80 p-3 hover:border-brandColor hover:bg-white dark:border-gray-800 dark:bg-gray-900/60 dark:hover:border-brandColor dark:hover:bg-gray-900 transition-all self-start">
                            <button
                                type="button"
                                class="flex w-full items-center justify-center gap-2 rounded-lg py-2 text-xs font-semibold text-gray-600 hover:text-brandColor dark:text-gray-400 dark:hover:text-white transition-colors"
                                @click="openCreateStageModal"
                            >
                                <span class="icon-add text-base"></span>
                                <span>@lang('task::app.kanban.add-list', ['default' => '+ Add List'])</span>
                            </button>
                        </div>
                    @endif
                </div>

                {!! view_render_event('admin.tasks.index.kanban.content.after') !!}
            </div>

            <!-- Task Quick View Drawer -->
            <x-admin::drawer
                ref="taskQuickViewDrawer"
                width="420px"
                auto-height
            >
                <x-slot:header>
                    <p class="text-base font-semibold dark:text-white">
                        @lang('task::app.view.title', ['default' => 'Task Details'])
                    </p>
                </x-slot>

                <x-slot:content>
                    <div
                        class="flex flex-col gap-4"
                        v-if="quickView.task"
                    >
                        <!-- Related CRM Record Box -->
                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-800 dark:bg-gray-950"
                            v-if="quickView.task.entity_title"
                        >
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                @lang('task::app.view.relationships', ['default' => 'Related Record'])
                            </p>

                            <div class="mt-1 flex items-center justify-between">
                                <span class="font-medium dark:text-white">
                                    @{{ quickView.task.entity_title }}
                                </span>

                                <a
                                    v-if="quickView.task.entity_url"
                                    :href="quickView.task.entity_url"
                                    target="_blank"
                                    class="text-xs font-medium text-brandColor hover:underline"
                                >
                                    @lang('task::app.datagrid.view', ['default' => 'View'])
                                </a>
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('task::app.fields.title', ['default' => 'Title'])
                            </label>

                            <input
                                type="text"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.title"
                            />
                        </div>

                        <!-- Assigned User -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('task::app.fields.assigned_to', ['default' => 'Assigned To'])
                            </label>

                            <select
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.assigned_to"
                            >
                                <option :value="null">
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

                        <!-- Status / Stage -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('task::app.fields.status', ['default' => 'Status'])
                            </label>

                            <select
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.status"
                            >
                                <option
                                    v-for="stage in stagesList"
                                    :key="stage.code"
                                    :value="stage.code"
                                >
                                    @{{ stage.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Priority -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('task::app.fields.priority', ['default' => 'Priority'])
                            </label>

                            <select
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.priority"
                            >
                                <option value="urgent">@lang('task::app.priority.urgent')</option>
                                <option value="high">@lang('task::app.priority.high')</option>
                                <option value="medium">@lang('task::app.priority.medium')</option>
                                <option value="low">@lang('task::app.priority.low')</option>
                            </select>
                        </div>

                        <!-- Due Date & Time -->
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-gray-800 dark:text-white">
                                    @lang('task::app.fields.due_date', ['default' => 'Due Date'])
                                </label>

                                <input
                                    type="date"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    v-model="quickView.form.due_date"
                                />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-gray-800 dark:text-white">
                                    @lang('task::app.fields.due_time', ['default' => 'Due Time'])
                                </label>

                                <input
                                    type="time"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    v-model="quickView.form.due_time"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-gray-800 dark:text-white">
                                    @lang('task::app.fields.schedule_from')
                                </label>
                                <input
                                    type="datetime-local"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    v-model="quickView.form.schedule_from"
                                />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-gray-800 dark:text-white">
                                    @lang('task::app.fields.schedule_to')
                                </label>
                                <input
                                    type="datetime-local"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    v-model="quickView.form.schedule_to"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('task::app.fields.location')
                            </label>
                            <input
                                type="text"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.location"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('task::app.fields.reminder')
                            </label>
                            <select
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.reminder_minutes"
                            >
                                <option value="">@lang('task::app.fields.no-reminder')</option>
                                <option value="5">@lang('task::app.fields.reminder-option', ['minutes' => 5])</option>
                                <option value="15">@lang('task::app.fields.reminder-option', ['minutes' => 15])</option>
                                <option value="30">@lang('task::app.fields.reminder-option', ['minutes' => 30])</option>
                                <option value="60">@lang('task::app.fields.reminder-option', ['minutes' => 60])</option>
                                <option value="1440">@lang('task::app.fields.reminder-day')</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('task::app.fields.participants')
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <select
                                    multiple
                                    size="4"
                                    v-model="quickView.form.participants.users"
                                    class="w-full rounded-md border border-gray-300 bg-white px-2 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    aria-label="@lang('task::app.fields.users')"
                                >
                                    <option v-for="user in assignableUsers" :key="'participant-user-' + user.id" :value="user.id">
                                        @{{ user.name }}
                                    </option>
                                </select>
                                <select
                                    multiple
                                    size="4"
                                    v-model="quickView.form.participants.persons"
                                    class="w-full rounded-md border border-gray-300 bg-white px-2 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    aria-label="@lang('task::app.fields.persons')"
                                >
                                    <option v-for="person in participantPersons" :key="'participant-person-' + person.id" :value="person.id">
                                        @{{ person.name }}
                                    </option>
                                </select>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @lang('task::app.fields.participant-selection-help')
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('task::app.fields.description', ['default' => 'Description'])
                            </label>

                            <textarea
                                rows="3"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.description"
                            ></textarea>
                        </div>
                    </div>
                </x-slot>

                <x-slot:footer>
                    <div class="flex w-full items-center justify-end gap-2.5">
                        <x-admin::button
                            class="primary-button"
                            :title="trans('task::app.kanban.quick-view.save-btn')"
                            ::loading="quickView.saving"
                            ::disabled="quickView.saving"
                            @click="saveQuickView"
                        />

                        <a
                            v-if="quickView.task"
                            :href="'{{ route('admin.tasks.view', ':id') }}'.replace(':id', quickView.task.id)"
                            class="secondary-button"
                        >
                            @lang('task::app.kanban.quick-view.open-full')
                        </a>
                    </div>
                </x-slot>
            </x-admin::drawer>

            <!-- Create / Edit List Stage Modal -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="stageFormRef"
            >
                <form @submit="handleSubmit($event, saveStage)">
                    <x-admin::modal ref="taskStageCreateModal">
                        <x-slot:header>
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @{{ isEditingStage ? "@lang('admin::app.tasks.edit-list')" : "@lang('task::app.kanban.create-list-title', ['default' => 'Create New List Column'])" }}
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <div class="flex flex-col gap-4">
                                <!-- Stage Name -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('task::app.kanban.list-name', ['default' => 'List Name'])
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="stage_name"
                                        name="name"
                                        rules="required|max:100"
                                        v-model="newStage.name"
                                        :label="trans('task::app.kanban.list-name', ['default' => 'List Name'])"
                                        :placeholder="trans('task::app.kanban.list-name-placeholder', ['default' => 'e.g., In Review, QA, Blocked...'])"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <!-- Stage Color -->
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-medium text-gray-800 dark:text-white">
                                        @lang('task::app.kanban.list-color', ['default' => 'List Color'])
                                    </label>

                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <template v-for="color in stageColors" :key="color">
                                            <button
                                                type="button"
                                                class="h-6 w-6 rounded-full transition-all focus:outline-none"
                                                :class="{ 'ring-2 ring-offset-2 ring-brandColor dark:ring-offset-gray-900 scale-110': newStage.color === color }"
                                                :style="{ backgroundColor: color }"
                                                @click="newStage.color = color"
                                            ></button>
                                        </template>

                                        <div class="flex items-center gap-2 ml-1">
                                            <input
                                                type="color"
                                                v-model="newStage.color"
                                                class="h-7 w-8 cursor-pointer rounded border border-gray-300 bg-white p-0.5 dark:border-gray-700 dark:bg-gray-900"
                                                title="@lang('task::app.kanban.custom-color', ['default' => 'Custom Color'])"
                                            />
                                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">@{{ newStage.color }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-slot>

                        <x-slot:footer>
                            <div class="flex items-center justify-end gap-2.5">
                                <button
                                    type="button"
                                    class="secondary-button"
                                    @click="$refs.taskStageCreateModal.close()"
                                >
                                    @lang('task::app.create.cancel', ['default' => 'Cancel'])
                                </button>

                                <button
                                    type="submit"
                                    class="primary-button"
                                    :disabled="isSubmittingStage"
                                >
                                    <span v-if="isSubmittingStage" class="spinner-border mr-1.5 h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent inline-block"></span>
                                    @{{ isEditingStage ? "@lang('admin::app.tasks.save-list')" : "@lang('task::app.kanban.save-list-btn', ['default' => 'Create List'])" }}
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            <x-admin::modal ref="taskStageReassignModal">
                <x-slot:header>
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.tasks.reassign-list')
                    </p>
                </x-slot:header>

                <x-slot:content>
                    <div class="flex flex-col gap-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @{{ stageToReassign?.name }}
                            <span class="block mt-1">@lang('admin::app.tasks.reassign-list-help')</span>
                        </p>

                        <label for="stage_reassign_destination" class="text-xs font-medium text-gray-800 dark:text-white">
                            @lang('admin::app.tasks.reassign-list-destination')
                        </label>
                        <select
                            id="stage_reassign_destination"
                            v-model="stageReassignDestination"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        >
                            <option :value="null">@lang('admin::app.tasks.reassign-list-select-destination')</option>
                            <option v-for="destination in reassignDestinationStages" :key="destination.code" :value="destination.code">
                                @{{ destination.name }}
                            </option>
                        </select>
                    </div>
                </x-slot:content>

                <x-slot:footer>
                    <div class="flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            class="secondary-button"
                            :disabled="isReassigningStage"
                            @click="$refs.taskStageReassignModal.close()"
                        >
                            @lang('task::app.create.cancel', ['default' => 'Cancel'])
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            :disabled="isReassigningStage || ! stageReassignDestination"
                            @click="reassignStage"
                        >
                            <span v-if="isReassigningStage" class="spinner-border mr-1.5 h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent inline-block"></span>
                            @lang('admin::app.tasks.reassign-list')
                        </button>
                    </div>
                </x-slot:footer>
            </x-admin::modal>

            <x-admin::modal ref="taskStageDeleteModal">
                <x-slot:header>
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.tasks.delete-list')
                    </p>
                </x-slot:header>

                <x-slot:content>
                    <div class="flex flex-col gap-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            @{{ stageToDelete?.name }}
                            <span class="block mt-1">@lang('admin::app.tasks.confirm-delete-list')</span>
                        </p>

                        <label class="flex items-start gap-2 text-sm text-gray-800 dark:text-white">
                            <input
                                type="radio"
                                name="stage_delete_action"
                                value="delete_tasks"
                                v-model="stageDeleteAction"
                                class="mt-0.5"
                            >
                            <span>
                                <span class="block font-medium">@lang('admin::app.tasks.delete-list-delete-tasks')</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.tasks.delete-list-delete-warning')</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-2 text-sm text-gray-800 dark:text-white">
                            <input
                                type="radio"
                                name="stage_delete_action"
                                value="reassign_tasks"
                                v-model="stageDeleteAction"
                                :disabled="! destinationStages.length"
                                class="mt-0.5"
                            >
                            <span class="font-medium">@lang('admin::app.tasks.delete-list-move-tasks')</span>
                        </label>

                        <div v-if="stageDeleteAction === 'reassign_tasks'" class="flex flex-col gap-1">
                            <label for="stage_delete_destination" class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('admin::app.tasks.delete-list-destination')
                            </label>
                            <select
                                id="stage_delete_destination"
                                v-model="stageDeleteDestination"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            >
                                <option :value="null">@lang('admin::app.tasks.delete-list-select-destination')</option>
                                <option v-for="destination in destinationStages" :key="destination.code" :value="destination.code">
                                    @{{ destination.name }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @lang('admin::app.tasks.delete-list-move-help')
                            </p>
                        </div>
                    </div>
                </x-slot:content>

                <x-slot:footer>
                    <div class="flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            class="secondary-button"
                            :disabled="isDeletingStage"
                            @click="$refs.taskStageDeleteModal.close()"
                        >
                            @lang('task::app.create.cancel', ['default' => 'Cancel'])
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            :disabled="isDeletingStage || ! stageDeleteAction || (stageDeleteAction === 'reassign_tasks' && ! stageDeleteDestination)"
                            @click="deleteStage"
                        >
                            <span v-if="isDeletingStage" class="spinner-border mr-1.5 h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent inline-block"></span>
                            @lang('admin::app.tasks.delete-list')
                        </button>
                    </div>
                </x-slot:footer>
            </x-admin::modal>
        </template>
    </script>

    <script type="module">
        app.component('v-tasks-kanban', {
            template: '#v-tasks-kanban-template',

            data() {
                return {
                    src: "{{ route('admin.tasks.get') }}",

                    applied: {
                        filters: {
                            columns: [],
                        }
                    },

                    stages: @json($pipeline->stages->toArray() ?? []),

                    assignableUsers: @json($assignableUsers ?? []),

                    participantPersons: @json($participantPersons ?? []),

                    selectedUserFilter: null,

                    stageTasks: {},

                    isLoading: true,

                    isLoadingMore: false,

                    quickView: {
                        task: null,
                        saving: false,
                        form: {
                            title: '',
                            assigned_to: null,
                            status: 'pending',
                            priority: 'medium',
                            due_date: '',
                            due_time: '',
                            schedule_from: '',
                            schedule_to: '',
                            location: '',
                            reminder_minutes: '',
                            participants: {
                                users: [],
                                persons: [],
                            },
                            description: '',
                        },
                    },

                    cardFields: {
                        assignedUser: true,
                        priority: true,
                        dueDate: true,
                        entity: true,
                        description: false,
                    },

                    isSubmittingStage: false,

                    stageToDelete: null,

                    stageDeleteAction: null,

                    stageDeleteDestination: null,

                    stageToReassign: null,

                    stageReassignDestination: null,

                    isDeletingStage: false,

                    isReassigningStage: false,

                    isEditingStage: false,

                    editingStageCode: null,

                    newStage: {
                        name: '',
                        color: '#3B82F6',
                    },

                    stageColors: [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6',
                        '#EC4899',
                        '#06B6D4',
                        '#6B7280',
                    ],
                };
            },

            computed: {
                stagesList() {
                    return this.stages;
                },

                destinationStages() {
                    return this.stages.filter(stage => stage.code !== this.stageToDelete?.code);
                },

                reassignDestinationStages() {
                    return this.stages.filter(stage => stage.code !== this.stageToReassign?.code);
                },

                totalStagesTasks() {
                    let total = 0;

                    for (let [key, stage] of Object.entries(this.stageTasks)) {
                        total += parseInt(stage.tasks?.meta?.total ?? stage.tasks?.data?.length ?? 0);
                    }

                    return total;
                },
            },

            mounted() {
                this.boot();

                this.$emitter.on('reload-datagrids', () => {
                    this.get().then(response => {
                        if (response?.data) {
                            for (let [stageKey, data] of Object.entries(response.data)) {
                                this.stageTasks[stageKey] = data;
                            }
                        }
                    });
                });

                this.$emitter.on('open-create-task-stage-modal', () => {
                    this.openCreateStageModal();
                });
            },

            methods: {
                boot() {
                    this.get().then(response => {
                        if (response?.data) {
                            for (let [stageKey, data] of Object.entries(response.data)) {
                                this.stageTasks[stageKey] = data;
                            }
                        }
                    });
                },

                get(requestedParams = {}) {
                    let params = {
                        search: '',
                        searchFields: '',
                        limit: 10,
                    };

                    this.applied.filters.columns.forEach((column) => {
                        if (column.index === 'all') {
                            if (! column.value?.length) {
                                return;
                            }

                            params['search'] += `title:${column.value.join(',')};`;
                            params['searchFields'] += `title:like;`;

                            return;
                        }

                        if (column.index === 'user_id' || column.index === 'assigned_to') {
                            return;
                        }

                        if (column.type === 'date' || column.type === 'datetime') {
                            if (column.value?.length) {
                                params[column.index] = column.value;
                            }

                            return;
                        }

                        params['search'] += `${column.index}:${column.value.join(',')};`;
                        params['searchFields'] += `${column.index}:in;`;
                    });

                    if (this.selectedUserFilter !== null) {
                        params.assigned_user_id = this.selectedUserFilter;
                    }

                    return this.$axios
                        .get("{{ route('admin.tasks.get') }}", {
                            params: {
                                ...params,
                                ...requestedParams,
                            }
                        })
                        .then(response => {
                            this.isLoading = false;

                            return response;
                        })
                        .catch(error => {
                            this.isLoading = false;
                            console.error(error);
                        });
                },

                filter(filters) {
                    this.applied.filters.columns = [
                        ...(this.applied.filters.columns.filter((column) => column.index === 'all')),
                        ...filters.columns,
                    ];

                    const userFilter = filters.columns.find((column) => column.index === 'user_id');

                    if (userFilter) {
                        const value = Array.isArray(userFilter.value) ? userFilter.value[0] : userFilter.value;
                        this.selectedUserFilter = value?.value ?? value ?? null;
                    }

                    this.get().then(response => {
                        if (response?.data) {
                            for (let [stageKey, data] of Object.entries(response.data)) {
                                this.stageTasks[stageKey] = data;
                            }
                        }
                    });
                },

                search(filters) {
                    this.applied.filters.columns = [
                        ...(this.applied.filters.columns.filter((column) => column.index !== 'all')),
                        ...filters.columns,
                    ];

                    this.get().then(response => {
                        if (response?.data) {
                            for (let [stageKey, data] of Object.entries(response.data)) {
                                this.stageTasks[stageKey] = data;
                            }
                        }
                    });
                },

                filterByUser(userId) {
                    this.selectedUserFilter = userId;

                    this.applied.filters.columns = this.applied.filters.columns.filter(
                        (column) => column.index !== 'user_id' && column.index !== 'assigned_to'
                    );

                    this.isLoading = true;

                    this.get().then(response => {
                        if (response?.data) {
                            for (let [stageKey, data] of Object.entries(response.data)) {
                                this.stageTasks[stageKey] = data;
                            }
                        }
                    });
                },

                openQuickView(task) {
                    this.quickView.task = task;

                    this.quickView.form = {
                        title: task.title ?? '',
                        assigned_to: task.assigned_to ?? null,
                        status: task.status ?? 'pending',
                        priority: task.priority ?? 'medium',
                        due_date: task.due_date ?? '',
                        due_time: task.due_time ?? '',
                        schedule_from: task.schedule_from ?? '',
                        schedule_to: task.schedule_to ?? '',
                        location: task.location ?? '',
                        reminder_minutes: task.reminder_minutes == null ? '' : String(task.reminder_minutes),
                        participants: {
                            users: (task.participants?.users || []).map(participant => participant.id),
                            persons: (task.participants?.persons || []).map(participant => participant.id),
                        },
                        description: task.description ?? '',
                    };

                    this.$refs.taskQuickViewDrawer.open();
                },

                saveQuickView() {
                    if (! this.quickView.task) {
                        return;
                    }

                    this.quickView.saving = true;

                    this.$axios.put(
                        "{{ route('admin.tasks.quick_update', ':id') }}".replace(':id', this.quickView.task.id),
                        {
                            ...this.quickView.form,
                            assigned_to: this.quickView.form.assigned_to || null,
                        }
                    )
                        .then(response => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message || 'Task updated successfully.'
                            });

                            this.$refs.taskQuickViewDrawer.close();

                            this.get().then(boardResponse => {
                                if (boardResponse?.data) {
                                    for (let [stageKey, data] of Object.entries(boardResponse.data)) {
                                        this.stageTasks[stageKey] = data;
                                    }
                                }
                            });
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || error.message,
                            });
                        })
                        .finally(() => {
                            this.quickView.saving = false;
                        });
                },

                append(params) {
                    return this.get(params).then(response => {
                        if (response?.data) {
                            for (let [stageKey, data] of Object.entries(response.data)) {
                                if (! this.stageTasks[stageKey]) {
                                    this.stageTasks[stageKey] = data;
                                } else {
                                    this.stageTasks[stageKey].tasks.data = this.stageTasks[stageKey].tasks.data.concat(data.tasks.data);
                                    this.stageTasks[stageKey].tasks.meta = data.tasks.meta;
                                }
                            }
                        }
                    });
                },

                handleUpdate(stage, event) {
                    if (event.moved) {
                        return;
                    }

                    if (event.removed) {
                        return;
                    }

                    this.$axios.put(
                        "{{ route('admin.tasks.stage.update', '__TASK_ID__') }}".replace('__TASK_ID__', event.added.element.id),
                        { status: stage.code }
                    )
                        .then(response => {
                            this.get().then(response => {
                                if (response?.data) {
                                    for (let [stageKey, data] of Object.entries(response.data)) {
                                        this.stageTasks[stageKey] = data;
                                    }
                                }
                            });

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message || 'Task status updated.'
                            });
                        })
                        .catch(error => {
                            this.get().then(response => {
                                if (response?.data) {
                                    for (let [stageKey, data] of Object.entries(response.data)) {
                                        this.stageTasks[stageKey] = data;
                                    }
                                }
                            });

                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || 'Failed to update task status.'
                            });
                        });
                },

                handleScroll(stage, event) {
                    const bottom = event.target.scrollHeight - event.target.scrollTop - event.target.clientHeight <= 5;

                    if (! bottom) {
                        return;
                    }

                    if (this.isLoadingMore) {
                        return;
                    }

                    const currentStageMeta = this.stageTasks[stage.code]?.tasks?.meta;

                    if (! currentStageMeta || currentStageMeta.current_page >= currentStageMeta.last_page) {
                        return;
                    }

                    this.isLoadingMore = true;

                    this.append({
                        stage_id: stage.code,
                        page: currentStageMeta.current_page + 1,
                        limit: 10,
                    }).finally(() => {
                        this.isLoadingMore = false;
                    });
                },

                updateCardFields(fields) {
                    this.cardFields = { ...fields };
                },

                getPriorityClass(priority) {
                    switch (priority) {
                        case 'urgent':
                            return 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
                        case 'high':
                            return 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300';
                        case 'medium':
                            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300';
                        case 'low':
                            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
                        default:
                            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                    }
                },

                openCreateStageModal() {
                    this.isEditingStage = false;
                    this.editingStageCode = null;
                    this.newStage = {
                        name: '',
                        color: '#3B82F6',
                    };

                    this.$refs.taskStageCreateModal.open();
                },

                openEditStageModal(stage) {
                    this.isEditingStage = true;
                    this.editingStageCode = stage.code;
                    this.newStage = {
                        name: stage.name,
                        color: stage.color || '#3B82F6',
                    };

                    this.$refs.taskStageCreateModal.open();
                },

                reloadStagesAndBoard(stages) {
                    this.stages = stages;

                    this.get().then(response => {
                        if (response?.data) {
                            this.stageTasks = response.data;
                        }
                    });
                },

                openReassignStageModal(stage) {
                    this.stageToReassign = stage;
                    this.stageReassignDestination = null;

                    this.$refs.taskStageReassignModal.open();
                },

                reassignStage() {
                    if (! this.stageToReassign || ! this.stageReassignDestination) {
                        return;
                    }

                    this.isReassigningStage = true;

                    this.$axios.put(
                        "{{ route('admin.tasks.stages.reassign', '__CODE__') }}".replace('__CODE__', encodeURIComponent(this.stageToReassign.code)),
                        {
                            destination_stage: this.stageReassignDestination,
                        }
                    )
                        .then(response => {
                            this.$refs.taskStageReassignModal.close();
                            this.stageToReassign = null;
                            this.stageReassignDestination = null;
                            this.reloadStagesAndBoard(response.data.stages || []);

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data?.message || "@lang('admin::app.tasks.list-tasks-reassigned')"
                            });
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || error.message
                            });
                        })
                        .finally(() => {
                            this.isReassigningStage = false;
                        });
                },

                openDeleteStageModal(stage) {
                    this.stageToDelete = stage;
                    this.stageDeleteAction = null;
                    this.stageDeleteDestination = null;

                    this.$refs.taskStageDeleteModal.open();
                },

                deleteStage() {
                    if (! this.stageToDelete || ! this.stageDeleteAction) {
                        return;
                    }

                    this.isDeletingStage = true;

                    this.$axios.delete(
                        "{{ route('admin.tasks.stages.delete', '__CODE__') }}".replace('__CODE__', encodeURIComponent(this.stageToDelete.code)),
                        {
                            data: {
                                action: this.stageDeleteAction,
                                destination_stage: this.stageDeleteDestination,
                            },
                        }
                    )
                        .then(response => {
                            this.$refs.taskStageDeleteModal.close();
                            this.reloadStagesAndBoard(response.data.stages || []);

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data?.message || "@lang('admin::app.tasks.list-deleted')"
                            });
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || error.message
                            });
                        })
                        .finally(() => {
                            this.isDeletingStage = false;
                        });
                },

                saveStage(params, { resetForm }) {
                    const name = this.newStage.name?.trim();

                    if (! name) {
                        return;
                    }

                    this.isSubmittingStage = true;

                    const request = this.isEditingStage
                        ? this.$axios.put("{{ route('admin.tasks.stages.update', '__CODE__') }}".replace('__CODE__', encodeURIComponent(this.editingStageCode)), {
                            name: name,
                            color: this.newStage.color || '#3B82F6',
                        })
                        : this.$axios.post("{{ route('admin.tasks.stages.store') }}", {
                            name: name,
                            color: this.newStage.color || '#3B82F6',
                        });

                    request
                        .then(response => {
                            this.$refs.taskStageCreateModal.close();
                            resetForm();

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data?.message || 'List column created successfully.'
                            });

                            this.reloadStagesAndBoard(response.data?.stages || []);
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || error.message || 'Failed to create list column.'
                            });
                        })
                        .finally(() => {
                            this.isSubmittingStage = false;
                        });
                },
            }
        });
    </script>
@endPushOnce
