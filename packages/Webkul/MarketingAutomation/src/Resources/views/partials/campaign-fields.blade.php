@php
    $startsAt = data_get($campaign, 'starts_at');
    $endsAt = data_get($campaign, 'ends_at');
@endphp
<div class="mb-2 grid gap-2 md:grid-cols-2">
    <label class="text-sm dark:text-white">Name
        <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="name" value="{{ old('name', data_get($campaign, 'name', '')) }}" required>
    </label>
    <label class="text-sm dark:text-white">Status
        <select class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="status">
            @foreach (['draft', 'active', 'paused', 'completed'] as $status)
                <option value="{{ $status }}" @selected(old('status', data_get($campaign, 'status', 'draft')) === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm dark:text-white">Budget
        <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="budget" type="number" min="0" step="0.01" value="{{ old('budget', data_get($campaign, 'budget', 0)) }}" required>
    </label>
    <label class="text-sm dark:text-white">Spent
        <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="spent" type="number" min="0" step="0.01" value="{{ old('spent', data_get($campaign, 'spent', 0)) }}">
    </label>
    <label class="text-sm dark:text-white">Currency
        <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="currency" value="{{ old('currency', data_get($campaign, 'currency', 'USD')) }}" maxlength="8">
    </label>
    <label class="text-sm dark:text-white">Audience
        <select class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="audience_id">
            <option value="">All contacts with an email</option>
            @foreach ($audiences as $audience)
                <option value="{{ $audience->id }}" @selected((string) old('audience_id', data_get($campaign, 'audience_id', '')) === (string) $audience->id)>{{ $audience->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm dark:text-white">Source name
        <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="source_name" value="{{ old('source_name', data_get($campaign, 'source_name', '')) }}" placeholder="google">
    </label>
    <label class="text-sm dark:text-white">UTM campaign
        <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="utm_campaign" value="{{ old('utm_campaign', data_get($campaign, 'utm_campaign', '')) }}" placeholder="spring-launch">
    </label>
    <label class="text-sm dark:text-white">Starts
        <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="starts_at" type="datetime-local" value="{{ old('starts_at', $startsAt ? $startsAt->format('Y-m-d\TH:i') : '') }}">
    </label>
    <label class="text-sm dark:text-white">Ends
        <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="ends_at" type="datetime-local" value="{{ old('ends_at', $endsAt ? $endsAt->format('Y-m-d\TH:i') : '') }}">
    </label>
</div>
