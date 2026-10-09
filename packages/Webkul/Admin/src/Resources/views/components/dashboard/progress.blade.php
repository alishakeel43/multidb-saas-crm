@props(['metric', 'inverse' => false, 'share' => null])

@php
    $positiveIsGood = $inverse ? 'false' : 'true';
    $title = match ($share) {
        'won' => __('admin::app.dashboard.win-share'),
        'lost' => __('admin::app.dashboard.lost-share'),
        default => __('admin::app.dashboard.comparison-period'),
    };
@endphp

<div
    v-if="{{ $metric }}.progress !== null"
    class="flex items-center gap-0.5 text-xs font-semibold"
    @if ($share === 'won')
        :class="Number({{ $metric }}.progress) > 0 ? 'text-green-500' : 'text-gray-500 dark:text-gray-400'"
    @elseif ($share === 'lost')
        :class="Number({{ $metric }}.progress) > 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'"
    @else
        :class="Number({{ $metric }}.progress) === 0 ? 'text-gray-500 dark:text-gray-400' : ((Number({{ $metric }}.progress) > 0) === {{ $positiveIsGood }} ? 'text-green-500' : 'text-red-500')"
    @endif
    title="{{ $title }}"
>
    @unless ($share)
        <span
            v-if="Number.isFinite(Number({{ $metric }}.progress)) && Number({{ $metric }}.progress) !== 0"
            class="text-base !font-semibold"
            :class="[
                Number({{ $metric }}.progress) < 0 ? 'icon-stats-down' : 'icon-stats-up',
                (Number({{ $metric }}.progress) > 0) === {{ $positiveIsGood }} ? 'text-green-500 dark:!text-green-500' : 'text-red-500 dark:!text-red-500'
            ]"
        ></span>
    @endunless

    <span v-text="(Number.isFinite(Number({{ $metric }}.progress)) ? Math.abs(Number({{ $metric }}.progress)) : 0).toFixed(2) + '%'"></span>
</div>
