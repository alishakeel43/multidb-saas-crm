@php
    $audiences = \Illuminate\Support\Facades\Schema::hasTable('ma_audiences')
        ? \Webkul\MarketingAutomation\Models\Audience::query()->orderBy('name')->get()
        : collect();
@endphp

<div class="mt-4 grid gap-4">
    <p class="text-sm font-semibold text-gray-800 dark:text-white">Budget, audience, and attribution</p>

    <p class="text-sm text-gray-600 dark:text-gray-300" v-if="campaign.ma_results" v-text="campaign.ma_results"></p>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-800 dark:text-white">Budget</label>
        <input type="number" min="0" step="0.01" name="ma_budget" v-model="campaign.ma_budget" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-800 dark:text-white">Spent</label>
        <input type="number" min="0" step="0.01" name="ma_spent" v-model="campaign.ma_spent" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-800 dark:text-white">Currency</label>
        <input type="text" name="ma_currency" maxlength="8" v-model="campaign.ma_currency" placeholder="USD" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-800 dark:text-white">Audience</label>
        <select name="ma_audience_id" v-model="campaign.ma_audience_id" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <option value="">All contacts</option>
            @foreach ($audiences as $audience)
                <option value="{{ $audience->id }}">{{ $audience->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-800 dark:text-white">Source name</label>
        <input type="text" name="ma_source_name" v-model="campaign.ma_source_name" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-800 dark:text-white">UTM campaign</label>
        <input type="text" name="ma_utm_campaign" v-model="campaign.ma_utm_campaign" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-800 dark:text-white">Starts</label>
        <input type="datetime-local" name="ma_starts_at" v-model="campaign.ma_starts_at" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-800 dark:text-white">Ends</label>
        <input type="datetime-local" name="ma_ends_at" v-model="campaign.ma_ends_at" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
    </div>
</div>
