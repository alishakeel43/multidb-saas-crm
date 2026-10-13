@php
    $ready = \Illuminate\Support\Facades\Schema::hasTable('ma_engagement_rules');
    $rules = $ready ? \Webkul\MarketingAutomation\Models\EngagementRule::query()->orderBy('event_type')->get() : collect();
    $total = $ready ? (int) \Webkul\MarketingAutomation\Models\EngagementScore::query()->sum('points') : 0;
@endphp

@if ($ready)
    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-2 text-base font-semibold dark:text-white">Engagement scoring</p>
            <p class="mb-3 text-sm text-gray-500">These points stay separate from the field score above. Total engagement points: {{ $total }}.</p>
            <form method="POST" action="{{ route('admin.marketing_automation.engagement.store') }}" class="grid gap-2">
                @csrf
                <input name="name" required placeholder="Rule name" class="w-full rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <select name="event_type" class="w-full rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <option value="email_open">Email opened</option>
                    <option value="email_click">Email clicked</option>
                    <option value="form_submit">Form submitted</option>
                    <option value="unsubscribe">Unsubscribed</option>
                </select>
                <input name="points" type="number" required value="5" class="w-full rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <label class="flex items-center gap-2 text-sm dark:text-white">
                    <input type="checkbox" name="is_active" value="1" checked> Active
                </label>
                <button class="primary-button" type="submit">Save engagement rule</button>
            </form>
        </div>
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <div class="space-y-2 text-sm dark:text-white">
                @forelse ($rules as $rule)
                    <div class="flex items-center justify-between rounded border p-2 dark:border-gray-700">
                        <div>
                            <div class="font-medium">{{ $rule->name }} ({{ $rule->points }})</div>
                            <div class="text-gray-500">{{ $rule->event_type }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.marketing_automation.engagement.destroy', $rule->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="transparent-button" type="submit">Delete</button>
                        </form>
                    </div>
                @empty
                    <p class="text-gray-500">No engagement rules yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif
