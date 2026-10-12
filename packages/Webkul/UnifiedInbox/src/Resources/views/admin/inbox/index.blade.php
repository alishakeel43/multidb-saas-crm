<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.inbox.title')
    </x-slot>

    @include('unified_inbox::partials.styles')

    @php
        $waConn = $connections->firstWhere('channel', 'whatsapp');
        $msgConn = $connections->firstWhere('channel', 'messenger');
        $igConn = $connections->firstWhere('channel', 'instagram');

        // "Linked" = a ChannelConnection row for this tenant with status 'connected'
        $uiLinked = $connections->where('status', 'connected')->pluck('channel')->unique()->values();
        $uiTab = request('channel');
        $uiTabLinked = $uiTab ? $uiLinked->contains($uiTab) : $uiLinked->isNotEmpty();

        // Never show threads/rows from unlinked channels
        if ($activeConversation && ! $uiLinked->contains($activeConversation->channel)) {
            $activeConversation = null;
        }
    @endphp

    <!-- Master Unified Inbox Workspace (Reference Design) -->
    <div class="ui-workspace {{ $activeConversation ? 'ui-detail' : 'ui-index' }}" v-pre>
        @include('unified_inbox::partials.header')

        @if (! $uiTabLinked)
        <section class="ui-connect-gate">
            @if ($uiTab)
                <span class="ui-channel ui-channel-{{ $uiTab }}" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $uiTab])</span>
                <h2>{{ $channels[$uiTab]['label'] ?? ucfirst($uiTab) }} is not connected</h2>
                <p class="ui-muted">Connect this channel to see and reply to its conversations here.</p>
            @else
                <span class="ui-channel" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => 'all'])</span>
                <h2>No channels connected</h2>
                <p class="ui-muted">Connect WhatsApp, Messenger or Instagram to start receiving conversations.</p>
            @endif
            <a class="primary-button" href="{{ route('admin.unified_inbox.channels.index') }}">@lang('unified_inbox::app.inbox.connect-channels')</a>
        </section>
        @else
        <div class="ui-columns">
            @include('unified_inbox::partials.list')

            @if ($activeConversation)
                <div class="ui-chat-layout">
                    <!-- Center Chat Column -->
                    <section class="ui-chat ui-thread ui-thread-{{ $activeConversation->channel }}">
                        <header class="ui-chat-header">
                            <a class="secondary-button ui-icon-btn ui-back" href="{{ route('admin.unified_inbox.index', request()->except('conversation_id')) }}" aria-label="Back">←</a>
                            @php $displayName = $activeConversation->customer_name; @endphp
                            <span class="ui-avatar">{{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}</span>
                            <div class="ui-chat-title">
                                <h1 class="truncate">{{ $displayName }}</h1>
                                <p class="ui-chat-sub">
                                    <span class="ui-pill ui-pill-{{ $activeConversation->channel }}">
                                        <span class="ui-channel ui-channel-{{ $activeConversation->channel }}" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $activeConversation->channel])</span>
                                        {{ $channels[$activeConversation->channel]['label'] ?? ucfirst($activeConversation->channel) }}
                                    </span>
                                    <span class="font-mono">{{ $activeConversation->external_id }}</span>
                                    <span class="ui-pill ui-pill-status-{{ $activeConversation->status }}">{{ ucfirst($activeConversation->status) }}</span>
                                </p>
                            </div>
                            <div class="ui-chat-actions">
                                <div class="ui-assignee">
                                    <span class="ui-muted">Assigned to</span>
                                    <strong>{{ $activeConversation->user?->name ?? 'Unassigned' }}</strong>
                                </div>
                                <select
                                    id="status-selector"
                                    aria-label="Conversation status"
                                    onchange="updateConversationStatus({{ $activeConversation->id }}, this.value)"
                                >
                                    <option value="open" @selected($activeConversation->status === 'open')>Open</option>
                                    <option value="pending" @selected($activeConversation->status === 'pending')>Pending</option>
                                    <option value="closed" @selected($activeConversation->status === 'closed')>Closed</option>
                                </select>
                                @if ($activeConversation->status !== 'closed')
                                    <button type="button" class="secondary-button ui-icon-btn" title="Mark as closed" aria-label="Mark as closed" onclick="updateConversationStatus({{ $activeConversation->id }}, 'closed')">✓</button>
                                @endif
                            </div>
                        </header>

                        <!-- Scrollable Message History -->
                        <div class="ui-history" id="chat-messages-container" aria-label="Messages">
                            @if ($activeMessages->hasPages())
                                <div class="mb-4">
                                    {{ $activeMessages->links() }}
                                </div>
                            @endif

                            @php $uiLastDay = null; @endphp
                            @forelse ($activeMessages as $message)
                                @php $uiDay = $message->occurred_at?->format('D, d M Y'); @endphp
                                @if ($uiDay && $uiDay !== $uiLastDay)
                                    <div class="ui-day"><span>{{ $uiDay }}</span></div>
                                    @php $uiLastDay = $uiDay; @endphp
                                @endif
                                <article class="ui-message ui-message-{{ $message->direction }}">
                                    <p v-pre class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                                    <div class="ui-message-meta">
                                        <span>{{ $message->occurred_at?->format('H:i') }}</span>
                                        @if ($message->direction === 'outbound')
                                            <span class="ui-msg-status ui-msg-status-{{ $message->status }}" title="{{ ucfirst($message->status) }}">{{ in_array($message->status, ['read', 'delivered']) ? '✓✓' : ($message->status === 'failed' ? '!' : '✓') }}</span>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <div class="ui-history-empty">
                                    <p class="ui-history-empty-title">No messages yet</p>
                                    <p class="ui-muted">Send the first reply below.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Chat Composer -->
                        <div class="ui-composer">
                            <div class="ui-composer-tabs" role="tablist">
                                <span class="ui-composer-tab" role="tab" aria-selected="true">Reply</span>
                                <span class="ui-composer-tab" role="tab" aria-disabled="true" title="Internal notes are not available yet">Internal Note <small>Soon</small></span>
                            </div>
                            <div class="ui-quick">
                                <button type="button" onclick="insertCanned('Hello! Thank you for contacting us. How can I assist you today?')" class="ui-chip">Greeting</button>
                                <button type="button" onclick="insertCanned('We have received your requirement. A team member will follow up shortly.')" class="ui-chip">Request received</button>
                                <button type="button" onclick="insertCanned('Could you please share your requirements so we can prepare a tailored quote?')" class="ui-chip">Quote inquiry</button>
                            </div>
                            <form id="message-form" onsubmit="event.preventDefault(); sendMessage({{ $activeConversation->id }});">
                                <textarea
                                    id="message-input"
                                    rows="2"
                                    placeholder="@lang('unified_inbox::app.inbox.type-message')"
                                    onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage({{ $activeConversation->id }}); }"
                                ></textarea>
                                <div class="ui-composer-bar">
                                    <span class="ui-muted">Enter to send · Shift+Enter for new line</span>
                                    <button type="submit" id="send-button" class="primary-button"><span>Send</span></button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <!-- Right CRM Profile Drawer (Tabs: Overview, CRM, Work) -->
                    <aside
                        class="ui-profile"
                        v-pre
                        data-ui-side-tabs
                        data-conversation-id="{{ $activeConversation->id }}"
                    >
                        <div class="ui-side-tabs-root">
                            @php $sideTabGroup = 'ui-side-tabs-'.$activeConversation->id; $c = $activeConversation; @endphp
                            <input type="radio" class="ui-side-radio" name="{{ $sideTabGroup }}" id="{{ $sideTabGroup }}-overview" data-side-tab="overview" checked>
                            <input type="radio" class="ui-side-radio" name="{{ $sideTabGroup }}" id="{{ $sideTabGroup }}-crm" data-side-tab="crm">
                            <input type="radio" class="ui-side-radio" name="{{ $sideTabGroup }}" id="{{ $sideTabGroup }}-work" data-side-tab="work">

                            <div class="ui-side-tablist" role="tablist">
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-overview" data-side-tab="overview">Customer</label>
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-crm" data-side-tab="crm">CRM Records</label>
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-work" data-side-tab="work">Activity</label>
                            </div>

                            <div class="ui-side-panels">
                                <div class="ui-side-panel" role="tabpanel" data-side-panel="overview">
                                    <section class="ui-profile-head">
                                        <span class="ui-avatar ui-avatar-lg">{{ mb_strtoupper(mb_substr($c->customer_name, 0, 1)) }}</span>
                                        <div class="ui-profile-head-copy">
                                            <h2>{{ $c->customer_name }}</h2>
                                            <span class="ui-pill ui-pill-{{ $c->channel }}">{{ $channels[$c->channel]['label'] ?? ucfirst($c->channel) }}</span>
                                        </div>
                                    </section>
                                    <section>
                                        <dl class="ui-dl">
                                            <dt>{{ $c->channel === 'whatsapp' ? 'Phone' : 'External ID' }}</dt><dd class="font-mono">{{ $c->external_id }}</dd>
                                            @if ($c->person?->emails)
                                                <dt>Email</dt><dd>{{ collect($c->person->emails)->pluck('value')->filter()->first() ?? '—' }}</dd>
                                            @endif
                                            <dt>Last message</dt><dd>{{ $c->last_message_at?->format('d M Y, H:i') ?? '—' }}</dd>
                                            <dt>Last inbound</dt><dd>{{ $c->last_incoming_at?->diffForHumans() ?? '—' }}</dd>
                                        </dl>
                                    </section>
                                    <section>
                                        <h2>Quick Actions</h2>
                                        <div class="ui-quick-actions">
                                            @if (! $c->person)
                                                <button type="button" class="secondary-button" onclick="createPersonFromChat({{ $c->id }})">＋ Create Contact</button>
                                            @endif
                                            @if (! $c->lead)
                                                <button type="button" class="secondary-button" onclick="createLeadFromChat({{ $c->id }})">＋ Create Lead</button>
                                            @endif
                                            <a class="secondary-button" href="{{ route('admin.activities.index') }}">Activities</a>
                                        </div>
                                    </section>
                                    <section>
                                        <h2>Conversation Status</h2>
                                        <label class="ui-field">Assigned agent
                                            <select id="agent-selector" onchange="assignConversationAgent({{ $c->id }}, this.value)">
                                                <option value="">Unassigned</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" @selected($c->user_id === $user->id)>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="ui-field">Status
                                            <select onchange="updateConversationStatus({{ $c->id }}, this.value)">
                                                @foreach (['open', 'pending', 'closed'] as $st)
                                                    <option value="{{ $st }}" @selected($c->status === $st)>{{ ucfirst($st) }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </section>
                                </div>

                                <div class="ui-side-panel" role="tabpanel" data-side-panel="crm">
                                    <section>
                                        <h2>Linked CRM Records</h2>
                                        <div class="ui-record">
                                            <span class="ui-record-label">Contact</span>
                                            @if ($c->person)
                                                <span class="ui-record-value">{{ $c->person->name }}</span>
                                                <a class="secondary-button" href="{{ route('admin.contacts.persons.edit', $c->person->id) }}">View</a>
                                            @else
                                                <span class="ui-record-value ui-muted">Not linked</span>
                                                <button type="button" class="secondary-button" onclick="createPersonFromChat({{ $c->id }})">＋ Create</button>
                                            @endif
                                        </div>
                                        <div class="ui-record">
                                            <span class="ui-record-label">Lead</span>
                                            @if ($c->lead)
                                                <span class="ui-record-value">{{ $c->lead->title }}</span>
                                                <a class="secondary-button" href="{{ route('admin.leads.view', $c->lead->id) }}">View</a>
                                            @else
                                                <span class="ui-record-value ui-muted">Not linked</span>
                                                <button type="button" class="secondary-button" onclick="createLeadFromChat({{ $c->id }})">＋ Create</button>
                                            @endif
                                        </div>
                                    </section>
                                </div>

                                <div class="ui-side-panel" role="tabpanel" data-side-panel="work">
                                    <section>
                                        <h2>Tasks & Follow-ups</h2>
                                        <p class="ui-muted">Schedule CRM activities for this customer.</p>
                                        <a href="{{ route('admin.activities.index') }}" class="secondary-button">Open Activities →</a>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            @else
                <!-- Empty State (No Chat Selected) -->
                <section class="ui-empty">
                    <span class="ui-avatar" aria-hidden="true">☏</span>
                    <h2>@lang('unified_inbox::app.inbox.select-conversation')</h2>
                    <p class="ui-muted">@lang('unified_inbox::app.inbox.select-conversation-desc')</p>
                    <a class="primary-button" href="{{ route('admin.unified_inbox.channels.index') }}">
                        ＋ @lang('unified_inbox::app.inbox.connect-channels')
                    </a>

                    <!-- Channels Quick Status Grid in Empty State -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3 w-full max-w-lg text-left">
                        @php
                            $waConn = $connections->firstWhere('channel', 'whatsapp');
                            $msgConn = $connections->firstWhere('channel', 'messenger');
                            $igConn = $connections->firstWhere('channel', 'instagram');
                        @endphp

                        <div class="ui-card p-3">
                            <span class="ui-channel ui-channel-whatsapp mb-2" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => 'whatsapp'])</span>
                            <h3 class="font-bold text-xs">WhatsApp</h3>
                            <p class="ui-muted text-[11px]">{{ $waConn ? 'Connected' : 'Not Connected' }}</p>
                        </div>

                        <div class="ui-card p-3">
                            <span class="ui-channel ui-channel-messenger mb-2" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => 'messenger'])</span>
                            <h3 class="font-bold text-xs">Messenger</h3>
                            <p class="ui-muted text-[11px]">{{ $msgConn ? 'Connected' : 'Not Connected' }}</p>
                        </div>

                        <div class="ui-card p-3">
                            <span class="ui-channel ui-channel-instagram mb-2" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => 'instagram'])</span>
                            <h3 class="font-bold text-xs">Instagram</h3>
                            <p class="ui-muted text-[11px]">{{ $igConn ? 'Connected' : 'Not Connected' }}</p>
                        </div>
                    </div>

                    <p class="ui-muted text-xs mt-3">
                        Central Meta Webhook: <code class="font-mono text-[11px]">{{ $centralWebhookUrl }}</code>
                    </p>
                </section>
            @endif
        </div>
        @endif
    </div>

    @pushOnce('scripts')
    <script>
        function insertCanned(text) {
            const input = document.getElementById('message-input');
            if (!input) return;
            input.value = text;
            input.focus();
        }

        async function sendMessage(conversationId) {
            const input = document.getElementById('message-input');
            const button = document.getElementById('send-button');
            const body = input.value.trim();

            if (!body) return;

            button.disabled = true;

            try {
                const response = await fetch("{{ url(config('app.admin_path') . '/unified-inbox/conversations') }}/" + conversationId + "/send", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ body: body })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    input.value = '';
                    const history = document.getElementById('chat-messages-container');
                    const article = document.createElement('article');
                    article.className = 'ui-message ui-message-outbound';
                    article.innerHTML = `
                        <div class="ui-message-meta">
                            <strong>Outbound</strong>
                            <span>Just now · sent</span>
                        </div>
                        <p class="mt-1 whitespace-pre-wrap break-words leading-relaxed">${escapeHtml(body)}</p>
                    `;
                    history.appendChild(article);
                    history.scrollTop = history.scrollHeight;
                } else {
                    alert(data.error || 'Failed to send message.');
                }
            } catch (err) {
                alert('Network error while sending message.');
            } finally {
                button.disabled = false;
            }
        }

        async function updateConversationStatus(id, status) {
            try {
                const response = await fetch("{{ url(config('app.admin_path') . '/unified-inbox/conversations') }}/" + id + "/status", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: status })
                });
                if (response.ok) {
                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function assignConversationAgent(id, userId) {
            try {
                const response = await fetch("{{ url(config('app.admin_path') . '/unified-inbox/conversations') }}/" + id + "/assign", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId })
                });
                if (response.ok) {
                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function createPersonFromChat(id) {
            try {
                const response = await fetch("{{ url(config('app.admin_path') . '/unified-inbox/conversations') }}/" + id + "/link-person", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    window.location.reload();
                } else {
                    alert('Could not create person: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Network error while creating person.');
            }
        }

        async function createLeadFromChat(id) {
            try {
                const response = await fetch("{{ url(config('app.admin_path') . '/unified-inbox/conversations') }}/" + id + "/link-lead", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    window.location.reload();
                } else {
                    alert('Could not convert to lead: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Network error while converting lead.');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const history = document.getElementById('chat-messages-container');
            if (history) {
                history.scrollTop = history.scrollHeight;
            }
        });
    </script>
    @endPushOnce
</x-admin::layouts>
