@php
    $webFormId = isset($webform) ? (int) $webform->id : 0;
    $rows = ($webFormId && \Illuminate\Support\Facades\Schema::hasTable('ma_attributions'))
        ? \Webkul\MarketingAutomation\Models\Attribution::query()->where('web_form_id', $webFormId)->latest('id')->limit(10)->get()
        : collect();
@endphp

<div class="box-shadow mt-4 rounded-lg bg-white p-4 dark:bg-gray-900">
    <p class="mb-2 text-base font-semibold dark:text-white">Attribution</p>
    <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
        Submissions keep UTM source, medium, campaign, content, and term, then match an active campaign.
    </p>
    <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
        @forelse ($rows as $row)
            <div>{{ $row->utm_source ?: '—' }} / {{ $row->utm_campaign ?: '—' }}</div>
        @empty
            <p>No attributed submissions yet.</p>
        @endforelse
    </div>
</div>
