
<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.email-template.edit.title')
    </x-slot>

    {!! view_render_event('admin.settings.email_template.edit.form.before') !!}

    <x-admin::form
        :action="route('admin.settings.email_templates.update', $emailTemplate->id)"
        method="PUT"
    >
        <div class="flex flex-col gap-4">
            <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    {!! view_render_event('admin.settings.email_template.edit.breadcrumbs.before') !!}

                    <!-- Breadcrumbs -->
                    <x-admin::breadcrumbs
                        name="settings.email_templates.edit"
                        :entity="$emailTemplate"
                    />

                    {!! view_render_event('admin.settings.email_template.edit.breadcrumbs.after') !!}

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.settings.email-template.edit.title')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <!-- Create button for person -->
                    <div class="flex items-center gap-x-2.5">
                        {!! view_render_event('admin.settings.email_template.edit.save_button.before') !!}

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('Save Email Template')
                        </button>

                        {!! view_render_event('admin.settings.email_template.edit.save_button.before') !!}
                    </div>
                </div>
            </div>

            <v-email-template></v-email-template>
        </div>
    </x-admin::form>

    {!! view_render_event('admin.settings.email_template.edit.form.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-email-template-template"
        >
            <div class="flex gap-2.5 max-xl:flex-wrap">
                <!-- Left sub-component -->
                <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                    <div class="box-shadow rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.settings.email-template.edit.email-template')
                            </p>
                        </div>

                        {!! view_render_event('admin.settings.email_template.edit.subject.before') !!}

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.email-template.edit.subject')
                            </x-admin::form.control-group.label>

                            <div class="flex">
                                <div class="relative flex-1">
                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="subject"
                                        id="subject"
                                        class="rounded-r-none"
                                        rules="required"
                                        :label="trans('admin::app.settings.email-template.edit.subject')"
                                        :placeholder="trans('admin::app.settings.email-template.edit.subject')"
                                        v-model="subject"
                                        autocomplete="off"
                                        @focusout="onSubjectFocusOut"
                                        @input="onSubjectInput"
                                        @keydown="onSubjectKeydown"
                                    />

                                    <ul
                                        v-if="subjectSuggestions.length"
                                        class="absolute left-0 right-0 z-20 mt-1 max-h-56 overflow-auto rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                    >
                                        <li
                                            v-for="(suggestion, index) in subjectSuggestions"
                                            :key="suggestion.insert + index"
                                            class="cursor-pointer px-3 py-2 dark:text-gray-200"
                                            :class="index === subjectSuggestionIndex ? 'bg-gray-100 dark:bg-gray-800' : ''"
                                            @mousedown.prevent="applySubjectSuggestion(suggestion)"
                                        >
                                            @{{ suggestion.label }}
                                        </li>
                                    </ul>
                                </div>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="placeholder"
                                    id="placeholder"
                                    class="!w-1/3 rounded-l-none"
                                    :label="trans('admin::app.settings.email-template.edit.subject-placeholder')"
                                    v-model="selectedPlaceholder"
                                    @change="insertPlaceholder"
                                >
                                    <optgroup
                                        v-for="entity in placeholders"
                                        :key="entity.text"
                                        :label="entity.text"
                                    >
                                        <option
                                            v-for="placeholder in entity.menu"
                                            :key="placeholder.value"
                                            :value="placeholder.value"
                                            :text="placeholder.text"
                                        ></option>
                                    </optgroup>
                                </x-admin::form.control-group.control>
                            </div>
                        </x-admin::form.control-group>

                        <x-admin::form.control-group.error control-name="subject"/>

                        {!! view_render_event('admin.settings.email_template.edit.subject.after') !!}

                        {!! view_render_event('admin.settings.email_template.edit.content.before') !!}

                        <!-- Event Name -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.email-template.edit.content')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                id="content"
                                name="content"
                                rules="required"
                                :value="old('content') ?? $emailTemplate->content"
                                :tinymce="true"
                                :placeholders="json_encode($placeholders)"
                                :label="trans('admin::app.settings.email-template.edit.content')"
                                :placeholder="trans('admin::app.settings.email-template.edit.content')"
                            />

                            <x-admin::form.control-group.error control-name="content" />
                        </x-admin::form.control-group>

                        {!! view_render_event('admin.settings.email_template.edit.content.after') !!}
                    </div>
                </div>

                <!-- Right sub-component -->
                <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
                    {!! view_render_event('admin.settings.email_template.edit.accordion.general.before') !!}

                    <x-admin::accordion>
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.settings.email-template.edit.general')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.email-template.edit.name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="name"
                                    id="name"
                                    :value="old('name') ?? $emailTemplate->name"
                                    rules="required"
                                    :label="trans('admin::app.settings.email-template.edit.name')"
                                    :placeholder="trans('admin::app.settings.email-template.edit.name')"
                                />
                                <x-admin::form.control-group.error control-name="name" />
                            </x-admin::form.control-group>
                        </x-slot>
                    </x-admin::accordion>

                    {!! view_render_event('admin.settings.email_template.edit.accordion.general.after') !!}
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-email-template', {
                template: '#v-email-template-template',

                data() {
                    return {
                        subject: @json(old('subject') ?? $emailTemplate->subject),

                        selectedPlaceholder: '',

                        cursorPosition: 0,

                        placeholders: @json($placeholders),

                        placeholderCatalog: @json($placeholderCatalog),

                        subjectSuggestions: [],

                        subjectSuggestionIndex: 0,

                        subjectTokenStart: -1,
                    };
                },

                methods: {
                    /**
                     * Save the cursor position when the input is focused.
                     *
                     * @param {Event} event
                     * @returns {void}
                     */
                    saveCursorPosition(event) {
                        this.cursorPosition = event.target.selectionStart;
                    },

                    onSubjectFocusOut(event) {
                        this.saveCursorPosition(event);

                        setTimeout(() => {
                            this.subjectSuggestions = [];
                        }, 150);
                    },

                    onSubjectInput(event) {
                        this.subject = event.target.value;
                        this.cursorPosition = event.target.selectionStart;
                        this.refreshSubjectSuggestions();
                    },

                    onSubjectKeydown(event) {
                        if (! this.subjectSuggestions.length) {
                            return;
                        }

                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            this.subjectSuggestionIndex = (this.subjectSuggestionIndex + 1) % this.subjectSuggestions.length;
                        } else if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            this.subjectSuggestionIndex = (this.subjectSuggestionIndex - 1 + this.subjectSuggestions.length) % this.subjectSuggestions.length;
                        } else if (event.key === 'Enter' || event.key === 'Tab') {
                            event.preventDefault();
                            this.applySubjectSuggestion(this.subjectSuggestions[this.subjectSuggestionIndex]);
                        } else if (event.key === 'Escape') {
                            this.subjectSuggestions = [];
                        }
                    },

                    refreshSubjectSuggestions() {
                        const cursor = this.cursorPosition ?? this.subject.length;
                        const beforeCursor = this.subject.substring(0, cursor);
                        const match = beforeCursor.match(/\{\%([^%]*)$/);

                        if (! match) {
                            this.subjectSuggestions = [];
                            this.subjectTokenStart = -1;

                            return;
                        }

                        this.subjectTokenStart = beforeCursor.lastIndexOf('{%');
                        this.subjectSuggestions = this.suggestPlaceholders(match[1] || '');
                        this.subjectSuggestionIndex = 0;
                    },

                    suggestPlaceholders(pattern) {
                        const normalized = String(pattern || '').toLowerCase().trimStart();
                        const results = [];

                        if (! normalized.includes('.')) {
                            this.placeholderCatalog.forEach((entity) => {
                                const type = entity.type;
                                const label = entity.label;

                                if (
                                    normalized !== ''
                                    && ! type.startsWith(normalized)
                                    && ! label.toLowerCase().startsWith(normalized)
                                ) {
                                    return;
                                }

                                results.push({
                                    label: `${label} (${type})`,
                                    insert: `{%${type}.`,
                                });
                            });

                            return results;
                        }

                        const [entityPart, attrPart = ''] = normalized.split('.');

                        this.placeholderCatalog.forEach((entity) => {
                            if (entity.type !== entityPart) {
                                return;
                            }

                            (entity.attributes || []).forEach((attribute) => {
                                const code = String(attribute.code || '').toLowerCase();
                                const name = String(attribute.label || '').toLowerCase();

                                if (
                                    attrPart !== ''
                                    && ! code.startsWith(attrPart)
                                    && ! name.startsWith(attrPart)
                                ) {
                                    return;
                                }

                                results.push({
                                    label: `${attribute.label} — ${entity.type}.${attribute.code}`,
                                    insert: attribute.value,
                                });
                            });
                        });

                        return results;
                    },

                    applySubjectSuggestion(suggestion) {
                        if (! suggestion || this.subjectTokenStart < 0) {
                            return;
                        }

                        const before = this.subject.substring(0, this.subjectTokenStart);
                        const after = this.subject.substring(this.cursorPosition);
                        this.subject = `${before}${suggestion.insert}${after}`;
                        this.cursorPosition = before.length + suggestion.insert.length;
                        this.subjectSuggestions = [];

                        this.$nextTick(() => {
                            const input = document.getElementById('subject');

                            if (input) {
                                input.focus();
                                input.setSelectionRange(this.cursorPosition, this.cursorPosition);
                            }
                        });
                    },

                    /**
                     * Insert the selected placeholder into the subject.
                     *
                     * @returns {void}
                     */
                    insertPlaceholder() {
                        const placeholder = this.selectedPlaceholder;

                        if (this.cursorPosition >= 0) {
                            const before = this.subject.substring(0, this.cursorPosition);

                            const after = this.subject.substring(this.cursorPosition);

                            this.subject = `${before}${placeholder}${after}`;

                            this.cursorPosition += placeholder.length;
                        } else if (placeholder) {
                            this.subject += placeholder;
                        }

                        this.selectedPlaceholder = '';
                        this.subjectSuggestions = [];
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
