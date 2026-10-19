@php
    $uiBase = request()->only('search', 'channel', 'status', 'unread');
    $uiChip = function (array $set) use ($uiBase) {
        return route('admin.unified_inbox.index', array_filter(array_merge($uiBase, $set), fn ($v) => $v !== null && $v !== ''));
    };
    $uiStatus = request('status');
    $uiUnread = request()->boolean('unread');
@endphp

<aside class="ui-list" aria-label="Conversations">
    @php
        $uiComposeChannels = collect($channels ?? [])->filter(fn ($channel, $key) => ($uiLinked ?? collect())->contains($key));
        $uiComposeDefault = $uiComposeChannels->has(request('channel')) ? request('channel') : $uiComposeChannels->keys()->first();
    @endphp
    <div class="ui-list-toolbar">
        <form method="GET" action="{{ route('admin.unified_inbox.index') }}" class="ui-list-search">
            <input type="hidden" name="channel" value="{{ request('channel') }}">
            <input type="hidden" name="status" value="{{ $uiStatus }}">
            @if ($uiUnread)<input type="hidden" name="unread" value="1">@endif
            <label class="ui-search">
                <span class="sr-only">Search</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search conversations..." maxlength="200">
            </label>
        </form>
        @if ($uiComposeChannels->isNotEmpty())
            <button type="button" class="ui-new-chat" onclick="uiOpenCompose(@js($uiComposeDefault))" title="Add new contact" aria-label="Add new contact">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5z"/>
                </svg>
            </button>
        @endif
    </div>

    <div class="ui-chips" role="group" aria-label="Filters">
        <a class="ui-chip" href="{{ $uiChip(['unread' => $uiUnread ? null : 1]) }}" @if($uiUnread) aria-pressed="true" @endif>Unread</a>
        @foreach (['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'] as $value => $label)
            <a class="ui-chip" href="{{ $uiChip(['status' => $uiStatus === $value ? null : $value]) }}" @if($uiStatus === $value) aria-pressed="true" @endif>{{ $label }}</a>
        @endforeach
        @if ($uiStatus || $uiUnread || request('search'))
            <a class="ui-chip ui-chip-clear" href="{{ route('admin.unified_inbox.index', request()->only('channel')) }}">Clear</a>
        @endif
    </div>

    <div class="ui-conversations">
        @php $uiRows = $conversations->getCollection()->filter(fn ($c) => $uiLinked->contains($c->channel)); @endphp
        @forelse ($uiRows as $item)
            <a class="ui-conversation" href="{{ route('admin.unified_inbox.index', array_merge(request()->query(), ['conversation_id' => $item->id])) }}" @if($activeConversation?->id === $item->id) aria-current="page" @endif>
                <span class="ui-avatar-wrap">
                    <span class="ui-avatar" v-pre>{{ mb_strtoupper(mb_substr($item->customer_name, 0, 1)) }}</span>
                    <span class="ui-channel ui-channel-{{ $item->channel }} ui-avatar-badge" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $item->channel])</span>
                </span>
                <span class="ui-conversation-copy">
                    <span class="ui-row-top">
                        <strong v-pre>{{ $item->customer_name }}</strong>
                        <time class="ui-time">{{ $item->last_message_at?->isToday() ? $item->last_message_at->format('H:i') : $item->last_message_at?->format('d M') }}</time>
                    </span>
                    <span class="ui-row-mid">
                        <span class="ui-pill ui-pill-{{ $item->channel }}">{{ $channels[$item->channel]['label'] ?? ucfirst($item->channel) }}</span>
                        @if ($item->status !== 'open')
                            <span class="ui-pill ui-pill-status-{{ $item->status }}">{{ ucfirst($item->status) }}</span>
                        @endif
                    </span>
                    <span class="ui-row-bottom">
                        <span class="ui-preview" v-pre>{{ $item->last_message_preview ?: 'No messages yet' }}</span>
                        @if ($item->unread_count > 0)
                            <span class="ui-count">{{ $item->unread_count }}</span>
                        @endif
                    </span>
                </span>
            </a>
        @empty
            @php
                $uiFiltered = $uiStatus || $uiUnread || request('search');
                $uiTabLabel = request('channel') ? ($channels[request('channel')]['label'] ?? ucfirst(request('channel'))) : null;
            @endphp
            <div class="ui-list-empty">
                <p class="ui-list-empty-title">
                    @if ($uiFiltered)
                        No {{ $uiUnread ? 'unread ' : '' }}{{ $uiStatus ? $uiStatus.' ' : '' }}conversations{{ $uiTabLabel ? ' in '.$uiTabLabel : '' }}
                    @else
                        No conversations yet{{ $uiTabLabel ? ' in '.$uiTabLabel : '' }}
                    @endif
                </p>
                <p class="ui-muted">
                    {{ $uiFiltered ? 'Try another filter or clear filters to see all chats.' : 'New messages will appear here as soon as customers reach out.' }}
                </p>
                @if ($uiFiltered)
                    <a href="{{ route('admin.unified_inbox.index', request()->only('channel')) }}" class="secondary-button">Clear filters</a>
                @endif
            </div>
        @endforelse
    </div>

    <div class="ui-list-footer">
        <span class="ui-muted">
            @if ($conversations->total())
                {{ $conversations->firstItem() }}–{{ $conversations->lastItem() }} of {{ $conversations->total() }}
            @endif
        </span>
        @if ($conversations->hasPages())
            <span class="ui-pager">
                <a class="secondary-button" href="{{ $conversations->previousPageUrl() ?? '#' }}" @if($conversations->onFirstPage()) aria-disabled="true" @endif aria-label="Previous">‹</a>
                <a class="secondary-button" href="{{ $conversations->nextPageUrl() ?? '#' }}" @if(!$conversations->hasMorePages()) aria-disabled="true" @endif aria-label="Next">›</a>
            </span>
        @endif
    </div>
</aside>
