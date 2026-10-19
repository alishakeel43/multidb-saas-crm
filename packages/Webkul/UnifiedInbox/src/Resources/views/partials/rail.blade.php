@php
    // Meta Business inbox style left rail: channels + folders + labels. Pure GET links, no JS.
    $uiRailBase = request()->only('search', 'channel', 'folder', 'label');
    $uiRail = fn (array $set) => route('admin.unified_inbox.index', array_filter(array_merge($uiRailBase, $set), fn ($v) => $v !== null && $v !== ''));
    $uiFolder = $folder ?? null;
    $uiFolders = [
        'inbox' => ['Inbox', 'M3 13h4l2 3h6l2-3h4M5 5h14l2 8v6H3v-6z'],
        'unread' => ['Unread', 'M4 6h16v12H4zM4 7l8 6 8-6'],
        'follow_up' => ['Follow up', 'M5 21V4h11l-2 4 2 4H5'],
        'done' => ['Done', 'M5 12l5 5L20 7'],
        'spam' => ['Spam', 'M12 3l9 16H3zM12 10v4M12 17v.5'],
    ];
@endphp
<nav class="ui-rail" aria-label="Inbox folders" v-pre>
    <p class="ui-rail-head">Channels</p>
    <a class="ui-rail-item" href="{{ $uiRail(['channel' => null]) }}" @if(! request('channel')) aria-current="page" @endif>
        <span class="ui-channel" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => 'all'])</span>
        <span class="ui-rail-label">All</span>
    </a>
    @foreach ($channels as $key => $channel)
        <a class="ui-rail-item" href="{{ $uiRail(['channel' => $key]) }}" @if(request('channel') === $key) aria-current="page" @endif>
            <span class="ui-channel ui-channel-{{ $key }}" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $key])</span>
            <span class="ui-rail-label">{{ $channel['label'] ?? ucfirst($key) }}</span>
        </a>
    @endforeach

    <p class="ui-rail-head">Folders</p>
    @foreach ($uiFolders as $key => [$label, $icon])
        <a class="ui-rail-item" href="{{ $uiRail(['folder' => $uiFolder === $key ? null : $key]) }}" @if($uiFolder === $key) aria-current="page" @endif>
            <svg class="ui-rail-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
            <span class="ui-rail-label">{{ $label }}</span>
            @if (($folderCounts[$key] ?? 0) > 0)
                <span class="ui-rail-count">{{ $folderCounts[$key] }}</span>
            @endif
        </a>
    @endforeach

    @if (! empty($allLabels))
        <p class="ui-rail-head">Labels</p>
        @foreach ($allLabels as $railLabel)
            <a class="ui-rail-item" href="{{ $uiRail(['label' => ($labelFilter ?? '') === $railLabel ? null : $railLabel]) }}" @if(($labelFilter ?? '') === $railLabel) aria-current="page" @endif>
                <span class="ui-label-dot" style="--ui-label-hue: {{ crc32($railLabel) % 360 }}" aria-hidden="true"></span>
                <span class="ui-rail-label">{{ $railLabel }}</span>
            </a>
        @endforeach
    @endif
</nav>
