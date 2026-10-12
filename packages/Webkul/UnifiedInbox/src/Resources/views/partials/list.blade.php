<aside class="ui-list" aria-label="Conversations">
    <div class="ui-list-heading">
        <h2>Conversations</h2>
        <span class="ui-muted">Latest</span>
    </div>
    <div class="ui-conversations">
        @forelse ($conversations as $item)
            <a class="ui-conversation" href="{{ route('admin.unified_inbox.index', array_merge(request()->query(), ['conversation_id' => $item->id])) }}" @if($activeConversation?->id === $item->id) aria-current="page" @endif>
                <span class="ui-avatar" v-pre>{{ mb_strtoupper(mb_substr($item->customer_name, 0, 1)) }}</span>
                <span class="ui-conversation-copy">
                    <strong v-pre>{{ $item->customer_name }}</strong>
                    <span class="ui-preview" v-pre>{{ $item->last_message_preview ?: 'No messages yet' }}</span>
                    <span class="ui-preview ui-channel-label">
                        <span class="ui-channel ui-channel-{{ $item->channel }}" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $item->channel])</span>
                        <span class="ui-channel-label-text">{{ $channels[$item->channel]['label'] ?? ucfirst($item->channel) }} · {{ $item->last_message_at?->diffForHumans(null, true) }}</span>
                    </span>
                </span>
                @if ($item->unread_count > 0)
                    <span><span class="ui-count">{{ $item->unread_count }}</span></span>
                @endif
            </a>
        @empty
            <div class="p-6 text-center">
                <p class="ui-muted text-xs">No conversations found.</p>
                <a href="{{ route('admin.unified_inbox.channels.index') }}" class="primary-button mt-3 inline-block text-xs">
                    ＋ Connect Channels
                </a>
            </div>
        @endforelse
    </div>
    @if ($conversations->hasPages())
        <div class="border-t border-slate-200 p-3 dark:border-slate-700">
            {{ $conversations->links() }}
        </div>
    @endif
</aside>
