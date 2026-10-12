@php
    $person = $person ?? null;
@endphp

@if ($person)
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                Call history
            </h3>

            <a
                href="{{ route('admin.telephony.call_logs.index', ['filters' => ['contact_id' => ['eq' => $person->id]]]) }}"
                class="text-xs font-medium text-brandColor hover:underline"
            >
                View all
            </a>
        </div>

        <div
            x-data="{
                loading: true,
                rows: [],
                async load() {
                    this.loading = true;

                    try {
                        const response = await fetch('{{ route('admin.telephony.call_logs.index') }}?filters[contact_id][eq]={{ $person->id }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const payload = await response.json();
                        this.rows = Array.isArray(payload.records) ? payload.records : [];
                    } catch (error) {
                        this.rows = [];
                    } finally {
                        this.loading = false;
                    }
                }
            }"
            x-init="load()"
        >
            <div x-show="loading" class="py-4 text-xs text-gray-500 dark:text-gray-400">
                Loading...
            </div>

            <template x-if="!loading && rows.length === 0">
                <p class="py-4 text-sm text-gray-500 dark:text-gray-400">
                    No calls yet
                </p>
            </template>

            <div x-show="!loading && rows.length > 0" class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-y-2 text-left text-xs">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="px-2 py-1 font-medium">Direction</th>
                            <th class="px-2 py-1 font-medium">Status</th>
                            <th class="px-2 py-1 font-medium">Duration</th>
                            <th class="px-2 py-1 font-medium">Started</th>
                            <th class="px-2 py-1 font-medium">Agent</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="row in rows" :key="row.id">
                            <tr class="rounded-md bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                <td class="px-2 py-2" x-text="row.direction"></td>
                                <td class="px-2 py-2" x-text="row.status"></td>
                                <td class="px-2 py-2" x-text="row.duration_seconds ? Math.floor(row.duration_seconds / 60) + ':' + String(row.duration_seconds % 60).padStart(2, '0') : '—'"></td>
                                <td class="px-2 py-2" x-text="row.started_at ? new Date(row.started_at).toLocaleString() : '—'"></td>
                                <td class="px-2 py-2" x-text="row.agent_name || '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
