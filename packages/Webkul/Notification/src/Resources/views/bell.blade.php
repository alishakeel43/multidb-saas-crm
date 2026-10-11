@auth('user')
    <v-crm-notifications>
        <button
            type="button"
            class="relative rounded-md p-1.5 text-2xl transition-all hover:bg-gray-100 dark:hover:bg-gray-950"
            aria-label="Notifications"
        >
            <svg class="h-5 w-5 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9" />
            </svg>
        </button>
    </v-crm-notifications>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-crm-notifications-template">
            <div class="relative">
                <button
                    type="button"
                    class="relative rounded-md p-1.5 transition-all hover:bg-gray-100 dark:hover:bg-gray-950"
                    @click="toggle"
                    aria-label="Notifications"
                >
                    <svg class="h-5 w-5 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9" />
                    </svg>

                    <span
                        v-if="unread > 0"
                        class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white"
                        v-text="unread > 99 ? '99+' : unread"
                    ></span>
                </button>

                <div
                    v-if="open"
                    class="absolute right-0 z-[10050] mt-2 w-80 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-700">
                        <p class="text-sm font-semibold dark:text-white">Notifications</p>
                        <button type="button" class="text-xs text-brandColor" @click="markAll">Mark all read</button>
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        <template v-if="items.length">
                            <a
                                v-for="item in items"
                                :key="item.id"
                                :href="item.link || '{{ route('admin.notifications.index') }}'"
                                class="block border-b border-gray-100 px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                                :class="{ 'opacity-60': item.read_at }"
                                @click="markOne(item)"
                            >
                                <p class="font-medium text-gray-900 dark:text-white" v-text="item.title"></p>
                                <p class="text-xs text-gray-500" v-text="item.body || item.created_at"></p>
                            </a>
                        </template>
                        <p v-else class="px-3 py-6 text-center text-xs text-gray-500">No notifications yet.</p>
                    </div>
                    <a
                        href="{{ route('admin.notifications.index') }}"
                        class="block border-t border-gray-200 px-3 py-2 text-center text-xs font-medium text-brandColor dark:border-gray-700"
                    >
                        View all
                    </a>
                </div>
            </div>
        </script>

        <script>
            app.component('v-crm-notifications', {
                template: '#v-crm-notifications-template',
                data() {
                    return { open: false, unread: 0, items: [] };
                },
                mounted() {
                    this.refresh();
                    this._timer = setInterval(this.refresh, 60000);
                    document.addEventListener('click', this.onDocClick);
                },
                beforeUnmount() {
                    clearInterval(this._timer);
                    document.removeEventListener('click', this.onDocClick);
                },
                methods: {
                    onDocClick(e) {
                        if (! this.$el.contains(e.target)) this.open = false;
                    },
                    toggle() {
                        this.open = ! this.open;
                        if (this.open) this.refresh();
                    },
                    refresh() {
                        this.$axios.get("{{ route('admin.notifications.feed') }}")
                            .then(({ data }) => {
                                this.unread = data.unread || 0;
                                this.items = data.items || [];
                            })
                            .catch(() => {});
                    },
                    markOne(item) {
                        if (item.read_at) return;
                        this.$axios.post(`{{ url(config('app.admin_path').'/notifications') }}/${item.id}/read`)
                            .then(() => this.refresh())
                            .catch(() => {});
                    },
                    markAll() {
                        this.$axios.post("{{ route('admin.notifications.read-all') }}")
                            .then(() => this.refresh())
                            .catch(() => {});
                    },
                },
            });
        </script>
    @endPushOnce
@endauth
