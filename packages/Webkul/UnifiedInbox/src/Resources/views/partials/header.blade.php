@php
    // Unread totals per channel for tab badges (read-only aggregate, no new endpoint)
    $uiUnreadByChannel = \Webkul\UnifiedInbox\Models\Conversation::query()
        ->selectRaw('channel, SUM(unread_count) as unread')
        ->whereIn('channel', $uiLinked ?? [])
        ->groupBy('channel')
        ->pluck('unread', 'channel');
    $uiUnreadAll = (int) $uiUnreadByChannel->sum();
    $uiTabQuery = request()->only('search', 'status', 'unread', 'folder', 'label');
@endphp

<header class="ui-header">
    <div>
        <h1>@lang('unified_inbox::app.inbox.title')</h1>
        <p class="ui-muted">All your customer conversations in one place</p>
    </div>
    <div class="ui-actions">
        <a class="secondary-button" href="{{ route('admin.unified_inbox.channels.index') }}">
            @lang('unified_inbox::app.inbox.manage-channels')
        </a>
    </div>
</header>

<nav class="ui-tabs" aria-label="Channels">
    <a class="ui-tab" href="{{ route('admin.unified_inbox.index', $uiTabQuery) }}" @if(!request('channel')) aria-current="page" @endif>
        <span class="ui-channel" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => 'all'])</span>
        <span>@lang('unified_inbox::app.inbox.all-channels')</span>
        <span class="ui-tab-count {{ $uiUnreadAll ? 'is-unread' : '' }}">{{ $uiUnreadAll }}</span>
    </a>
    @foreach ($channels as $key => $channel)
        @php $uiTabUnread = (int) ($uiUnreadByChannel[$key] ?? 0); @endphp
        <a class="ui-tab" href="{{ route('admin.unified_inbox.index', array_merge($uiTabQuery, ['channel' => $key])) }}" @if(request('channel') === $key) aria-current="page" @endif>
            <span class="ui-channel ui-channel-{{ $key }}" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $key])</span>
            <span>{{ $channel['label'] ?? ucfirst($key) }}</span>
            @if (($uiLinked ?? collect())->contains($key))
                <span class="ui-tab-count {{ $uiTabUnread ? 'is-unread' : '' }}">{{ $uiTabUnread }}</span>
            @else
                <span class="ui-tab-off" title="Not connected">Connect</span>
            @endif
        </a>
    @endforeach
</nav>
