{!! view_render_event('admin.leads.index.kanban.before') !!}

<!-- Kanban Vue Component -->
<v-leads-kanban ref="leadsKanban">
    <div class="flex flex-col gap-4">
        <!-- Shimmer -->
        <x-admin::shimmer.leads.index.kanban />
    </div>
</v-leads-kanban>

{!! view_render_event('admin.leads.index.kanban.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-leads-kanban-template"
    >
        <template v-if="isLoading">
            <div class="flex flex-col gap-4">
                <x-admin::shimmer.leads.index.kanban />
            </div>
        </template>

        <template v-else>
            <div class="flex flex-col gap-4">
                @include('admin::leads.index.kanban.toolbar')

                {!! view_render_event('admin.leads.index.kanban.content.before') !!}

                <div class="flex gap-2.5 overflow-x-auto max-h-[calc(100vh-300px)]">
                    <!-- Stage Cards -->
                    <div
                        class="flex min-w-[275px] max-w-[275px] flex-col gap-1 rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900"
                        v-for="(stage, index) in stageLeads"
                    >
                        {!! view_render_event('admin.leads.index.kanban.content.stage.header.before') !!}

                        <!-- Stage Header -->
                        <div class="flex flex-col px-2 py-3">
                            <!-- Stage Title and Action -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium dark:text-white">
                                    @{{ stage.name }} (@{{ stage.leads.meta.total }})
                                </span>

                                @if (bouncer()->hasPermission('leads.create'))
                                    <a
                                        :href="'{{ route('admin.leads.create') }}' + '?stage_id=' + stage.id"
                                        class="icon-add cursor-pointer rounded p-1 text-lg text-gray-600 transition-all hover:bg-gray-200 hover:text-gray-800 dark:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-white"
                                        target="_blank"
                                    >
                                    </a>
                                @endif
                            </div>

                            <!-- Stage Total Leads and Amount -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-medium dark:text-white">
                                    @{{ $admin.formatPrice(stage.lead_value, 2) }}
                                </span>

                                <!-- Progress Bar -->
                                <div class="h-1 w-36 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                                    <div
                                        class="h-1 bg-green-500"
                                        :style="{ width: (stage.lead_value / totalStagesAmount) * 100 + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        {!! view_render_event('admin.leads.index.kanban.content.stage.header.after') !!}

                        {!! view_render_event('admin.leads.index.kanban.content.stage.body.before') !!}

                        <!-- Draggable Stage Lead Cards -->
                        <draggable
                            class="flex h-[calc(100vh-317px)] flex-col gap-2 overflow-y-auto p-2"
                            :class="{ 'justify-center': stage.leads.data.length === 0 }"
                            ghost-class="draggable-ghost"
                            handle=".lead-item"
                            v-bind="{animation: 200}"
                            :list="stage.leads.data"
                            item-key="id"
                            group="leads"
                            @scroll="handleScroll(stage, $event)"
                            @change="handleUpdate(stage, $event)"
                        >
                            <template #header>
                                <div
                                    class="flex flex-col items-center justify-center"
                                    v-if="! stage.leads.data.length"
                                >
                                    <img
                                        class="dark:mix-blend-exclusion dark:invert"
                                        src="{{ vite()->asset('images/empty-placeholders/pipedrive.svg') }}"
                                    >

                                    <div class="flex flex-col items-center gap-4">
                                        <div class="flex flex-col items-center gap-2">
                                            <p class="!text-base font-semibold dark:text-white">
                                                @lang('admin::app.leads.index.kanban.empty-list')
                                            </p>

                                            <p class="!text-sm text-gray-400 dark:text-gray-400">
                                                @lang('admin::app.leads.index.kanban.empty-list-description')
                                            </p>
                                        </div>

                                        @if (bouncer()->hasPermission('leads.create'))
                                            <a
                                                :href="'{{ route('admin.leads.create') }}' + '?stage_id=' + stage.id"
                                                class="secondary-button"
                                            >
                                                @lang('admin::app.leads.index.kanban.create-lead-btn')
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </template>

                            <!-- Lead Card -->
                            <template #item="{ element, index }">
                                {!! view_render_event('admin.leads.index.kanban.content.stage.body.card.before') !!}

                                <div
                                    class="lead-item flex cursor-pointer flex-col gap-3 rounded-lg border border-gray-300 bg-gray-100 p-2 shadow-xl shadow-slate-200 dark:border-gray-400 dark:bg-gray-400"
                                    role="button"
                                    tabindex="0"
                                    @click.prevent="openQuickView(element)"
                                    @keydown.enter.prevent="openQuickView(element)"
                                >
                                    {!! view_render_event('admin.leads.index.kanban.content.stage.body.card.header.before') !!}

                                    <!-- Header -->
                                    <div class="flex items-start justify-between">
                                        <div
                                            class="flex items-center gap-1"
                                            v-if="cardFields.contactPerson"
                                        >
                                            <x-admin::avatar ::name="element.person ? element.person.name : 'Unknown'" />

                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs font-medium">
                                                    @{{ element.person ? element.person.name : 'Unknown' }}
                                                </span>

                                                <span class="text-[10px] leading-normal">
                                                    @{{ element.person && element.person.organization ? element.person.organization.name : '' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="group relative"
                                            v-if="cardFields.rottenDays && element.rotten_days > 0"
                                        >
                                            <span class="icon-rotten cursor-default text-xl text-rose-600"></span>

                                            <div class="absolute -top-1 right-7 hidden w-max flex-col items-center group-hover:flex">
                                                <span class="whitespace-no-wrap relative rounded-md bg-black px-4 py-2 text-xs leading-none text-white shadow-lg">
                                                    @{{ "@lang('admin::app.leads.index.kanban.rotten-days', ['days' => 'replaceDays'])".replace('replaceDays', element.rotten_days) }}
                                                </span>

                                                <div class="absolute -right-1 top-2 h-3 w-3 rotate-45 bg-black"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {!! view_render_event('admin.leads.index.kanban.content.stage.body.card.header.after') !!}

                                    {!! view_render_event('admin.leads.index.kanban.content.stage.body.card.title.before') !!}

                                    <!-- Lead Title -->
                                    <p class="text-xs font-medium">
                                        @{{ element.title }}
                                    </p>

                                    {!! view_render_event('admin.leads.index.kanban.content.stage.body.card.title.after') !!}

                                    <div class="flex flex-wrap gap-1">
                                        <div
                                            class="flex items-center gap-1 rounded-xl px-2 py-1 text-xs font-medium"
                                            :class="element.user
                                                ? 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-100'
                                                : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100'"
                                            v-if="cardFields.assignedUser"
                                        >
                                            <span class="icon-settings-user text-sm"></span>

                                            @{{ element.user ? element.user.name : "@lang('admin::app.leads.index.kanban.unassigned')" }}
                                        </div>

                                        <div
                                            class="rounded-xl bg-gray-200 px-2 py-1 text-xs font-medium dark:bg-gray-800 dark:text-white"
                                            v-if="cardFields.leadValue"
                                        >
                                            @{{ element.formatted_lead_value }}
                                        </div>

                                        <div
                                            class="rounded-xl bg-gray-200 px-2 py-1 text-xs font-medium dark:bg-gray-800 dark:text-white"
                                            v-if="cardFields.source && element.source"
                                        >
                                            @{{ element.source.name }}
                                        </div>

                                        <div
                                            class="rounded-xl bg-gray-200 px-2 py-1 text-xs font-medium dark:bg-gray-800 dark:text-white"
                                            v-if="cardFields.type && element.type"
                                        >
                                            @{{ element.type.name }}
                                        </div>

                                        <!-- Tags -->
                                        <template v-if="cardFields.tags">
                                            <template v-for="tag in element.tags">
                                                {!! view_render_event('admin.leads.index.kanban.content.stage.body.card.tag.before') !!}

                                                <div
                                                    class="rounded-xl bg-gray-200 px-2 py-1 text-xs font-medium dark:bg-gray-800"
                                                    :style="{
                                                        backgroundColor: tag.color,
                                                        color: tagTextColor[tag.color]
                                                    }"
                                                >
                                                    @{{ tag.name }}
                                                </div>

                                                {!! view_render_event('admin.leads.index.kanban.content.stage.body.card.tag.after') !!}
                                            </template>
                                        </template>
                                    </div>

                                    <div
                                        class="flex items-start gap-1 rounded-md border border-dashed border-gray-300 bg-white/70 px-2 py-1.5 text-[11px] text-gray-700 dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-200"
                                        v-if="cardFields.nextActivity && element.next_activity"
                                    >
                                        <span class="icon-calendar text-sm text-brandColor"></span>

                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-medium">
                                                @{{ element.next_activity.title || element.next_activity.type }}
                                            </p>

                                            <p
                                                class="truncate text-gray-500 dark:text-gray-400"
                                                v-if="element.next_activity.schedule_from"
                                            >
                                                @{{ formatActivitySchedule(element.next_activity.schedule_from) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {!! view_render_event('admin.leads.index.kanban.content.stage.body.card.after') !!}
                            </template>
                        </draggable>

                        {!! view_render_event('admin.leads.index.kanban.content.stage.body.after') !!}
                    </div>
                </div>

                {!! view_render_event('admin.leads.index.kanban.content.after') !!}
            </div>

            <!-- Show modal for additional information while updating the leads into won or lost stage. -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="stageUpdateForm"
                >
                <form @submit="handleSubmit($event, handleFormSubmit)">
                    <!-- Modal -->
                    <x-admin::modal
                        ref="stageUpdateModal"
                        @toggle="handleCloseModal"
                    >
                        <!-- Header -->
                        <x-slot:header>
                            <h3 class="text-base font-semibold dark:text-white">
                                @lang('admin::app.leads.index.kanban.stages.need-more-info')
                            </h3>
                        </x-slot>

                        <!-- Content -->
                        <x-slot:content>
                            <x-admin::form.control-group.control
                                type="hidden"
                                name="lead_pipeline_stage_id"
                                ::value="finalized.stage.id"
                            />

                            <!-- Won Value -->
                            <template v-if="finalized.stage.code == 'won'">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.leads.index.kanban.stages.won-value')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="lead_value"
                                        ::value="finalized.lead.lead_value"
                                    />
                                </x-admin::form.control-group>
                            </template>

                            <!-- Lost Reason -->
                            <template v-else>
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.leads.index.kanban.stages.lost-reason')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        name="lost_reason"
                                    />
                                </x-admin::form.control-group>
                            </template>

                            <!-- Closed At -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.leads.index.kanban.stages.closed-at')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="datetime"
                                    name="closed_at"
                                    :label="trans('admin::app.leads.index.kanban.stages.closed-at')"
                                />

                                <x-admin::form.control-group.error control-name="closed_at"/>
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Footer -->
                        <x-slot:footer>
                            <x-admin::button
                                class="primary-button"
                                :title="trans('admin::app.leads.index.kanban.stages.save-btn')"
                                ::loading="finalized.updating"
                                ::disabled="finalized.updating"
                            />
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            <!-- Lead Quick View Drawer -->
            <x-admin::drawer
                ref="leadQuickViewDrawer"
                width="420px"
            >
                <x-slot:header>
                    <p class="text-base font-semibold dark:text-white">
                        @lang('admin::app.leads.index.kanban.quick-view.title')
                    </p>
                </x-slot>

                <x-slot:content>
                    <div
                        class="flex flex-col gap-4"
                        v-if="quickView.lead"
                    >
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-800 dark:bg-gray-950">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                @lang('admin::app.leads.index.kanban.quick-view.contact')
                            </p>

                            <p class="mt-1 font-medium dark:text-white">
                                @{{ quickView.lead.person ? quickView.lead.person.name : '—' }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('admin::app.leads.index.kanban.columns.title')
                            </label>

                            <input
                                type="text"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.title"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('admin::app.leads.index.kanban.quick-view.assigned-to')
                            </label>

                            <select
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.user_id"
                            >
                                <option :value="null">
                                    @lang('admin::app.leads.index.kanban.quick-view.unassigned')
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

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('admin::app.leads.index.kanban.quick-view.lead-value')
                            </label>

                            <input
                                type="text"
                                maxlength="255"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.lead_value"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('admin::app.leads.index.kanban.quick-view.stage')
                            </label>

                            <select
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.lead_pipeline_stage_id"
                            >
                                <option
                                    v-for="stage in stages"
                                    :key="stage.id"
                                    :value="stage.id"
                                >
                                    @{{ stage.name }}
                                </option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('admin::app.leads.index.kanban.quick-view.expected-close-date')
                            </label>

                            <input
                                type="date"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.expected_close_date"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-800 dark:text-white">
                                @lang('admin::app.leads.index.kanban.quick-view.description')
                            </label>

                            <textarea
                                rows="3"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                v-model="quickView.form.description"
                            ></textarea>
                        </div>

                        <div class="flex h-[200px] max-h-[200px] w-full min-w-0 flex-col overflow-hidden rounded-md border border-gray-200 p-3 dark:border-gray-800">
                            <div class="mb-2 flex shrink-0 items-center justify-between">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    @lang('admin::app.leads.index.kanban.quick-view.notes')
                                </p>

                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        @{{ quickView.notes.length }}
                                    </span>

                                    <a
                                        class="text-xs font-medium text-brandColor hover:underline"
                                        :href="'{{ route('admin.leads.view', 'replaceId') }}?tab=note'.replace('replaceId', quickView.lead.id)"
                                    >
                                        @lang('admin::app.leads.index.kanban.quick-view.view-all-notes')
                                    </a>
                                </div>
                            </div>

                            <div class="min-h-0 w-full min-w-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-contain pr-1">
                                <p
                                    v-if="quickView.notesLoading"
                                    class="py-2 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    @lang('admin::app.leads.index.kanban.quick-view.loading-notes')
                                </p>

                                <p
                                    v-else-if="quickView.notesError"
                                    class="py-2 text-sm text-red-600 dark:text-red-400"
                                >
                                    @lang('admin::app.leads.index.kanban.quick-view.notes-load-failed')
                                </p>

                                <p
                                    v-else-if="! quickView.notes.length"
                                    class="py-2 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    @lang('admin::app.leads.index.kanban.quick-view.no-notes')
                                </p>

                                <div
                                    v-else
                                    class="divide-y divide-gray-200 dark:divide-gray-800"
                                >
                                    <article
                                        v-for="note in quickView.notes"
                                        :key="note.id"
                                        class="py-2 first:pt-0 last:pb-0"
                                    >
                                        <div
                                            class="activity-rich-content break-words text-sm text-gray-800 dark:text-gray-200"
                                            v-safe-html="note.comment || note.title || '—'"
                                        ></div>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            @{{ note.user?.name || '—' }} · @{{ formatActivitySchedule(note.created_at) }}
                                        </p>
                                    </article>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-dashed border-gray-300 p-3 dark:border-gray-700">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                @lang('admin::app.leads.index.kanban.quick-view.next-activity')
                            </p>

                            <template v-if="quickView.lead.next_activity">
                                <p class="mt-1 text-sm font-medium dark:text-white">
                                    @{{ quickView.lead.next_activity.title || quickView.lead.next_activity.type }}
                                </p>

                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                    v-if="quickView.lead.next_activity.schedule_from"
                                >
                                    @{{ formatActivitySchedule(quickView.lead.next_activity.schedule_from) }}
                                </p>
                            </template>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                v-else
                            >
                                @lang('admin::app.leads.index.kanban.quick-view.no-next-activity')
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <button
                                type="button"
                                class="primary-button"
                                :disabled="quickView.saving"
                                @click="saveQuickView"
                            >
                                @lang('admin::app.leads.index.kanban.quick-view.save-btn')
                            </button>

                            <a
                                class="secondary-button"
                                :href="'{{ route('admin.leads.view', 'replaceId') }}'.replace('replaceId', quickView.lead.id)"
                            >
                                @lang('admin::app.leads.index.kanban.quick-view.open-full')
                            </a>
                        </div>
                    </div>
                </x-slot>
            </x-admin::drawer>
        </template>
    </script>

    <script type="module">
        app.component('v-leads-kanban', {
            template: '#v-leads-kanban-template',

            data() {
                return {
                    available: {
                        columns: @json($columns),
                    },

                    applied: {
                        filters: {
                            columns: [],
                        }
                    },

                    finalized: {
                        lead: null,
                        stage: null,
                        updating: false,
                    },

                    stages: @json($pipeline->stages->toArray()),

                    assignableUsers: @json($assignableUsers ?? []),

                    workspaceTimezone: @json(config('app.timezone')),

                    selectedUserFilter: null,

                    stageLeads: {},

                    isLoading: true,

                    isLoadingMore: false,

                    quickView: {
                        lead: null,
                        saving: false,
                        notes: [],
                        notesLoading: false,
                        notesError: false,
                        form: {
                            title: '',
                            user_id: null,
                            lead_value: null,
                            lead_pipeline_stage_id: null,
                            expected_close_date: '',
                            description: '',
                        },
                    },

                    cardFields: {
                        contactPerson: true,
                        rottenDays: true,
                        assignedUser: true,
                        leadValue: true,
                        source: true,
                        type: true,
                        tags: true,
                        nextActivity: true,
                    },

                    tagTextColor: {
                        '#FEE2E2': '#DC2626',
                        '#FFEDD5': '#EA580C',
                        '#FEF3C7': '#D97706',
                        '#FEF9C3': '#CA8A04',
                        '#ECFCCB': '#65A30D',
                        '#DCFCE7': '#16A34A',
                    },
                };
            },

            computed: {
                /**
                 * Computes the total amount of leads across all stages.
                 *
                 * @return {number} The total amount of leads.
                 */
                totalStagesAmount() {
                    let totalAmount = 0;

                    for (let [key, stage] of Object.entries(this.stageLeads)) {
                        totalAmount += parseFloat(stage.lead_value);
                    }

                    return totalAmount;
                }
            },

            mounted () {
                this.boot();

                this.$emitter.on('reload-datagrids', () => {
                    this.get()
                        .then(response => {
                            for (let [sortOrder, data] of Object.entries(response.data)) {
                                this.stageLeads[sortOrder] = data;
                            }
                        });
                });
            },

            methods: {
                /**
                 * Initialization: This function checks for any previously saved filters in local storage and applies them as needed.
                 *
                 * @returns {void}
                 */
                boot() {
                    this.cardFields = this.getCardFields();

                    let kanbans = this.getKanbans();

                    if (kanbans?.length) {
                        const currentKanban = kanbans.find(({ src }) => src === this.src);

                        if (currentKanban) {
                            this.applied.filters = currentKanban.applied.filters;

                            this.get()
                                .then(response => {
                                    for (let [sortOrder, data] of Object.entries(response.data)) {
                                        this.stageLeads[sortOrder] = data;
                                    }
                                });

                            return;
                        }
                    }

                    this.get()
                        .then(response => {
                            for (let [sortOrder, data] of Object.entries(response.data)) {
                                this.stageLeads[sortOrder] = data;
                            }
                        });
                },

                /**
                 * Fetches the leads based on the applied filters.
                 *
                 * @param {object} requestedParams - The requested parameters.
                 * @returns {Promise} The promise object representing the request.
                 */
                get(requestedParams = {}) {
                    let params = {
                        search: '',
                        searchFields: '',
                        pipeline_id: "{{ request('pipeline_id') }}",
                        limit: 10,
                    };

                    this.applied.filters.columns.forEach((column) => {
                        if (column.index === 'all') {
                            if (! column.value.length) {
                                return;
                            }

                            params['search'] += `title:${column.value.join(',')};`;
                            params['searchFields'] += `title:like;`;

                            return;
                        }

                        /**
                         * Assignee tabs use a dedicated query param so unassigned (null user_id)
                         * can be filtered without relying on RequestCriteria equality.
                         */
                        if (column.index === 'user_id') {
                            return;
                        }

                        /**
                         * Date range filters are sent as a dedicated parameter, not through the search
                         * string, so the server can resolve them the same way the datagrid does. A quick
                         * filter, such as `last_month`, is applied as a plain string, whereas a custom
                         * range is applied as a `[[from, to]]` pair.
                         */
                        if (column.type === 'date' || column.type === 'datetime') {
                            if (column.value.length) {
                                params[column.index] = column.value;
                            }

                            return;
                        }

                        /**
                         * If the column is a searchable dropdown, then we need to append the column value
                         * with the column label. Otherwise, we can directly append the column value.
                         */
                        params['search'] += column.filterable_type === 'searchable_dropdown'
                            ? `${column.index}:${column.value.map(option => option.value).join(',')};`
                            : `${column.index}:${column.value.join(',')};`;

                        params['searchFields'] += `${column.index}:${column.search_field};`;
                    });

                    if (this.selectedUserFilter !== null) {
                        params.assigned_user_id = this.selectedUserFilter;
                    }

                    return this.$axios
                        .get("{{ route('admin.leads.get') }}", {
                            params: {
                                ...params,

                                ...requestedParams,
                            }
                        })
                        .then(response => {
                            this.isLoading = false;

                            this.updateKanbans();

                            return response;
                        })
                        .catch(error => {
                            console.log(error)
                        });
                },

                /**
                 * Filters the leads based on the applied filters.
                 *
                 * @param {object} filters - The filters to be applied.
                 * @returns {void}
                 */
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

                    this.get()
                        .then(response => {
                            for (let [sortOrder, data] of Object.entries(response.data)) {
                                this.stageLeads[sortOrder] = data;
                            }
                        });
                },

                /**
                 * Searches the leads based on the applied filters.
                 *
                 * @param {object} filters - The filters to be applied.
                 * @returns {void}
                 */
                search(filters) {
                    this.applied.filters.columns = [
                        ...(this.applied.filters.columns.filter((column) => column.index !== 'all')),
                        ...filters.columns,
                    ];

                    this.get()
                        .then(response => {
                            for (let [sortOrder, data] of Object.entries(response.data)) {
                                this.stageLeads[sortOrder] = data;
                            }
                        });
                },

                /**
                 * Filter kanban leads by assignee user tab.
                 *
                 * @param {number|null} userId
                 * @returns {void}
                 */
                filterByUser(userId) {
                    this.selectedUserFilter = userId;

                    this.applied.filters.columns = this.applied.filters.columns.filter(
                        (column) => column.index !== 'user_id'
                    );

                    this.isLoading = true;

                    this.get()
                        .then(response => {
                            for (let [sortOrder, data] of Object.entries(response.data)) {
                                this.stageLeads[sortOrder] = data;
                            }
                        });
                },

                /**
                 * Open the lead quick-view drawer.
                 *
                 * @param {object} lead
                 * @returns {void}
                 */
                openQuickView(lead) {
                    this.quickView.lead = lead;

                    let expectedCloseDate = '';

                    if (lead.expected_close_date) {
                        const parsed = new Date(lead.expected_close_date);

                        if (! Number.isNaN(parsed.getTime())) {
                            expectedCloseDate = parsed.toISOString().slice(0, 10);
                        }
                    }

                    this.quickView.form = {
                        title: lead.title ?? '',
                        user_id: lead.user_id ?? null,
                        lead_value: lead.lead_value ?? null,
                        lead_pipeline_stage_id: lead.lead_pipeline_stage_id ?? lead.stage?.id ?? null,
                        expected_close_date: expectedCloseDate,
                        description: lead.description ?? '',
                    };

                    this.$refs.leadQuickViewDrawer.open();

                    this.loadQuickViewNotes(lead.id);
                },

                /**
                 * Load the latest notes for the lead shown in the drawer.
                 *
                 * @param {number} leadId
                 * @returns {void}
                 */
                loadQuickViewNotes(leadId) {
                    this.quickView.notes = [];
                    this.quickView.notesLoading = true;
                    this.quickView.notesError = false;

                    this.$axios.get(
                        "{{ route('admin.leads.activities.index', 'replaceId') }}".replace('replaceId', leadId),
                        {
                            params: {
                                type: 'note',
                                per_page: 50,
                            },
                        }
                    )
                        .then(response => {
                            if (this.quickView.lead?.id === leadId) {
                                this.quickView.notes = response.data.data ?? [];
                            }
                        })
                        .catch(() => {
                            if (this.quickView.lead?.id === leadId) {
                                this.quickView.notesError = true;
                            }
                        })
                        .finally(() => {
                            if (this.quickView.lead?.id === leadId) {
                                this.quickView.notesLoading = false;
                            }
                        });
                },

                /**
                 * Persist quick-view edits and refresh the board.
                 *
                 * @returns {void}
                 */
                saveQuickView() {
                    if (! this.quickView.lead) {
                        return;
                    }

                    this.quickView.saving = true;

                    this.$axios.put(
                        "{{ route('admin.leads.quick_update', ':id') }}".replace(':id', this.quickView.lead.id),
                        {
                            ...this.quickView.form,
                            user_id: this.quickView.form.user_id || null,
                        }
                    )
                        .then(response => {
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                            this.$refs.leadQuickViewDrawer.close();

                            this.get()
                                .then(boardResponse => {
                                    for (let [sortOrder, data] of Object.entries(boardResponse.data)) {
                                        this.stageLeads[sortOrder] = data;
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

                /**
                 * Format next-activity schedule for card/drawer display.
                 *
                 * @param {string} value
                 * @returns {string}
                 */
                formatActivitySchedule(value) {
                    if (! value) {
                        return '';
                    }

                    const date = new Date(value);

                    if (Number.isNaN(date.getTime())) {
                        return value;
                    }

                    return this.$admin.formatDate(value, 'MMM d, h:mm A', this.workspaceTimezone);
                },

                /**
                 * Appends the leads to the stage.
                 *
                 * @param {object} params - The parameters to be appended.
                 * @returns {Promise}
                 */
                append(params) {
                    return this.get(params)
                        .then(response => {
                            for (let [sortOrder, data] of Object.entries(response.data)) {
                                if (! this.stageLeads[sortOrder]) {
                                    this.stageLeads[sortOrder] = data;
                                } else {
                                    this.stageLeads[sortOrder].leads.data = this.stageLeads[sortOrder].leads.data.concat(data.leads.data);

                                    this.stageLeads[sortOrder].leads.meta = data.leads.meta;
                                }
                            }
                        });
                },

                /**
                 * Updates the stage with the latest lead data.
                 *
                 * @param {object} stage - The stage object.
                 * @param {object} event - The event object.
                 * @returns {void}
                 */
                handleUpdate(stage, event) {
                    if (event.moved) {
                        return;
                    }

                    if (
                        (stage.code === "won" || stage.code === "lost")
                        && event.added
                        && event.added.element
                    ) {
                        this.finalized.lead = event.added.element;

                        this.finalized.stage = stage;

                        this.toggleStageUpdateModal();

                        return;
                    }

                    if (event.removed) {
                        return;
                    }

                    this.updateStage("{{ route('admin.leads.stage.update', '__LEAD_ID__') }}".replace('__LEAD_ID__', event.added.element.id), {
                        'lead_pipeline_stage_id': stage.id
                    })
                        .then(response => {
                            this.get()
                                .then(response => {
                                    for (let [sortOrder, data] of Object.entries(response.data)) {
                                        this.stageLeads[sortOrder] = data;
                                    }
                                });

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch(error => {
                            this.get()
                                .then(response => {
                                    for (let [sortOrder, data] of Object.entries(response.data)) {
                                        this.stageLeads[sortOrder] = data;
                                    }
                                });

                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                        });
                },

                /**
                 * Updates the stage with the latest lead data.
                 *
                 * @param {string} url - The URL to update the stage.
                 * @param {object} params - The parameters to be updated.
                 *
                 * @returns {Promise} The promise object representing the request.
                 */
                updateStage(url, params) {
                    return this.$axios.put(url, params);
                },

                handleFormSubmit(params) {
                    this.finalized.updating = true;

                    this.updateStage("{{ route('admin.leads.stage.update', '__LEAD_ID__') }}".replace('__LEAD_ID__', this.finalized.lead.id), {
                        ...params,
                        lead_pipeline_stage_id: this.finalized.stage.id,
                    })
                        .then(response => {
                            this.toggleStageUpdateModal();

                            this.resetFinalized();

                            this.get()
                                .then(response => {
                                    for (let [sortOrder, data] of Object.entries(response.data)) {
                                        this.stageLeads[sortOrder] = data;
                                    }
                                });

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                        }).finally(() => {
                            this.finalized.updating = false;
                        });
                },

                /**
                 * Resets the finalized lead and stage data.
                 *
                 * @returns {void}
                 */
                resetFinalized() {
                    this.finalized = {
                        lead: null,
                        stage: null,
                        updating: false,
                    };
                },

                /**
                 * Handles the close event of the modal.
                 *
                 * @returns {void}
                 */
                handleCloseModal(state) {
                    if (state.isActive) {
                        return;
                    }

                    this.resetFinalized();

                    this.get()
                        .then(response => {
                            for (let [sortOrder, data] of Object.entries(response.data)) {
                                this.stageLeads[sortOrder] = data;
                            }
                        });
                },

                /**
                 * Toggles the stage update modal.
                 *
                 * @returns {void}
                 */
                toggleStageUpdateModal() {
                    this.$refs.stageUpdateModal.toggle();
                },

                /**
                 * Handles the scroll event on the stage leads.
                 *
                 * @param {object} stage - The stage object.
                 * @param {object} event - The scroll event.
                 * @returns {void}
                 */
                handleScroll(stage, event) {
                    const bottom = event.target.scrollHeight - event.target.scrollTop - event.target.clientHeight <= 5;

                    if (! bottom) {
                        return;
                    }

                    if (this.isLoadingMore) {
                        return;
                    }

                    if (this.stageLeads[stage.sort_order].leads.meta.current_page == this.stageLeads[stage.sort_order].leads.meta.last_page) {
                        return;
                    }

                    this.isLoadingMore = true;

                    this.append({
                        pipeline_stage_id: stage.id,
                        pipeline_id: stage.lead_pipeline_id,
                        page: this.stageLeads[stage.sort_order].leads.meta.current_page + 1,
                        limit: 10,
                    }).finally(() => {
                        this.isLoadingMore = false;
                    });
                },

                //=======================================================================================
                // Support for previous applied values in kanban's. All code is based on local storage.
                //=======================================================================================

                /**
                 * Updates the kanban's stored in local storage with the latest data.
                 *
                 * @returns {void}
                 */
                updateKanbans() {
                    let kanbans = this.getKanbans();

                    if (kanbans?.length) {
                        const currentKanban = kanbans.find(({ src }) => src === this.src);

                        if (currentKanban) {
                            kanbans = kanbans.map(kanban => {
                                if (kanban.src === this.src) {
                                    return {
                                        ...kanban,
                                        requestCount: ++kanban.requestCount,
                                        available: this.available,
                                        applied: this.applied,
                                    };
                                }

                                return kanban;
                            });
                        } else {
                            kanbans.push(this.getKanbanInitialProperties());
                        }
                    } else {
                        kanbans = [this.getKanbanInitialProperties()];
                    }

                    this.setKanbans(kanbans);
                },

                /**
                 * Returns the initial properties for a kanban.
                 *
                 * @returns {object} Initial properties for a kanban.
                 */
                getKanbanInitialProperties() {
                    return {
                        src: this.src,
                        requestCount: 0,
                        available: this.available,
                        applied: this.applied,
                    };
                },

                /**
                 * Returns the storage key for kanban's in local storage.
                 *
                 * @returns {string} Storage key for kanban's.
                 */
                getKanbansStorageKey() {
                    return 'kanbans';
                },

                /**
                 * Retrieves the kanban's stored in local storage.
                 *
                 * @returns {Array} Kanban's stored in local storage.
                 */
                getKanbans() {
                    let kanbans = localStorage.getItem(
                        this.getKanbansStorageKey()
                    );

                    return JSON.parse(kanbans) ?? [];
                },

                /**
                 * Sets the kanban's in local storage.
                 *
                 * @param {Array} kanbans - Kanban's to be stored in local storage.
                 * @returns {void}
                 */
                setKanbans(kanbans) {
                    localStorage.setItem(
                        this.getKanbansStorageKey(),
                        JSON.stringify(kanbans)
                    );
                },

                //=======================================================================================
                // Support for the customizable lead card fields. All code is based on local storage.
                //=======================================================================================

                /**
                 * Returns the storage key for the lead card field's visibility in local storage.
                 *
                 * @returns {string} Storage key for the lead card field's visibility.
                 */
                getCardFieldsStorageKey() {
                    return 'lead_kanban_card_fields';
                },

                /**
                 * Retrieves the lead card field's visibility stored in local storage, merged with the defaults.
                 *
                 * @returns {object} Lead card field's visibility.
                 */
                getCardFields() {
                    let storedCardFields = JSON.parse(
                        localStorage.getItem(this.getCardFieldsStorageKey())
                    );

                    return {
                        ...this.cardFields,
                        ...(storedCardFields ?? {}),
                    };
                },

                /**
                 * Updates the lead card field's visibility and stores it in local storage.
                 *
                 * @param {object} fields - Lead card field's visibility.
                 * @returns {void}
                 */
                updateCardFields(fields) {
                    this.cardFields = fields;

                    localStorage.setItem(
                        this.getCardFieldsStorageKey(),
                        JSON.stringify(fields)
                    );
                },
            }
        });
    </script>
@endPushOnce
