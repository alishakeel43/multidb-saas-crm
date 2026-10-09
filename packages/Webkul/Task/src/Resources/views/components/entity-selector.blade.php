@props([
    'entityType' => '',
    'entityId' => '',
    'entityTitle' => '',
    'entityTypeLabel' => null,
    'entityIdLabel' => null,
])

<v-task-entity-selector
    initial-entity-type="{{ (string) $entityType }}"
    initial-entity-id="{{ (string) $entityId }}"
    initial-entity-title="{{ (string) $entityTitle }}"
    search-url="{{ route('admin.tasks.search_entities') }}"
    entity-type-label="{{ $entityTypeLabel ?? trans('admin::app.tasks.fields.entity_type') }}"
    entity-id-label="{{ $entityIdLabel ?? trans('admin::app.tasks.fields.entity_id') }}"
></v-task-entity-selector>

@pushOnce('styles')
    <style>
        .task-entity-dropdown-scroll {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }
        .task-entity-dropdown-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .task-entity-dropdown-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .task-entity-dropdown-scroll::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 9999px;
        }
        .task-entity-dropdown-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }
        .dark .task-entity-dropdown-scroll {
            scrollbar-color: #475569 transparent;
        }
        .dark .task-entity-dropdown-scroll::-webkit-scrollbar-thumb {
            background-color: #475569;
        }
        .dark .task-entity-dropdown-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #64748b;
        }
    </style>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-task-entity-selector-template">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Related Entity Type -->
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @{{ entityTypeLabel }}
                </x-admin::form.control-group.label>

                <select
                    name="entity_type"
                    v-model="entityType"
                    @change="onEntityTypeChange"
                    class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                >
                    <option value="">@lang('admin::app.tasks.fields.select-entity')</option>
                    <option value="leads">Lead / Deal</option>
                    <option value="persons">Person / Contact</option>
                    <option value="organizations">Organization</option>
                    <option value="quotes">Quote</option>
                </select>

                <x-admin::form.control-group.error control-name="entity_type" />
            </x-admin::form.control-group>

            <!-- Related Record Dropdown with Search -->
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @{{ entityIdLabel }}
                </x-admin::form.control-group.label>

                <!-- Hidden form input for submission and validation -->
                <input
                    type="hidden"
                    name="entity_id"
                    :value="selectedItem ? selectedItem.id : ''"
                />

                <div class="relative" ref="dropdownRoot">
                    <!-- Dropdown Trigger Box -->
                    <div
                        @click="toggleDropdown"
                        :class="[
                            ! entityType
                                ? 'cursor-not-allowed bg-gray-50 opacity-60 dark:bg-gray-800/60 text-gray-400'
                                : 'cursor-pointer bg-white hover:border-gray-400 focus:border-gray-400 dark:bg-gray-900',
                            isOpen
                                ? 'border-brandColor ring-1 ring-brandColor'
                                : 'border-gray-300 dark:border-gray-800'
                        ]"
                        class="relative flex min-h-[39px] w-full items-center justify-between rounded border px-2.5 py-2 text-sm font-normal transition-all select-none dark:text-gray-300"
                    >
                        <!-- Selected Label or Placeholder -->
                        <div class="flex items-center gap-2 overflow-hidden min-w-0 flex-1 ltr:mr-2 rtl:ml-2">
                            <template v-if="selectedItem && selectedItem.id">
                                <span class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-xs font-semibold text-brandColor dark:bg-gray-800 dark:text-blue-400 shrink-0">
                                    #@{{ selectedItem.id }}
                                </span>
                                <span class="truncate font-medium text-gray-800 dark:text-white min-w-0" :title="selectedItem.name">
                                    @{{ selectedItem.name }}
                                </span>
                            </template>
                            <template v-else>
                                <span class="truncate text-gray-400 dark:text-gray-500 min-w-0">
                                    @{{ entityType ? getTriggerPlaceholder() : '@lang('admin::app.tasks.fields.select-entity-first')' }}
                                </span>
                            </template>
                        </div>

                        <!-- Action Icons (Clear + Arrow) -->
                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                type="button"
                                v-if="selectedItem && selectedItem.id"
                                @click.stop="clearSelection"
                                class="flex h-5 w-5 items-center justify-center rounded-full text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                title="Clear selection"
                            >
                                <i class="icon-cross-large text-xs"></i>
                            </button>

                            <i
                                class="text-xl text-gray-400 transition-transform duration-200"
                                :class="isOpen ? 'icon-up-arrow' : 'icon-down-arrow'"
                            ></i>
                        </div>
                    </div>

                    <!-- Dropdown Popover Panel -->
                    <div
                        v-if="isOpen"
                        :class="placement === 'top' ? 'bottom-full mb-1' : 'top-full mt-1'"
                        class="absolute left-0 z-[100] w-full rounded-lg border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-800 dark:bg-gray-900 transition-all"
                        style="min-width: 100%; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);"
                    >
                        <!-- Live Search Input (Sticky/Fixed at Top) -->
                        <div class="relative mb-2 flex items-center">
                            <div
                                class="icon-search pointer-events-none absolute text-base text-gray-400 flex items-center justify-center"
                                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px;"
                            ></div>

                            <input
                                type="text"
                                ref="searchInput"
                                v-model="searchTerm"
                                @input="onSearchInput"
                                @keydown.down.prevent="navigateDown"
                                @keydown.up.prevent="navigateUp"
                                @keydown.enter.prevent="selectHighlighted"
                                @keydown.esc.prevent="closeDropdown"
                                class="w-full rounded border border-gray-300 py-1.5 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-500"
                                style="padding-left: 32px; padding-right: 32px;"
                                :placeholder="getSearchBoxPlaceholder()"
                                autocomplete="off"
                                spellcheck="false"
                            />

                            <!-- Spinner or Clear input button -->
                            <div
                                class="absolute flex items-center justify-center"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);"
                            >
                                <x-admin::spinner v-if="isSearching" class="!h-4 !w-4 text-brandColor" />
                                <button
                                    type="button"
                                    v-else-if="searchTerm"
                                    @click.stop="clearSearchTerm"
                                    class="flex h-4 w-4 items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                    title="Clear"
                                >
                                    <i class="icon-cross-large text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Results List (Scrollable & Responsive) -->
                        <div
                            ref="resultsContainer"
                            class="task-entity-dropdown-scroll overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800 rounded"
                            :style="{ maxHeight: dynamicMaxHeight, overflowY: 'auto' }"
                            style="overscroll-behavior: contain; -webkit-overflow-scrolling: touch;"
                        >
                            <!-- Searching Indicator -->
                            <div
                                v-if="isSearching && results.length === 0"
                                class="flex items-center justify-center py-6 text-sm text-gray-500 dark:text-gray-400"
                            >
                                <x-admin::spinner class="!h-5 !w-5 text-brandColor ltr:mr-2 rtl:ml-2" />
                                <span>@lang('admin::app.tasks.fields.search-record')</span>
                            </div>

                            <!-- Empty Results Notice -->
                            <div
                                v-else-if="! isSearching && results.length === 0"
                                class="py-5 text-center text-xs text-gray-500 dark:text-gray-400"
                            >
                                <div class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    @lang('admin::app.tasks.fields.no-records')
                                </div>
                                <span v-if="searchTerm">
                                    Try a different search query
                                </span>
                            </div>

                            <!-- Items List -->
                            <ul v-else class="space-y-0.5 p-0.5">
                                <li
                                    v-for="(item, index) in results"
                                    :key="item.id"
                                    @click="selectItem(item)"
                                    @mouseenter="highlightedIndex = index"
                                    :class="[
                                        isSelected(item)
                                            ? 'bg-blue-50 text-brandColor dark:bg-gray-800 dark:text-blue-400 font-semibold'
                                            : (highlightedIndex === index
                                                ? 'bg-gray-100 text-gray-900 dark:bg-gray-800/80 dark:text-white'
                                                : 'hover:bg-gray-50 text-gray-800 dark:text-gray-200 dark:hover:bg-gray-800/60')
                                    ]"
                                    class="flex cursor-pointer items-center justify-between rounded px-3 py-2 text-sm transition-colors select-none"
                                >
                                    <div class="flex flex-col min-w-0 pr-2 overflow-hidden">
                                        <span class="truncate font-medium leading-5" :title="item.name">
                                            @{{ item.name }}
                                        </span>
                                        <span
                                            v-if="item.sub_title"
                                            class="truncate text-xs text-gray-400 dark:text-gray-500 mt-0.5"
                                            :title="item.sub_title"
                                        >
                                            @{{ item.sub_title }}
                                        </span>
                                    </div>

                                    <span class="text-xs font-mono text-gray-400 dark:text-gray-500 shrink-0 ml-2 rounded bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 border border-gray-200/50 dark:border-gray-700/50">
                                        #@{{ item.id }}
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <!-- Results Footer / Record Count -->
                        <div
                            v-if="results.length > 0"
                            class="mt-1.5 pt-1.5 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between px-2 text-[11px] text-gray-400 dark:text-gray-500 select-none"
                        >
                            <span>@{{ results.length }} @{{ results.length === 1 ? 'record' : 'records' }}</span>
                            <span class="hidden sm:inline">Use ↑ ↓ to navigate • Enter to select</span>
                        </div>
                    </div>
                </div>

                <x-admin::form.control-group.error control-name="entity_id" />
            </x-admin::form.control-group>
        </div>
    </script>

    <script type="module">
        app.component('v-task-entity-selector', {
            template: '#v-task-entity-selector-template',

            props: {
                initialEntityType: {
                    type: String,
                    default: '',
                },

                initialEntityId: {
                    type: [String, Number],
                    default: '',
                },

                initialEntityTitle: {
                    type: String,
                    default: '',
                },

                searchUrl: {
                    type: String,
                    required: true,
                },

                entityTypeLabel: {
                    type: String,
                    default: '',
                },

                entityIdLabel: {
                    type: String,
                    default: '',
                },
            },

            data() {
                const entityType = this.initialEntityType || '';
                const entityId = this.initialEntityId ? String(this.initialEntityId) : '';
                const entityTitle = this.initialEntityTitle || '';

                return {
                    entityType: entityType,
                    selectedItem: entityId ? { id: entityId, name: entityTitle || ('#' + entityId) } : null,
                    isOpen: false,
                    searchTerm: '',
                    results: [],
                    isSearching: false,
                    debounceTimer: null,
                    cancelToken: null,
                    placement: 'bottom',
                    dynamicMaxHeight: '260px',
                    highlightedIndex: -1,
                };
            },

            watch: {
                initialEntityType(val) {
                    if (val) {
                        this.entityType = val;
                    }
                },

                initialEntityId(val) {
                    if (val) {
                        const idStr = String(val);
                        this.selectedItem = {
                            id: idStr,
                            name: this.initialEntityTitle || ('#' + idStr),
                        };
                    } else {
                        this.selectedItem = null;
                    }
                },
            },

            mounted() {
                document.addEventListener('click', this.handleOutsideClick);
                window.addEventListener('resize', this.updatePlacement);
            },

            beforeUnmount() {
                document.removeEventListener('click', this.handleOutsideClick);
                window.removeEventListener('resize', this.updatePlacement);

                if (this.debounceTimer) {
                    clearTimeout(this.debounceTimer);
                }
            },

            methods: {
                onEntityTypeChange() {
                    this.selectedItem = null;
                    this.searchTerm = '';
                    this.results = [];
                    this.highlightedIndex = -1;

                    if (this.isOpen) {
                        if (! this.entityType) {
                            this.isOpen = false;
                        } else {
                            this.fetchResults();
                        }
                    }
                },

                toggleDropdown() {
                    if (! this.entityType) {
                        return;
                    }

                    this.isOpen = ! this.isOpen;

                    if (this.isOpen) {
                        this.searchTerm = '';
                        this.highlightedIndex = -1;
                        this.updatePlacement();
                        this.fetchResults();

                        this.$nextTick(() => {
                            if (this.$refs.searchInput) {
                                this.$refs.searchInput.focus();
                            }
                        });
                    }
                },

                closeDropdown() {
                    this.isOpen = false;
                    this.highlightedIndex = -1;
                },

                clearSelection() {
                    this.selectedItem = null;
                    this.searchTerm = '';
                    this.highlightedIndex = -1;
                },

                clearSearchTerm() {
                    this.searchTerm = '';
                    this.highlightedIndex = -1;
                    this.fetchResults();
                    this.$nextTick(() => {
                        if (this.$refs.searchInput) {
                            this.$refs.searchInput.focus();
                        }
                    });
                },

                selectItem(item) {
                    this.selectedItem = item;
                    this.isOpen = false;
                    this.searchTerm = '';
                    this.highlightedIndex = -1;
                },

                isSelected(item) {
                    return this.selectedItem && String(this.selectedItem.id) === String(item.id);
                },

                onSearchInput() {
                    if (this.debounceTimer) {
                        clearTimeout(this.debounceTimer);
                    }

                    this.debounceTimer = setTimeout(() => {
                        this.highlightedIndex = -1;
                        this.fetchResults();
                    }, 300);
                },

                fetchResults() {
                    if (! this.entityType) {
                        this.results = [];
                        return;
                    }

                    this.isSearching = true;

                    if (this.cancelToken) {
                        this.cancelToken.cancel();
                    }

                    if (this.$axios?.CancelToken) {
                        this.cancelToken = this.$axios.CancelToken.source();
                    }

                    const params = {
                        entity_type: this.entityType,
                        query: this.searchTerm ? this.searchTerm.trim() : '',
                    };

                    const config = { params };

                    if (this.cancelToken) {
                        config.cancelToken = this.cancelToken.token;
                    }

                    this.$axios.get(this.searchUrl, config)
                        .then(response => {
                            const data = response.data?.data ?? response.data;
                            this.results = Array.isArray(data) ? data : [];
                            this.updatePlacement();
                        })
                        .catch(error => {
                            if (this.$axios?.isCancel && this.$axios.isCancel(error)) {
                                return;
                            }
                            console.error('Error fetching related records:', error);
                            this.results = [];
                        })
                        .finally(() => {
                            this.isSearching = false;
                        });
                },

                updatePlacement() {
                    if (! this.isOpen || ! this.$refs.dropdownRoot) {
                        return;
                    }

                    const rect = this.$refs.dropdownRoot.getBoundingClientRect();
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const spaceAbove = rect.top;

                    // If space below is less than 280px and space above is greater, open upwards
                    if (spaceBelow < 280 && spaceAbove > spaceBelow) {
                        this.placement = 'top';
                        const available = spaceAbove - 80;
                        this.dynamicMaxHeight = `${Math.max(160, Math.min(260, available))}px`;
                    } else {
                        this.placement = 'bottom';
                        const available = spaceBelow - 80;
                        this.dynamicMaxHeight = `${Math.max(160, Math.min(260, available))}px`;
                    }
                },

                navigateDown() {
                    if (! this.results.length) return;
                    if (this.highlightedIndex < this.results.length - 1) {
                        this.highlightedIndex++;
                    } else {
                        this.highlightedIndex = 0;
                    }
                    this.scrollHighlightedIntoView();
                },

                navigateUp() {
                    if (! this.results.length) return;
                    if (this.highlightedIndex > 0) {
                        this.highlightedIndex--;
                    } else {
                        this.highlightedIndex = this.results.length - 1;
                    }
                    this.scrollHighlightedIntoView();
                },

                selectHighlighted() {
                    if (this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
                        this.selectItem(this.results[this.highlightedIndex]);
                    } else if (this.results.length === 1) {
                        this.selectItem(this.results[0]);
                    }
                },

                scrollHighlightedIntoView() {
                    this.$nextTick(() => {
                        const container = this.$refs.resultsContainer;
                        if (! container) return;
                        const items = container.querySelectorAll('li');
                        const target = items[this.highlightedIndex];
                        if (target && typeof target.scrollIntoView === 'function') {
                            target.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        }
                    });
                },

                handleOutsideClick(event) {
                    if (! this.isOpen) {
                        return;
                    }

                    const root = this.$refs.dropdownRoot;
                    if (root && ! root.contains(event.target)) {
                        this.isOpen = false;
                        this.highlightedIndex = -1;
                    }
                },

                getTriggerPlaceholder() {
                    switch (this.entityType) {
                        case 'leads': return 'Search Lead / Deal...';
                        case 'persons': return 'Search Person / Contact...';
                        case 'organizations': return 'Search Organization...';
                        case 'quotes': return 'Search Quote...';
                        default: return 'Search record...';
                    }
                },

                getSearchBoxPlaceholder() {
                    switch (this.entityType) {
                        case 'leads': return 'Type lead title or #ID...';
                        case 'persons': return 'Type contact name, email or #ID...';
                        case 'organizations': return 'Type organization name or #ID...';
                        case 'quotes': return 'Type quote subject or #ID...';
                        default: return 'Type to search...';
                    }
                },
            },
        });
    </script>
@endPushOnce
