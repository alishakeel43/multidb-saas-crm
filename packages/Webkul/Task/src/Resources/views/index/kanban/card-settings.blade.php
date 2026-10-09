{!! view_render_event('admin.tasks.index.kanban.card_settings.before') !!}

<v-tasks-kanban-card-settings
    :card-fields="cardFields"
    @update-card-fields="updateCardFields"
>
</v-tasks-kanban-card-settings>

{!! view_render_event('admin.tasks.index.kanban.card_settings.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-tasks-kanban-card-settings-template"
    >
        {!! view_render_event('admin.tasks.index.kanban.card_settings.dropdown.before') !!}

        <x-admin::dropdown ::close-on-click="false">
            <!-- Dropdown Toggler -->
            <x-slot:toggle>
                {!! view_render_event('admin.tasks.index.kanban.card_settings.dropdown.toggle_button.before') !!}

                <div
                    class="icon-setting cursor-pointer rounded-md border p-2 text-lg text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:text-gray-300 dark:hover:border-gray-400"
                    title="@lang('task::app.kanban.toolbar.card-settings.title')"
                ></div>

                {!! view_render_event('admin.tasks.index.kanban.card_settings.dropdown.toggle_button.after') !!}
            </x-slot>

            <!-- Dropdown Content -->
            <x-slot:content>
                {!! view_render_event('admin.tasks.index.kanban.card_settings.dropdown.content.before') !!}

                <div class="grid w-72 max-h-[350px] gap-3 overflow-y-auto p-4">
                    <p class="text-base font-semibold dark:text-white">
                        @lang('task::app.kanban.toolbar.card-settings.title')
                    </p>

                    <div
                        class="flex items-center gap-2.5"
                        v-for="field in fieldOptions"
                        :key="field.key"
                    >
                        <input
                            type="checkbox"
                            :id="'task-card-field-' + field.key"
                            :checked="fields[field.key]"
                            class="peer hidden"
                            @change="toggleField(field.key)"
                        />

                        <label
                            :for="'task-card-field-' + field.key"
                            class="icon-checkbox-outline peer-checked:icon-checkbox-select cursor-pointer rounded-md text-2xl text-gray-500 peer-checked:text-brandColor"
                        >
                        </label>

                        <label
                            :for="'task-card-field-' + field.key"
                            class="cursor-pointer text-sm text-gray-700 dark:text-gray-300 select-none"
                            v-text="field.label"
                        >
                        </label>
                    </div>
                </div>

                {!! view_render_event('admin.tasks.index.kanban.card_settings.dropdown.content.after') !!}
            </x-slot>
        </x-admin::dropdown>

        {!! view_render_event('admin.tasks.index.kanban.card_settings.dropdown.after') !!}
    </script>

    <script type="module">
        app.component('v-tasks-kanban-card-settings', {
            template: '#v-tasks-kanban-card-settings-template',

            props: ['cardFields'],

            emits: ['update-card-fields'],

            data() {
                return {
                    fields: { ...this.cardFields },

                    fieldOptions: [
                        { key: 'assignedUser', label: '@lang('task::app.fields.assigned_to', ['default' => 'Assigned User'])' },
                        { key: 'priority', label: '@lang('task::app.fields.priority', ['default' => 'Priority'])' },
                        { key: 'dueDate', label: '@lang('task::app.fields.due_date', ['default' => 'Due Date'])' },
                        { key: 'entity', label: '@lang('task::app.view.relationships', ['default' => 'Related Record'])' },
                        { key: 'description', label: '@lang('task::app.fields.description', ['default' => 'Description Snippet'])' },
                    ],
                };
            },

            watch: {
                cardFields: {
                    deep: true,
                    handler(newFields) {
                        this.fields = { ...newFields };
                    },
                },
            },

            methods: {
                toggleField(key) {
                    this.fields[key] = ! this.fields[key];

                    this.$emit('update-card-fields', { ...this.fields });
                },
            },
        });
    </script>
@endPushOnce
