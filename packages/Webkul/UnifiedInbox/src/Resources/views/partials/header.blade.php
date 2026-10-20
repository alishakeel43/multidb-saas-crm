@php
    $uiTabStats = $channelTabStats ?? [];
    $uiConvAll = (int) array_sum(array_column($uiTabStats, 'conversations'));
    $uiUnreadAll = (int) array_sum(array_column($uiTabStats, 'unread'));
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
    <a class="ui-tab" href="{{ route('admin.unified_inbox.index', $uiTabQuery) }}" @if(!request('channel')) aria-current="page" @endif data-ui-channel="all" data-ui-conversations="{{ $uiConvAll }}" data-ui-unread="{{ $uiUnreadAll }}">
        <span class="ui-channel" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => 'all'])</span>
        <span>@lang('unified_inbox::app.inbox.all-channels')</span>
        <span class="ui-tab-count {{ $uiUnreadAll ? 'is-unread' : '' }}" title="{{ $uiConvAll }} {{ \Illuminate\Support\Str::plural('conversation', $uiConvAll) }}{{ $uiUnreadAll ? ', '.$uiUnreadAll.' unread' : '' }}">{{ $uiConvAll }}</span>
    </a>
    @foreach ($channels as $key => $channel)
        @php
            $uiStat = $uiTabStats[$key] ?? ['connected' => false, 'conversations' => 0, 'unread' => 0];
        @endphp
        <a class="ui-tab" href="{{ route('admin.unified_inbox.index', array_merge($uiTabQuery, ['channel' => $key])) }}" @if(request('channel') === $key) aria-current="page" @endif data-ui-channel="{{ $key }}" data-ui-connected="{{ $uiStat['connected'] ? '1' : '0' }}" data-ui-conversations="{{ (int) $uiStat['conversations'] }}" data-ui-unread="{{ (int) $uiStat['unread'] }}">
            <span class="ui-channel ui-channel-{{ $key }}" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $key])</span>
            <span>{{ $channel['label'] ?? ucfirst($key) }}</span>
            @if ($uiStat['connected'])
                <span class="ui-tab-status is-on">@lang('unified_inbox::app.channels.connected')</span>
            @else
                <span class="ui-tab-off" title="Not connected">{{ $uiStat['conversations'] > 0 ? __('unified_inbox::app.channels.disconnected') : 'Connect' }}</span>
            @endif
            @if ($uiStat['connected'] || $uiStat['conversations'] > 0)
                <span class="ui-tab-count {{ $uiStat['unread'] ? 'is-unread' : '' }}" title="{{ (int) $uiStat['conversations'] }} {{ \Illuminate\Support\Str::plural('conversation', (int) $uiStat['conversations']) }}{{ $uiStat['unread'] ? ', '.(int) $uiStat['unread'].' unread' : '' }}">{{ (int) $uiStat['conversations'] }}</span>
            @endif
        </a>
    @endforeach
</nav>
