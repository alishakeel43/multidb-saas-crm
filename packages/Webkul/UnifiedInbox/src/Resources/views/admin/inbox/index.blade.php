<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.inbox.title')
    </x-slot>

    @include('unified_inbox::partials.styles')

    @php
        $waConn = $connections->firstWhere('channel', 'whatsapp');
        $msgConn = $connections->firstWhere('channel', 'messenger');
        $igConn = $connections->firstWhere('channel', 'instagram');
    @endphp

    <!-- Master Unified Inbox Workspace (Reference Design) -->
    <div class="ui-workspace {{ $activeConversation ? 'ui-detail' : 'ui-index' }}">
        @include('unified_inbox::partials.header')

        <div class="ui-columns">
            @include('unified_inbox::partials.list')

            @if ($activeConversation)
                <div class="ui-chat-layout">
                    <!-- Center Chat Column -->
                    <section class="ui-chat">
                        <header class="ui-chat-header">
                            <a class="secondary-button" href="{{ route('admin.unified_inbox.index', request()->except('conversation_id')) }}" aria-label="Back">←</a>
                            @php($displayName = $activeConversation->customer_name)
                            <span class="ui-avatar" v-pre>{{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}</span>
                            <div style="flex:1;min-width:0">
                                <h1 v-pre class="truncate">{{ $displayName }}</h1>
                                <p class="ui-muted">{{ ucfirst($activeConversation->channel) }} · <span class="capitalize">{{ $activeConversation->status }}</span> · <span class="font-mono text-xs">{{ $activeConversation->external_id }}</span></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <select
                                    id="status-selector"
                                    onchange="updateConversationStatus({{ $activeConversation->id }}, this.value)"
                                    class="secondary-button text-xs py-1 px-2"
                                >
                                    <option value="open" @selected($activeConversation->status === 'open')>🟢 Open</option>
                                    <option value="pending" @selected($activeConversation->status === 'pending')>🟡 Pending</option>
                                    <option value="closed" @selected($activeConversation->status === 'closed')>⚪ Closed</option>
                                </select>
                            </div>
                        </header>

                        <!-- Scrollable Message History -->
                        <div class="ui-history" id="chat-messages-container" aria-label="Messages">
                            @forelse ($activeMessages as $message)
                                <article class="ui-message ui-message-{{ $message->direction }}">
                                    <div class="ui-message-meta">
                                        <strong>{{ ucfirst($message->direction) }}</strong>
                                        <span>{{ $message->occurred_at ? $message->occurred_at->format('M d, H:i') : '' }} · {{ ucfirst($message->status) }}</span>
                                    </div>
                                    <p v-pre class="mt-1 whitespace-pre-wrap break-words leading-relaxed">{{ $message->body }}</p>
                                </article>
                            @empty
                                <div class="p-8 text-center text-slate-400">
                                    <span class="ui-avatar mx-auto mb-2" aria-hidden="true">👋</span>
                                    <p class="font-semibold text-xs">Start the conversation</p>
                                    <p class="ui-muted text-xs">Type a message below or use a quick reply.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Chat Composer -->
                        <div class="ui-composer">
                            <!-- Quick Replies Canned Suggestions -->
                            <div class="mb-3 flex items-center gap-2 overflow-x-auto text-xs no-scrollbar">
                                <span class="ui-muted text-xs font-bold">⚡ Quick:</span>
                                <button type="button" onclick="insertCanned('Hello! Thank you for contacting us. How can I assist you today?')" class="secondary-button text-xs py-1 px-2.5 whitespace-nowrap">
                                    👋 Greeting
                                </button>
                                <button type="button" onclick="insertCanned('We have received your requirement. A team member will follow up shortly.')" class="secondary-button text-xs py-1 px-2.5 whitespace-nowrap">
                                    📋 Request Received
                                </button>
                                <button type="button" onclick="insertCanned('Could you please share your requirements so we can prepare a tailored quote?')" class="secondary-button text-xs py-1 px-2.5 whitespace-nowrap">
                                    💼 Quote Inquiry
                                </button>
                            </div>

                            <form id="message-form" onsubmit="event.preventDefault(); sendMessage({{ $activeConversation->id }});" class="flex flex-col gap-2">
                                <textarea
                                    id="message-input"
                                    rows="3"
                                    placeholder="@lang('unified_inbox::app.inbox.type-message')"
                                    onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage({{ $activeConversation->id }}); }"
                                    class="w-full"
                                ></textarea>
                                <div class="flex items-center justify-between">
                                    <span class="ui-muted text-xs">Press Enter to send</span>
                                    <button type="submit" id="send-button" class="primary-button text-xs px-5 py-2">
                                        <span>Send Reply</span>
                                    </button>
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
                        <div class="ui-profile-head">
                            <span class="ui-avatar">{{ mb_strtoupper(mb_substr($activeConversation->customer_name, 0, 1)) }}</span>
                            <div class="ui-profile-head-copy">
                                <h2>{{ $activeConversation->customer_name }}</h2>
                                <p class="ui-muted">{{ ucfirst($activeConversation->channel) }} · <span class="capitalize">{{ $activeConversation->status }}</span></p>
                            </div>
                        </div>

                        <div class="ui-side-tabs-root">
                            @php($sideTabGroup = 'ui-side-tabs-'.$activeConversation->id)
                            <input type="radio" class="ui-side-radio" name="{{ $sideTabGroup }}" id="{{ $sideTabGroup }}-overview" data-side-tab="overview" checked>
                            <input type="radio" class="ui-side-radio" name="{{ $sideTabGroup }}" id="{{ $sideTabGroup }}-crm" data-side-tab="crm">
                            <input type="radio" class="ui-side-radio" name="{{ $sideTabGroup }}" id="{{ $sideTabGroup }}-work" data-side-tab="work">

                            <div class="ui-side-tablist" role="tablist">
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-overview" data-side-tab="overview">Overview</label>
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-crm" data-side-tab="crm">CRM Links</label>
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-work" data-side-tab="work">Work</label>
                            </div>

                            <div class="ui-side-panels">
                                <!-- TAB 1: Overview -->
                                <div class="ui-side-panel" role="tabpanel" data-side-panel="overview">
                                    <section>
                                        <h2>Conversation Info</h2>
                                        <div class="ui-side-item">
                                            <p class="ui-muted">Customer External ID</p>
                                            <p class="font-mono text-xs">{{ $activeConversation->external_id }}</p>
                                        </div>
                                        <div class="ui-side-item">
                                            <p class="ui-muted">Channel</p>
                                            <p class="font-semibold text-xs">{{ ucfirst($activeConversation->channel) }}</p>
                                        </div>
                                        <div class="ui-side-item">
                                            <p class="ui-muted">Assigned Agent</p>
                                            <select
                                                id="agent-selector"
                                                onchange="assignConversationAgent({{ $activeConversation->id }}, this.value)"
                                                class="w-full mt-1.5"
                                            >
                                                <option value="">Unassigned</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" @selected($activeConversation->user_id === $user->id)>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </section>
                                </div>

                                <!-- TAB 2: CRM Links -->
                                <div class="ui-side-panel" role="tabpanel" data-side-panel="crm">
                                    <section>
                                        <h2>CRM Contact (Person)</h2>
                                        @if ($activeConversation->person)
                                            <div class="ui-side-item">
                                                <p class="font-semibold text-xs text-blue-600">{{ $activeConversation->person->name }}</p>
                                                <a href="{{ route('admin.contacts.persons.edit', $activeConversation->person->id) }}" class="text-xs text-blue-500 hover:underline">
                                                    View in CRM &rarr;
                                                </a>
                                            </div>
                                        @else
                                            <p class="ui-muted text-xs">No CRM Person linked yet.</p>
                                            <button type="button" onclick="createPersonFromChat({{ $activeConversation->id }})" class="secondary-button text-xs mt-2 w-full">
                                                ＋ Create Person
                                            </button>
                                        @endif
                                    </section>

                                    <section>
                                        <h2>CRM Lead</h2>
                                        @if ($activeConversation->lead)
                                            <div class="ui-side-item">
                                                <p class="font-semibold text-xs text-amber-600">{{ $activeConversation->lead->title }}</p>
                                                <a href="{{ route('admin.leads.view', $activeConversation->lead->id) }}" class="text-xs text-blue-500 hover:underline">
                                                    View Lead &rarr;
                                                </a>
                                            </div>
                                        @else
                                            <p class="ui-muted text-xs">No Lead converted yet.</p>
                                            <button type="button" onclick="createLeadFromChat({{ $activeConversation->id }})" class="primary-button text-xs mt-2 w-full">
                                                ＋ Convert to Lead
                                            </button>
                                        @endif
                                    </section>
                                </div>

                                <!-- TAB 3: Work / Activities -->
                                <div class="ui-side-panel" role="tabpanel" data-side-panel="work">
                                    <section>
                                        <h2>Tasks & Follow-ups</h2>
                                        <p class="ui-muted text-xs mb-3">Schedule CRM activities directly from this customer chat.</p>
                                        <a href="{{ route('admin.activities.index') }}" class="secondary-button text-xs text-center block">
                                            Open Activities &rarr;
                                        </a>
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
                            <span class="ui-channel ui-channel-whatsapp mb-2">W</span>
                            <h3 class="font-bold text-xs">WhatsApp</h3>
                            <p class="ui-muted text-[11px]">{{ $waConn ? 'Connected' : 'Not Connected' }}</p>
                        </div>

                        <div class="ui-card p-3">
                            <span class="ui-channel ui-channel-messenger mb-2">M</span>
                            <h3 class="font-bold text-xs">Messenger</h3>
                            <p class="ui-muted text-[11px]">{{ $msgConn ? 'Connected' : 'Not Connected' }}</p>
                        </div>

                        <div class="ui-card p-3">
                            <span class="ui-channel ui-channel-instagram mb-2">I</span>
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
