@php
    $uiBase = request()->only('search', 'channel', 'folder', 'label');
    $uiChip = function (array $set) use ($uiBase) {
        return route('admin.unified_inbox.index', array_filter(array_merge($uiBase, $set), fn ($v) => $v !== null && $v !== ''));
    };
    $uiFolder = $folder ?? request('folder');
    $uiLabel = $labelFilter ?? request('label');
    $uiFolderCounts = $folderCounts ?? [];
    // Meta-style pills under search (folders moved out of the left rail).
    $uiMetaFilters = ($hasFolders ?? false)
        ? [
            'inbox' => 'Inbox',
            'unread' => 'Unread',
            'follow_up' => 'Follow up',
            'done' => 'Done',
            'spam' => 'Spam',
        ]
        : [
            'unread' => 'Unread',
        ];
@endphp

<aside class="ui-list" aria-label="Conversations">
    @php
        $uiComposeChannels = collect($channels ?? [])->filter(fn ($channel, $key) => ($uiLinked ?? collect())->contains($key));
        $uiComposeDefault = $uiComposeChannels->has(request('channel')) ? request('channel') : $uiComposeChannels->keys()->first();
    @endphp
    <div class="ui-list-toolbar">
        <form method="GET" action="{{ route('admin.unified_inbox.index') }}" class="ui-list-search">
            <input type="hidden" name="channel" value="{{ request('channel') }}">
            @if ($uiFolder)<input type="hidden" name="folder" value="{{ $uiFolder }}">@endif
            @if ($uiLabel)<input type="hidden" name="label" value="{{ $uiLabel }}">@endif
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

    <div class="ui-chips ui-meta-filters" role="group" aria-label="Inbox filters">
        @foreach ($uiMetaFilters as $value => $label)
            @php
                if ($value === 'inbox') {
                    $isActive = blank($uiFolder);
                    $href = $uiChip(['folder' => null]);
                } elseif ($value === 'unread' && ! ($hasFolders ?? false)) {
                    $isActive = request()->boolean('unread');
                    $href = $uiChip(['unread' => $isActive ? null : 1]);
                } else {
                    $isActive = $uiFolder === $value;
                    $href = $uiChip(['folder' => $isActive ? null : $value]);
                }
                $count = (int) ($uiFolderCounts[$value] ?? 0);
            @endphp
            <a class="ui-chip" href="{{ $href }}" @if($isActive) aria-pressed="true" @endif>
                <span>{{ $label }}</span>
                @if ($count > 0)<span class="ui-chip-count">{{ $count }}</span>@endif
            </a>
        @endforeach
        @foreach (($allLabels ?? []) as $chipLabel)
            <a class="ui-chip ui-chip-label" href="{{ $uiChip(['label' => $uiLabel === $chipLabel ? null : $chipLabel]) }}" @if($uiLabel === $chipLabel) aria-pressed="true" @endif style="--ui-label-hue: {{ crc32($chipLabel) % 360 }}">
                <span class="ui-label-dot" aria-hidden="true"></span>
                <span>{{ $chipLabel }}</span>
            </a>
        @endforeach
        @if ($uiFolder || $uiLabel || request('search') || request()->boolean('unread'))
            <a class="ui-chip ui-chip-clear" href="{{ route('admin.unified_inbox.index', request()->only('channel')) }}">Clear</a>
        @endif
    </div>

    <div class="ui-conversations">
        @php $uiRows = $conversations->getCollection()->filter(fn ($c) => $uiLinked->contains($c->channel)); @endphp
        @forelse ($uiRows as $item)
            <a class="ui-conversation" href="{{ route('admin.unified_inbox.index', array_merge(request()->query(), ['conversation_id' => $item->id])) }}" @if($activeConversation?->id === $item->id) aria-current="page" @endif>
                <span class="ui-avatar-wrap">
                    <span class="ui-avatar" v-pre>@if ($item->avatar_url)<img class="ui-avatar-img" src="{{ $item->avatar_url }}" alt="" loading="lazy" referrerpolicy="no-referrer">@else{{ mb_strtoupper(mb_substr($item->customer_name, 0, 1)) }}@endif</span>
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
                            <span class="ui-pill ui-pill-status-{{ $item->status }}">{{ $item->status === 'closed' ? 'Done' : ucfirst($item->status) }}</span>
                        @endif
                        @if (isset($oldNumbers[$item->channel.'|'.$item->account_id]))
                            <span class="ui-pill ui-pill-oldnum" title="Conversation on a number that is no longer connected">via old {{ $oldNumbers[$item->channel.'|'.$item->account_id] }}</span>
                        @endif
                        @if ($item->is_follow_up)
                            <span class="ui-pill" title="Follow up">⚑ Follow up</span>
                        @endif
                    </span>
                    <span class="ui-row-bottom">
                        <span class="ui-preview" v-pre>{{ $item->last_message_preview ?: 'No messages yet' }}</span>
                        @if ($item->unread_count > 0)
                            <span class="ui-count">{{ $item->unread_count }}</span>
                        @endif
                    </span>
                    @if (! empty($item->labels))
                        <span class="ui-labels" v-pre>
                            @foreach (array_slice($item->labels, 0, 3) as $rowLabel)
                                <span class="ui-label" style="--ui-label-hue: {{ crc32($rowLabel) % 360 }}">{{ $rowLabel }}</span>
                            @endforeach
                        </span>
                    @endif
                </span>
            </a>
        @empty
            @php
                $uiStatus = $statusFilter ?? request('status');
                $uiUnread = ($unreadFilter ?? false) || $uiFolder === 'unread';
                $uiFiltered = $uiStatus || $uiUnread || $uiFolder || $uiLabel || request('search');
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
