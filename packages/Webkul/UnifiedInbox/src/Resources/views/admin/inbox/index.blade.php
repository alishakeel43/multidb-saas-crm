<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.inbox.title')
    </x-slot>

    @include('unified_inbox::partials.styles')
    @include('unified_inbox::partials.styles-pro')

    @php
        $connectionPicker = app(\Webkul\UnifiedInbox\Repositories\ChannelConnectionRepository::class);
        $waConn = $connectionPicker->preferredForChannel($connections, 'whatsapp');
        $msgConn = $connectionPicker->preferredForChannel($connections, 'messenger');
        $igConn = $connectionPicker->preferredForChannel($connections, 'instagram');

        // "Linked" = a ChannelConnection row for this tenant with status 'connected'
        // Channels with a live connection, plus channels that only have history from an old (archived) number.
        $uiLinked = $connections->where('status', 'connected')->pluck('channel')->merge($historyChannels ?? [])->unique()->values();
        $uiOldNumbers = $oldNumbers ?? [];
        $uiTab = request('channel');
        $uiTabLinked = $uiTab ? $uiLinked->contains($uiTab) : $uiLinked->isNotEmpty();

        // Never show threads/rows from unlinked channels
        if ($activeConversation && ! $uiLinked->contains($activeConversation->channel)) {
            $activeConversation = null;
        }
    @endphp

    <!-- Master Unified Inbox Workspace (Reference Design) -->
    @php
        // Krayin chrome: 62px sticky header + 12px (pt-3) + bottom reserve (72px with fixed "Powered by" bar, else 16px)
        $uiChrome = 62 + 12 + ((bool) core()->getConfigData('general.settings.footer.show') ? 72 : 16);
    @endphp
    <div class="ui-workspace ui-inbox {{ $activeConversation ? 'ui-detail' : 'ui-index' }}" style="--ui-chrome: {{ $uiChrome }}px" v-pre>
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
        <div class="ui-columns {{ ($hasFolders ?? false) ? 'ui-has-rail' : '' }}">
            @if ($hasFolders ?? false)
                @include('unified_inbox::partials.rail')
            @endif
            @include('unified_inbox::partials.list')

            @if ($activeConversation)
                <div class="ui-chat-layout">
                    <!-- Center Chat Column -->
                    <section class="ui-chat ui-thread ui-thread-{{ $activeConversation->channel }}">
                        <header class="ui-chat-header">
                            <a class="secondary-button ui-icon-btn ui-back" href="{{ route('admin.unified_inbox.index', request()->except('conversation_id')) }}" aria-label="Back">←</a>
                            @php $displayName = $activeConversation->customer_name; @endphp
                            <span class="ui-avatar">@if ($activeConversation->avatar_url)<img class="ui-avatar-img" src="{{ $activeConversation->avatar_url }}" alt="" referrerpolicy="no-referrer">@else{{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}@endif</span>
                            <div class="ui-chat-title">
                                <h1 class="truncate">{{ $displayName }}</h1>
                                <p class="ui-chat-sub">
                                    <span class="ui-pill ui-pill-{{ $activeConversation->channel }}">
                                        <span class="ui-channel ui-channel-{{ $activeConversation->channel }}" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $activeConversation->channel])</span>
                                        {{ $channels[$activeConversation->channel]['label'] ?? ucfirst($activeConversation->channel) }}
                                    </span>
                                    <span class="font-mono">{{ $activeConversation->external_id }}</span>
                                    @if (isset($uiOldNumbers[$activeConversation->channel.'|'.$activeConversation->account_id]))
                                        <span class="ui-pill ui-pill-oldnum" title="This conversation happened on a number that is no longer connected">via old number {{ $uiOldNumbers[$activeConversation->channel.'|'.$activeConversation->account_id] }}</span>
                                    @endif
                                    <span class="ui-pill ui-pill-status-{{ $activeConversation->status }}">{{ ucfirst($activeConversation->status) }}</span>
                                </p>
                            </div>
                            <div class="ui-chat-actions">
                                @php $assigneeName = $activeConversation->user?->name; @endphp
                                <div class="ui-assignee-chip {{ $assigneeName ? 'is-assigned' : 'is-empty' }}" title="{{ $assigneeName ? 'Assigned to '.$assigneeName : 'No agent assigned' }}">
                                    <span class="ui-assignee-chip-icon" aria-hidden="true">
                                        @if ($assigneeName)
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0 2c-4.14 0-7.5 2.24-7.5 5v1.25c0 .41.34.75.75.75h13.5c.41 0 .75-.34.75-.75V19c0-2.76-3.36-5-7.5-5Z"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="4"/><path d="M2 21v-1c0-3 3-5 7-5s7 2 7 5v1"/><path d="M19 8v6M16 11h6"/></svg>
                                        @endif
                                    </span>
                                    <span class="ui-assignee-chip-copy">
                                        <span class="ui-assignee-chip-label">{{ $assigneeName ? 'Assigned to' : 'Assignee' }}</span>
                                        <span class="ui-assignee-chip-name">{{ $assigneeName ?? 'Unassigned' }}</span>
                                    </span>
                                </div>
                                @if ($hasFolders ?? false)
                                    <button type="button" class="ui-flag-btn {{ $activeConversation->status === 'closed' ? 'is-on' : '' }}" onclick="updateConversationStatus({{ $activeConversation->id }}, '{{ $activeConversation->status === 'closed' ? 'open' : 'closed' }}')" title="{{ $activeConversation->status === 'closed' ? 'Move back to Inbox' : 'Mark done (moves to Done)' }}">✓ {{ $activeConversation->status === 'closed' ? 'Reopen' : 'Mark done' }}</button>
                                    <button type="button" class="ui-flag-btn {{ $activeConversation->is_follow_up ? 'is-on' : '' }}" onclick="uiSetFlag({{ $activeConversation->id }}, 'is_follow_up', {{ $activeConversation->is_follow_up ? 'false' : 'true' }})" aria-pressed="{{ $activeConversation->is_follow_up ? 'true' : 'false' }}">⚑ Follow up</button>
                                    <button type="button" class="ui-flag-btn is-spam {{ $activeConversation->is_spam ? 'is-on' : '' }}" onclick="uiSetFlag({{ $activeConversation->id }}, 'is_spam', {{ $activeConversation->is_spam ? 'false' : 'true' }})" aria-pressed="{{ $activeConversation->is_spam ? 'true' : 'false' }}">⊘ {{ $activeConversation->is_spam ? 'Not spam' : 'Spam' }}</button>
                                @endif
                                <div class="ui-status-seg" role="group" aria-label="Conversation status">
                                    @foreach (['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'] as $stKey => $stLabel)
                                        <button
                                            type="button"
                                            class="ui-status-opt ui-status-opt-{{ $stKey }} {{ $activeConversation->status === $stKey ? 'is-active' : '' }}"
                                            aria-pressed="{{ $activeConversation->status === $stKey ? 'true' : 'false' }}"
                                            onclick="updateConversationStatus({{ $activeConversation->id }}, '{{ $stKey }}')"
                                        ><span class="ui-status-dot" aria-hidden="true"></span>{{ $stLabel }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </header>

                        @php
                            $uiConn = array_filter([
                                'lead' => $activeConversation->lead ? ['Lead', $activeConversation->lead->title, route('admin.leads.view', $activeConversation->lead->id)] : null,
                                'person' => $activeConversation->person ? ['Contact', $activeConversation->person->name, route('admin.contacts.persons.view', $activeConversation->person->id)] : null,
                                'organization' => $activeConversation->organization ? ['Organization', $activeConversation->organization->name, route('admin.contacts.organizations.edit', $activeConversation->organization->id)] : null,
                            ]);
                        @endphp
                        <div class="ui-connbar" aria-label="Connected CRM records">
                            <span class="ui-connbar-label">Connected to</span>
                            @forelse ($uiConn as $connType => [$connLabel, $connName, $connUrl])
                                <a class="ui-conn-chip ui-conn-{{ $connType }}" href="{{ $connUrl }}" title="{{ $connName }}">
                                    <span class="ui-conn-type">{{ $connLabel }}</span>
                                    <span class="ui-conn-name">{{ $connName }}</span>
                                </a>
                            @empty
                                <span class="ui-conn-none">No CRM record linked</span>
                            @endforelse
                            <label class="ui-conn-manage" for="ui-side-tabs-{{ $activeConversation->id }}-crm">{{ $uiConn ? 'Manage' : 'Link record' }}</label>
                        </div>

                        <!-- Scrollable Message History -->
                        <div class="ui-history" id="chat-messages-container" aria-label="Messages">
                            @if ($activeMessages->hasPages())
                                <div class="mb-4">
                                    {{ $activeMessages->links() }}
                                </div>
                            @endif

                            @foreach (($priorThreads ?? collect()) as $prior)
                                <details class="ui-prior">
                                    <summary>
                                        {{ $prior->number_active ? 'Same customer on current number' : 'Earlier history on old number' }}
                                        <strong>{{ $prior->number_label }}</strong>
                                        · {{ $prior->message_total }} message{{ $prior->message_total === 1 ? '' : 's' }}
                                        @if ($prior->last_message_at) · last {{ $prior->last_message_at->format('d M Y') }} @endif
                                        <a href="{{ route('admin.unified_inbox.index', ['conversation_id' => $prior->id]) }}">Open</a>
                                    </summary>
                                    @foreach ($prior->recent_messages as $pm)
                                        <article class="ui-message ui-message-{{ $pm->direction }} ui-message-prior">
                                            <p v-pre class="whitespace-pre-wrap break-words">{!! str_replace(["\r\n", "\r", "\n"], '<br>', e($pm->body)) !!}</p>
                                            <div class="ui-message-meta"><span>{{ $pm->occurred_at?->format('d M, H:i') }}</span></div>
                                        </article>
                                    @endforeach
                                </details>
                            @endforeach

                            @php $uiLastDay = null; @endphp
                            @forelse ($activeMessages as $message)
                                @php $uiDay = $message->occurred_at?->format('D, d M Y'); @endphp
                                @if ($uiDay && $uiDay !== $uiLastDay)
                                    <div class="ui-day"><span>{{ $uiDay }}</span></div>
                                    @php $uiLastDay = $uiDay; @endphp
                                @endif
                                <article class="ui-message ui-message-{{ $message->direction }}" data-message-id="{{ $message->id }}">
                                    @if (! empty($message->attachments))
                                        @include('unified_inbox::partials.attachments', ['message' => $message])
                                    @endif
                                    <p v-pre class="whitespace-pre-wrap break-words">{!! str_replace(["\r\n", "\r", "\n"], '<br>', e($message->body)) !!}</p>
                                    <div class="ui-message-meta">
                                        <span title="{{ $message->occurred_at?->format('d M Y, H:i') }}">{{ $message->occurred_at?->format('H:i') }}</span>
                                        @if ($message->direction === 'outbound')
                                            @include('unified_inbox::partials.tick', ['status' => (string) $message->status])
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
                        @php
                            $uiIsWa = $activeConversation->channel === 'whatsapp';
                            $uiOnOldNumber = isset($uiOldNumbers[$activeConversation->channel.'|'.$activeConversation->account_id]);
                            // Replies from an old-number thread go out from the current number, so its window applies.
                            $uiWindowSource = $uiOnOldNumber ? ($continuation ?? null) : $activeConversation;
                            $uiWindowEnds = $uiWindowSource?->serviceWindowEndsAt();
                            $uiWindowOpen = ! $uiIsWa || ($uiWindowSource?->serviceWindowOpen() ?? false);
                        @endphp
                        <div class="ui-composer {{ $uiWindowOpen ? '' : 'is-window-closed' }}" data-window-open="{{ $uiWindowOpen ? '1' : '0' }}">
                            @if ($uiOnOldNumber)
                                <div class="ui-window ui-window-closed">
                                    <span aria-hidden="true">↪</span>
                                    <span>This chat was on old number {{ $uiOldNumbers[$activeConversation->channel.'|'.$activeConversation->account_id] }}. Replies are sent from your current number and continue in that conversation.</span>
                                    @if ($continuation ?? null)
                                        <a class="secondary-button" href="{{ route('admin.unified_inbox.index', ['conversation_id' => $continuation->id]) }}">Open current chat</a>
                                    @endif
                                </div>
                            @endif
                            @if ($uiIsWa)
                                @if ($uiWindowOpen)
                                    <div class="ui-window ui-window-open" id="ui-window" data-ends="{{ $uiWindowEnds?->toIso8601String() }}">
                                        <span aria-hidden="true">●</span>
                                        <span>24h reply window open · <strong id="ui-window-left">{{ $uiWindowEnds ? now()->diff($uiWindowEnds)->format('%hh %Im') : '' }}</strong> left</span>
                                    </div>
                                @else
                                    <div class="ui-window ui-window-closed" id="ui-window">
                                        <span aria-hidden="true">⏱</span>
                                        <span>{{ $uiWindowEnds ? '24h reply window closed '.$uiWindowEnds->diffForHumans() : 'No customer message yet' }} — only approved templates can be sent.</span>
                                        <button type="button" class="primary-button" onclick="uiOpenTemplateFor(@js($activeConversation->external_id), @js($activeConversation->customer_name))">Send template</button>
                                    </div>
                                @endif
                            @endif
                            <div class="ui-composer-tabs" role="tablist">
                                <button type="button" class="ui-composer-tab" role="tab" aria-selected="true" data-composer-mode="reply" onclick="uiSetComposerMode('reply')">Reply</button>
                                <button type="button" class="ui-composer-tab" role="tab" aria-selected="false" data-composer-mode="note" onclick="uiSetComposerMode('note')" title="Visible to your team only — never sent to the customer">Internal Note</button>
                                <span class="ui-muted ui-composer-hint" id="composer-hint">Enter to send · Shift+Enter for new line</span>
                            </div>
                            <div class="ui-suggest-row">
                            <div class="ui-quick" id="ui-quick">
                                @forelse (($quickReplies ?? collect())->take(6) as $qr)
                                    <button type="button" onclick="insertCanned(@js($qr->body))" class="ui-chip" title="{{ $qr->shortcut ? '/'.$qr->shortcut : $qr->title }}">{{ $qr->title }}</button>
                                @empty
                                    <button type="button" onclick="insertCanned('Hello! Thank you for contacting us. How can I assist you today?')" class="ui-chip">Greeting</button>
                                    <button type="button" onclick="insertCanned('We have received your requirement. A team member will follow up shortly.')" class="ui-chip">Request received</button>
                                    <button type="button" onclick="insertCanned('Could you please share your requirements so we can prepare a tailored quote?')" class="ui-chip">Quote inquiry</button>
                                @endforelse
                                <button type="button" class="ui-chip" onclick="uiOpenQuickReplies()" title="Manage saved quick replies">⚙ Manage</button>
                            </div>
                            <div class="ui-ai" id="ui-ai" data-state="loading" aria-live="polite">
                                <div class="ui-ai-head">
                                    <span class="ui-ai-label" title="AI suggestions — click to insert (never auto-sent)"><span aria-hidden="true">✨</span> AI</span>
                                    <button type="button" class="ui-ai-refresh" id="ui-ai-refresh" onclick="uiLoadAiSuggestions(true)" title="Get new suggestions" aria-label="Refresh AI suggestions">↻</button>
                                </div>
                                <div class="ui-ai-list" id="ui-ai-list"><span class="ui-muted">Thinking…</span></div>
                            </div>
                            </div>
                            <div class="ui-attach-preview" id="ui-attach-preview" hidden>
                                <span aria-hidden="true">📎</span><span id="ui-attach-name"></span>
                                <button type="button" onclick="uiClearAttachment()" aria-label="Remove attachment">✕</button>
                            </div>
                            <form id="message-form" onsubmit="event.preventDefault(); uiSubmitComposer({{ $activeConversation->id }});">
                                <div class="ui-composer-tools">
                                    <button type="button" class="ui-tool-btn" onclick="uiToggleEmoji(event)" title="Emoji" aria-label="Emoji">☺</button>
                                    @if ($uiIsWa)
                                        <button type="button" class="ui-tool-btn" onclick="document.getElementById('ui-attach-input').click()" title="Attach image or document" aria-label="Attach" @disabled(! $uiWindowOpen)>📎</button>
                                        <input type="file" id="ui-attach-input" hidden accept="image/jpeg,image/png,image/webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv" onchange="uiPickAttachment(this)">
                                    @endif
                                    <div class="ui-popover ui-emoji-grid" id="ui-emoji" hidden></div>
                                    <div class="ui-popover ui-slash" id="ui-slash" hidden role="listbox"></div>
                                </div>
                                <textarea
                                    id="message-input"
                                    rows="1"
                                    oninput="uiAutoGrow(this); uiSlashCheck(this)"
                                    placeholder="{{ $uiWindowOpen ? trans('unified_inbox::app.inbox.type-message').' · type / for quick replies' : 'Reply window closed — send an approved template' }}"
                                    onkeydown="if (uiSlashKey(event)) return; if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); uiSubmitComposer({{ $activeConversation->id }}); }"
                                    @if (! $uiWindowOpen) data-reply-locked="1" readonly @endif
                                ></textarea>
                                <button type="submit" id="send-button" class="primary-button" @if (! $uiWindowOpen) data-reply-locked="1" disabled @endif><span>Send</span></button>
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
                            <input type="radio" class="ui-side-radio" name="{{ $sideTabGroup }}" id="{{ $sideTabGroup }}-notes" data-side-tab="notes">

                            <div class="ui-side-tablist" role="tablist">
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-overview" data-side-tab="overview"><span class="ui-side-tab-label">Customer</span></label>
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-crm" data-side-tab="crm"><span class="ui-side-tab-label">CRM Records</span></label>
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-work" data-side-tab="work"><span class="ui-side-tab-label">Activity</span></label>
                                <label class="ui-side-tab" for="{{ $sideTabGroup }}-notes" data-side-tab="notes"><span class="ui-side-tab-label">Notes</span>@if ($chatNotes->count())<span class="ui-side-tab-count" aria-label="{{ $chatNotes->count() }} notes">{{ $chatNotes->count() }}</span>@endif</label>
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
                                    @if ($hasFolders ?? false)
                                    <section>
                                        <h2>Labels</h2>
                                        <div class="ui-label-editor">
                                            <div class="ui-labels" id="ui-label-list" data-labels="{{ json_encode(array_values($c->labels ?? [])) }}">
                                                @forelse (($c->labels ?? []) as $cl)
                                                    <span class="ui-label" style="--ui-label-hue: {{ crc32($cl) % 360 }}">{{ $cl }} <button type="button" onclick="uiRemoveLabel({{ $c->id }}, @js($cl))" aria-label="Remove label {{ $cl }}">×</button></span>
                                                @empty
                                                    <span class="ui-muted">No labels</span>
                                                @endforelse
                                            </div>
                                            <input type="text" id="ui-label-input" list="ui-label-options" maxlength="40" placeholder="Add label and press Enter" onkeydown="if(event.key==='Enter'){event.preventDefault();uiAddLabel({{ $c->id }}, this.value);}">
                                            <datalist id="ui-label-options">
                                                @foreach (($allLabels ?? []) as $opt)<option value="{{ $opt }}"></option>@endforeach
                                            </datalist>
                                        </div>
                                    </section>
                                    @endif
                                    <section>
                                        <h2>Quick Actions</h2>
                                        <div class="ui-quick-actions">
                                            @if (! $c->person)
                                                <button type="button" class="secondary-button" onclick="createPersonFromChat({{ $c->id }})">＋ Create Contact</button>
                                            @endif
                                            @if (! $c->lead)
                                                <button type="button" class="secondary-button" onclick="createLeadFromChat({{ $c->id }})">＋ Create Lead</button>
                                            @endif
                                            <label class="secondary-button" for="{{ $sideTabGroup }}-work">Activity timeline</label>
                                        </div>
                                    </section>
                                    <section>
                                        <h2>Assignment</h2>
                                        @php $sideAssignee = $c->user?->name; @endphp
                                        <div class="ui-assign-card {{ $sideAssignee ? '' : 'is-empty' }}">
                                            <span class="ui-assignee-avatar" aria-hidden="true">{{ $sideAssignee ? mb_strtoupper(mb_substr($sideAssignee, 0, 1)) : '?' }}</span>
                                            <span class="ui-assignee-copy">
                                                <span class="ui-assignee-label">Assigned agent</span>
                                                <span class="ui-assignee-name">{{ $sideAssignee ?? 'Unassigned' }}</span>
                                            </span>
                                            @if ($sideAssignee)
                                                <button type="button" class="ui-unassign-btn" onclick="assignConversationAgent({{ $c->id }}, '')">Unassign</button>
                                            @endif
                                        </div>
                                        <label class="ui-field">{{ $sideAssignee ? 'Reassign to' : 'Assign to' }}
                                            <select id="agent-selector" onchange="assignConversationAgent({{ $c->id }}, this.value)">
                                                <option value="">Unassigned</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" @selected($c->user_id === $user->id)>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </section>
                                </div>

                                <div class="ui-side-panel" role="tabpanel" data-side-panel="crm">
                                    <section>
                                        <h2>Linked CRM Records</h2>
                                        @php
                                            $uiRecords = [
                                                'person' => ['label' => 'Contact', 'model' => $c->person, 'title' => $c->person?->name, 'url' => $c->person ? route('admin.contacts.persons.view', $c->person->id) : null, 'create' => 'createPersonFromChat('.$c->id.')'],
                                                'lead' => ['label' => 'Lead', 'model' => $c->lead, 'title' => $c->lead?->title, 'url' => $c->lead ? route('admin.leads.view', $c->lead->id) : null, 'create' => 'createLeadFromChat('.$c->id.')'],
                                                'organization' => ['label' => 'Organization', 'model' => $c->organization, 'title' => $c->organization?->name, 'url' => $c->organization ? route('admin.contacts.organizations.edit', $c->organization->id) : null, 'create' => 'createOrganizationFromChat('.$c->id.')'],
                                            ];
                                        @endphp
                                        @foreach ($uiRecords as $rType => $rec)
                                            <div class="ui-record" data-crm-record="{{ $rType }}">
                                                <span class="ui-record-label"><span class="ui-conn-type ui-conn-{{ $rType }}">{{ $rec['label'] }}</span></span>
                                                @if ($rec['model'])
                                                    <a class="ui-record-value ui-record-link" href="{{ $rec['url'] }}" title="{{ $rec['title'] }}">{{ $rec['title'] }}</a>
                                                    <span class="ui-record-actions">
                                                        <button type="button" class="ui-link-btn" onclick="uiOpenCrmSearch('{{ $rType }}')"><span aria-hidden="true">⇄</span> Change</button>
                                                        <button type="button" class="ui-link-btn ui-link-danger" onclick="uiLinkRecord({{ $c->id }}, '{{ $rType }}', null)"><span aria-hidden="true">✕</span> Unlink</button>
                                                    </span>
                                                @else
                                                    <span class="ui-record-value ui-muted">Not linked</span>
                                                    <span class="ui-record-actions">
                                                        <button type="button" class="ui-link-btn ui-link-primary" onclick="uiOpenCrmSearch('{{ $rType }}')"><span aria-hidden="true">🔗</span> Link existing</button>
                                                        <button type="button" class="ui-link-btn" onclick="{{ $rec['create'] }}"><span aria-hidden="true">＋</span> New</button>
                                                    </span>
                                                @endif
                                                <div class="ui-crm-search" data-crm-search="{{ $rType }}" hidden>
                                                    <input type="search" placeholder="Search {{ strtolower($rec['label']) }}s…" autocomplete="off" oninput="uiCrmSearch(this, '{{ $rType }}')">
                                                    <ul class="ui-crm-results" role="listbox"></ul>
                                                </div>
                                            </div>
                                        @endforeach
                                    </section>

                                    @php $rel = $crmRelated; @endphp
                                    @if ($rel && ($rel['personInferred'] || $rel['organizationInferred'] || $rel['leads']->isNotEmpty() || $rel['orgContacts']->isNotEmpty()))
                                        <section>
                                            <h2>Related in CRM</h2>
                                            @if ($rel['personInferred'])
                                                <div class="ui-related-row">
                                                    <span class="ui-record-label">Lead contact</span>
                                                    <span class="ui-conn-type ui-conn-person">Contact</span>
                                                    <a href="{{ route('admin.contacts.persons.view', $rel['person']->id) }}" title="{{ $rel['person']->name }}">{{ $rel['person']->name }}</a>
                                                    <button type="button" class="ui-link-btn ui-link-primary" onclick="uiLinkRecord({{ $c->id }}, 'person', {{ $rel['person']->id }})"><span aria-hidden="true">🔗</span> Link</button>
                                                </div>
                                            @endif
                                            @if ($rel['organizationInferred'])
                                                <div class="ui-related-row">
                                                    <span class="ui-record-label">Contact's org</span>
                                                    <span class="ui-conn-type ui-conn-organization">Organization</span>
                                                    <a href="{{ route('admin.contacts.organizations.edit', $rel['organization']->id) }}" title="{{ $rel['organization']->name }}">{{ $rel['organization']->name }}</a>
                                                    <button type="button" class="ui-link-btn ui-link-primary" onclick="uiLinkRecord({{ $c->id }}, 'organization', {{ $rel['organization']->id }})"><span aria-hidden="true">🔗</span> Link</button>
                                                </div>
                                            @endif
                                            @if ($rel['leads']->isNotEmpty())
                                                <p class="ui-related-head">Other leads of {{ $rel['person']->name }}</p>
                                                @foreach ($rel['leads'] as $rl)
                                                    <div class="ui-related-row">
                                                        <span class="ui-conn-type ui-conn-lead">Lead</span>
                                                        <a href="{{ route('admin.leads.view', $rl->id) }}" title="{{ $rl->title }}">{{ $rl->title }}</a>
                                                        <span class="ui-muted">{{ $rl->stage?->name }}</span>
                                                        @if (! $c->lead_id)
                                                            <button type="button" class="ui-link-btn ui-link-primary" onclick="uiLinkRecord({{ $c->id }}, 'lead', {{ $rl->id }})"><span aria-hidden="true">🔗</span> Link</button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                            @if ($rel['orgContacts']->isNotEmpty())
                                                <p class="ui-related-head">Contacts at {{ $rel['organization']->name }}</p>
                                                @foreach ($rel['orgContacts'] as $op)
                                                    <div class="ui-related-row">
                                                        <span class="ui-conn-type ui-conn-person">Contact</span>
                                                        <a href="{{ route('admin.contacts.persons.view', $op->id) }}" title="{{ $op->name }}">{{ $op->name }}</a>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </section>
                                    @endif
                                </div>

                                <div class="ui-side-panel" role="tabpanel" data-side-panel="work">
                                    <section>
                                        <h2>Activity Timeline</h2>
                                        @if (! $c->person_id && ! $c->lead_id && $timeline->isEmpty())
                                            <p class="ui-muted">Link a contact or lead to see its Krayin activities here. Internal notes from this chat also appear here.</p>
                                        @endif
                                        <ol class="ui-timeline">
                                            @forelse ($timeline as $act)
                                                @php $actFromChat = str_contains((string) $act->additional, '"conversation_id":'.$c->id.','); @endphp
                                                <li class="ui-tl-item ui-tl-{{ $act->type }}">
                                                    <div class="ui-tl-top">
                                                        <span class="ui-tl-type">{{ ucfirst($act->type) }}</span>
                                                        @if ($actFromChat)<span class="ui-tl-badge">This chat</span>@endif
                                                        <time class="ui-muted" title="{{ $act->created_at?->format('d M Y, H:i') }}">{{ $act->created_at?->diffForHumans() }}</time>
                                                    </div>
                                                    @if ($act->type !== 'note' || ! $actFromChat)
                                                        <a class="ui-tl-title" href="{{ route('admin.activities.edit', $act->id) }}">{{ $act->title }}</a>
                                                    @endif
                                                    @if ($act->comment)
                                                        <p v-pre class="ui-tl-comment">{!! str_replace(["\r\n", "\r", "\n"], '<br>', e(\Illuminate\Support\Str::limit($act->comment, 280))) !!}</p>
                                                    @endif
                                                    <p class="ui-tl-meta ui-muted">
                                                        {{ $act->user?->name ?? 'System' }}
                                                        @if ($act->schedule_from && $act->type !== 'note') · {{ $act->schedule_from->format('d M, H:i') }} @endif
                                                        @if ($act->type !== 'note') · {{ $act->is_done ? 'Done' : 'Planned' }} @endif
                                                    </p>
                                                </li>
                                            @empty
                                                @if ($c->person_id || $c->lead_id)
                                                    <li class="ui-muted">No activities yet for the linked records.</li>
                                                @endif
                                            @endforelse
                                        </ol>
                                    </section>
                                </div>
                                <div class="ui-side-panel" role="tabpanel" data-side-panel="notes">
                                    <section class="ui-notes-panel">
                                        <h2>Internal Notes <span class="ui-notes-sub">Newest first</span></h2>
                                        <div class="ui-note-form">
                                            <textarea id="ui-notes-input" rows="3" maxlength="5000" placeholder="Write an internal note — only your team sees this" onkeydown="if(event.key==='Enter'&&(event.metaKey||event.ctrlKey)){event.preventDefault();uiSaveSideNote({{ $c->id }});}"></textarea>
                                            <div class="ui-note-form-foot">
                                                <span class="ui-muted">Not sent to the customer · ⌘/Ctrl+Enter</span>
                                                <button type="button" id="ui-notes-save" class="ui-btn ui-btn-note" onclick="uiSaveSideNote({{ $c->id }})">Add note</button>
                                            </div>
                                        </div>
                                        <ol class="ui-notes-list">
                                            @forelse ($chatNotes as $note)
                                                @php $noteAuthor = $note->user?->name ?? 'System'; @endphp
                                                <li class="ui-note-item">
                                                    <div class="ui-note-head">
                                                        <span class="ui-note-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($noteAuthor, 0, 1)) }}</span>
                                                        <span class="ui-note-author">{{ $noteAuthor }}</span>
                                                        <time class="ui-note-time" datetime="{{ $note->created_at?->toIso8601String() }}" title="{{ $note->created_at?->format('d M Y, H:i') }}">{{ $note->created_at?->format('d M, H:i') }} · {{ $note->created_at?->diffForHumans(null, true) }} ago</time>
                                                    </div>
                                                    <div class="ui-note-body">{!! str_replace(["\r\n", "\r", "\n"], '<br>', e($note->comment)) !!}</div>
                                                </li>
                                            @empty
                                                <li class="ui-notes-empty">No internal notes for this conversation yet.</li>
                                            @endforelse
                                        </ol>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            @else
                <!-- Empty State (channels linked, nothing selected / filter returned no chats) -->
                @php $uiAnyFilter = request('status') || request()->boolean('unread') || request('search'); @endphp
                <section class="ui-empty">
                    <span class="ui-avatar" aria-hidden="true">{{ $conversations->total() ? '💬' : '∅' }}</span>
                    @if ($conversations->total())
                        <h2>@lang('unified_inbox::app.inbox.select-conversation')</h2>
                        <p class="ui-muted">@lang('unified_inbox::app.inbox.select-conversation-desc')</p>
                    @elseif ($uiAnyFilter)
                        <h2>No matching conversations</h2>
                        <p class="ui-muted">No chats match the current filters. Your channels are connected — clear filters to see everything.</p>
                        <a class="secondary-button" href="{{ route('admin.unified_inbox.index', request()->only('channel')) }}">Clear filters</a>
                    @else
                        <h2>No conversations yet</h2>
                        <p class="ui-muted">New messages show up here. Use the new-contact control beside search to message a WhatsApp number or a Messenger or Instagram contact.</p>
                    @endif
                </section>
            @endif
        </div>
        @endif
    </div>

    <dialog id="ui-compose" class="ui-compose">
        <form id="ui-compose-form">
            <header class="ui-compose-head">
                <div>
                    <p class="ui-compose-kicker">Add new contact</p>
                    <h2 id="ui-compose-title">New message</h2>
                </div>
                <button type="button" class="ui-compose-close" onclick="uiCloseCompose()" aria-label="Close">×</button>
            </header>
            <div class="ui-compose-body">
                <div id="ui-compose-channels" class="ui-compose-channels" role="radiogroup" aria-label="Channel"></div>
                <p class="ui-compose-help" id="ui-compose-help"></p>
                <label>
                    <span id="ui-compose-recipient-label">Recipient</span>
                    <input id="ui-compose-recipient" name="recipient" autocomplete="off" required>
                </label>
                <label id="ui-compose-name-field">
                    Display name
                    <input id="ui-compose-name" name="name" maxlength="191" placeholder="Shown in the conversation list">
                </label>
                <div id="ui-compose-wa">
                    <label>
                        Approved template
                        <select id="ui-compose-template" required></select>
                    </label>
                    <div id="ui-compose-params" class="ui-compose-params"></div>
                </div>
                <label id="ui-compose-text">
                    Message
                    <textarea id="ui-compose-body" maxlength="4096" placeholder="Write a message"></textarea>
                </label>
                <p class="ui-compose-error" id="ui-compose-error" hidden></p>
            </div>
            <div class="ui-compose-actions">
                <button type="button" class="secondary-button" onclick="uiCloseCompose()">Cancel</button>
                <button type="submit" class="primary-button" id="ui-compose-send">Send</button>
            </div>
        </form>
    </dialog>

    <dialog id="ui-qr" class="ui-qr-dialog">
        <header class="ui-compose-head">
            <div>
                <p class="ui-compose-kicker">Composer</p>
                <h2>Saved quick replies</h2>
            </div>
            <button type="button" class="ui-compose-close" onclick="document.getElementById('ui-qr').close()" aria-label="Close">×</button>
        </header>
        <div class="ui-qr-body">
            <div id="ui-qr-list"></div>
            <form id="ui-qr-form" class="ui-qr-form" onsubmit="event.preventDefault(); uiSaveQuickReply();">
                <input type="hidden" id="ui-qr-id">
                <input id="ui-qr-title" maxlength="120" placeholder="Title (e.g. Greeting)" required>
                <input id="ui-qr-shortcut" maxlength="60" pattern="[A-Za-z0-9_\-]+" placeholder="Shortcut, used as /shortcut (optional)">
                <textarea id="ui-qr-body" rows="3" maxlength="4096" placeholder="Reply text" required></textarea>
                <p class="ui-compose-error" id="ui-qr-error" hidden></p>
                <div class="ui-compose-actions">
                    <button type="button" class="secondary-button" onclick="uiResetQuickReplyForm()">Clear</button>
                    <button type="submit" class="primary-button" id="ui-qr-save">Save reply</button>
                </div>
            </form>
        </div>
    </dialog>

    @pushOnce('scripts')
    <script>
        const uiComposeTemplatesUrl = @js(route('admin.unified_inbox.compose.templates'));
        const uiComposeStoreUrl = @js(route('admin.unified_inbox.compose.store'));
        const uiComposeChannels = @js(collect($channels)->filter(fn ($channel, $key) => ($uiLinked ?? collect())->contains($key))->map(fn ($channel, $key) => ['key' => $key, 'label' => $channel['label'] ?? ucfirst($key)])->values());
        let uiComposeChannel = null;
        let uiComposeSlots = [];

        function uiApplyComposeChannel(channel) {
            uiComposeChannel = channel;
            const whatsapp = channel === 'whatsapp';
            document.getElementById('ui-compose-title').textContent = whatsapp ? 'Message a WhatsApp number' : (channel === 'instagram' ? 'Message an Instagram contact' : 'Message a Messenger contact');
            document.getElementById('ui-compose-help').textContent = whatsapp
                ? 'Send an approved template. Enter the number with the country code and digits only.'
                : 'Meta delivers this only to someone who has already messaged this account. Enter their contact ID.';
            document.getElementById('ui-compose-recipient-label').textContent = whatsapp ? 'WhatsApp number' : 'Contact ID';
            document.getElementById('ui-compose-recipient').placeholder = whatsapp ? '15125550100' : 'Contact ID';
            document.getElementById('ui-compose-wa').hidden = !whatsapp;
            document.getElementById('ui-compose-text').hidden = whatsapp;
            document.getElementById('ui-compose-name-field').hidden = whatsapp;
            document.getElementById('ui-compose-template').required = whatsapp;
            document.getElementById('ui-compose-body').required = !whatsapp;
            document.querySelectorAll('.ui-compose-channel').forEach(button => {
                const selected = button.dataset.channel === channel;
                button.classList.toggle('is-selected', selected);
                button.setAttribute('aria-checked', selected ? 'true' : 'false');
            });
            if (whatsapp) {
                uiLoadTemplates();
            }
        }

        function uiOpenCompose(channel) {
            const dialog = document.getElementById('ui-compose');
            const holder = document.getElementById('ui-compose-channels');
            holder.replaceChildren();
            uiComposeChannels.forEach(item => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'ui-compose-channel';
                button.dataset.channel = item.key;
                button.setAttribute('role', 'radio');
                button.textContent = item.label;
                button.addEventListener('click', () => uiApplyComposeChannel(item.key));
                holder.appendChild(button);
            });
            holder.hidden = uiComposeChannels.length < 2;
            document.getElementById('ui-compose-recipient').value = '';
            document.getElementById('ui-compose-name').value = '';
            document.getElementById('ui-compose-body').value = '';
            document.getElementById('ui-compose-error').hidden = true;
            const fallback = uiComposeChannels[0] ? uiComposeChannels[0].key : null;
            const selected = uiComposeChannels.some(item => item.key === channel) ? channel : fallback;
            if (!selected) {
                return;
            }
            uiApplyComposeChannel(selected);
            if (!dialog.open) {
                dialog.showModal();
            }
            document.getElementById('ui-compose-recipient').focus();
        }

        function uiCloseCompose() {
            document.getElementById('ui-compose').close();
        }

        async function uiLoadTemplates() {
            const select = document.getElementById('ui-compose-template');
            const error = document.getElementById('ui-compose-error');
            select.innerHTML = '<option value="">Loading templates...</option>';
            document.getElementById('ui-compose-params').innerHTML = '';
            uiComposeSlots = [];
            try {
                const response = await fetch(uiComposeTemplatesUrl, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Could not load templates.');
                }
                const templates = (data.templates || []).filter(template => template.name && !template.media);
                select.replaceChildren();
                if (!templates.length) {
                    const empty = document.createElement('option');
                    empty.value = '';
                    empty.textContent = 'No approved text templates';
                    select.appendChild(empty);
                    return;
                }
                templates.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.name + '\n' + template.language;
                    option.textContent = template.name + ' (' + template.language + ')';
                    select.appendChild(option);
                });
                select.dataset.templates = JSON.stringify(templates);
                uiRenderTemplateSlots();
                select.onchange = uiRenderTemplateSlots;
            } catch (err) {
                select.innerHTML = '<option value="">Templates unavailable</option>';
                error.hidden = false;
                error.textContent = err.message;
            }
        }

        function uiRenderTemplateSlots() {
            const select = document.getElementById('ui-compose-template');
            const templates = JSON.parse(select.dataset.templates || '[]');
            const parts = (select.value || '').split('\n');
            const name = parts[0] || '';
            const language = parts[1] || '';
            const template = templates.find(item => item.name === name && item.language === language);
            uiComposeSlots = (template && template.slots || []).filter(slot => !slot.media);
            const holder = document.getElementById('ui-compose-params');
            holder.replaceChildren();
            uiComposeSlots.forEach((slot, index) => {
                const label = document.createElement('label');
                const caption = document.createElement('span');
                caption.textContent = slot.component.charAt(0).toUpperCase() + slot.component.slice(1) + ' variable ' + slot.index;
                const input = document.createElement('input');
                input.setAttribute('data-template-param', String(index));
                input.required = true;
                input.maxLength = 1000;
                input.placeholder = 'Value ' + slot.index;
                label.appendChild(caption);
                label.appendChild(input);
                holder.appendChild(label);
            });
        }

        document.getElementById('ui-compose-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            const button = document.getElementById('ui-compose-send');
            const error = document.getElementById('ui-compose-error');
            error.hidden = true;
            button.disabled = true;
            const payload = {
                channel: uiComposeChannel,
                recipient: document.getElementById('ui-compose-recipient').value.trim(),
                name: document.getElementById('ui-compose-name').value.trim(),
                body: document.getElementById('ui-compose-body').value.trim()
            };
            if (uiComposeChannel === 'whatsapp') {
                const parts = (document.getElementById('ui-compose-template').value || '').split('\n');
                const name = parts[0] || '';
                const language = parts[1] || '';
                payload.template = name || '';
                payload.language = language || '';
                payload.parameters = Array.from(document.querySelectorAll('[data-template-param]')).map(input => input.value);
            }
            try {
                const response = await fetch(uiComposeStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Could not send the message.');
                }
                window.location.assign(data.url);
            } catch (err) {
                error.hidden = false;
                error.textContent = err.message;
                button.disabled = false;
            }
        });
    </script>
    <script>
        function insertCanned(text) {
            const input = document.getElementById('message-input');
            if (!input) return;
            input.value = text;
            uiAutoGrow(input);
            input.focus();
        }

        // WhatsApp-style composer: 1 line, grows to ~3 lines, then scrolls.
        function uiAutoGrow(el) {
            if (!el) return;
            const cs = getComputedStyle(el);
            const line = parseFloat(cs.lineHeight) || 20;
            const pad = parseFloat(cs.paddingTop) + parseFloat(cs.paddingBottom);
            const max = line * 3 + pad;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, max) + 'px';
            el.style.overflowY = el.scrollHeight > max ? 'auto' : 'hidden';
        }

        async function sendMessage(conversationId) {
            const input = document.getElementById('message-input');
            const button = document.getElementById('send-button');
            const body = input.value.trim();

            if (uiPendingFile) return uiSendAttachment(conversationId);
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
                    uiAutoGrow(input);
                    const history = document.getElementById('chat-messages-container');
                    const article = document.createElement('article');
                    article.className = 'ui-message ui-message-outbound';
                    const now = new Date();
                    const hhmm = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
                    article.innerHTML = `
                        <p class="whitespace-pre-wrap break-words">${escapeHtml(body)}</p>
                        <div class="ui-message-meta">
                            <span>${hhmm}</span>
                            ${uiTickSvg('sent')}
                        </div>
                    `;
                    history.querySelector('.ui-history-empty')?.remove();
                    history.appendChild(article);
                    history.scrollTop = history.scrollHeight;
                } else {
                    alert(data.error || 'Failed to send message.');
                    if (data.window_closed) window.location.reload();
                }
                if (response.ok && data.success && data.url) window.location.assign(data.url);
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


        const UI_BASE = "{{ url(config('app.admin_path') . '/unified-inbox') }}";
        const UI_CONV_ID = {{ $activeConversation?->id ?? 'null' }};
        let uiComposerMode = 'reply';

        // ---- AI reply suggestions (click-to-insert only, never auto-sent) ----
        const uiAiCache = {};
        async function uiLoadAiSuggestions(force) {
            const box = document.getElementById('ui-ai');
            const list = document.getElementById('ui-ai-list');
            const btn = document.getElementById('ui-ai-refresh');
            if (!box || !list || !UI_CONV_ID) return;
            const render = (data) => {
                list.innerHTML = '';
                const sugg = (data && data.suggestions) || [];
                if (sugg.length) {
                    box.dataset.state = 'ready';
                    sugg.forEach(text => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'ui-ai-chip';
                        b.title = 'Insert into reply (not sent)';
                        b.textContent = text;
                        b.onclick = () => { if (uiComposerMode !== 'reply') uiSetComposerMode('reply'); insertCanned(text); };
                        list.appendChild(b);
                    });
                    return;
                }
                const msg = document.createElement('span');
                msg.className = 'ui-muted';
                if (data && data.configured === false) {
                    box.dataset.state = 'off';
                    msg.textContent = 'AI not configured — add UNIFIED_INBOX_AI_API_KEY or enable Magic AI in Configuration.';
                } else {
                    box.dataset.state = data && data.error ? 'error' : 'empty';
                    msg.textContent = (data && data.error) || 'No suggestions for this chat yet.';
                }
                list.appendChild(msg);
            };
            if (!force && uiAiCache[UI_CONV_ID]) return render(uiAiCache[UI_CONV_ID]);
            box.dataset.state = 'loading';
            list.innerHTML = '<span class="ui-muted">Thinking…</span>';
            if (btn) btn.disabled = true;
            try {
                const r = await fetch(UI_BASE + '/conversations/' + UI_CONV_ID + '/ai-suggestions', { headers: { 'Accept': 'application/json' } });
                let data = {};
                try { data = await r.json(); } catch (e) {}
                if (r.status === 429) data = { suggestions: [], error: 'Too many requests — try again in a minute.' };
                if (!r.ok && !data.error) data.error = 'Could not get suggestions right now.';
                if (r.ok && data.suggestions && data.suggestions.length) uiAiCache[UI_CONV_ID] = data;
                render(data);
            } catch (e) {
                render({ suggestions: [], error: 'Could not get suggestions right now.' });
            } finally {
                if (btn) btn.disabled = false;
            }
        }
        document.addEventListener('DOMContentLoaded', () => uiLoadAiSuggestions(false));

        async function uiPost(path, payload) {
            const response = await fetch(UI_BASE + path, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept': 'application/json' },
                body: JSON.stringify(payload || {})
            });
            let data = {};
            try { data = await response.json(); } catch (e) {}
            return { ok: response.ok && data.success !== false, data };
        }

        function uiSetComposerMode(mode) {
            uiComposerMode = mode;
            document.querySelectorAll('[data-composer-mode]').forEach(t => t.setAttribute('aria-selected', t.dataset.composerMode === mode ? 'true' : 'false'));
            const composer = document.querySelector('.ui-composer');
            composer?.classList.toggle('is-note', mode === 'note');
            const input = document.getElementById('message-input');
            const btn = document.querySelector('#send-button span');
            const hint = document.getElementById('composer-hint');
            if (input) input.placeholder = mode === 'note' ? 'Write an internal note — only your team sees this' : "@lang('unified_inbox::app.inbox.type-message')";
            if (btn) btn.textContent = mode === 'note' ? 'Add note' : 'Send';
            if (hint) hint.textContent = mode === 'note' ? 'Saved as a Krayin note on the linked contact/lead · not sent to the customer' : 'Enter to send · Shift+Enter for new line';
            const locked = input?.dataset.replyLocked === '1';
            if (input && locked) input.readOnly = mode !== 'note';
            const sendBtn = document.getElementById('send-button');
            if (sendBtn && sendBtn.dataset.replyLocked === '1') sendBtn.disabled = mode !== 'note';
            input?.focus();
        }

        function uiSubmitComposer(id) {
            return uiComposerMode === 'note' ? uiSaveNote(id) : sendMessage(id);
        }

        async function uiSaveNote(id) {
            const input = document.getElementById('message-input');
            const button = document.getElementById('send-button');
            const comment = input.value.trim();
            if (!comment) return;
            button.disabled = true;
            try {
                const { ok, data } = await uiPost('/conversations/' + id + '/notes', { comment });
                if (ok) { sessionStorage.setItem('ui-side-tab', 'work'); window.location.reload(); }
                else alert(data.message || data.error || 'Could not save note.');
            } catch (e) { alert('Network error while saving note.'); }
            finally { button.disabled = false; }
        }

        async function uiSaveSideNote(id) {
            const input = document.getElementById('ui-notes-input');
            const button = document.getElementById('ui-notes-save');
            const comment = input.value.trim();
            if (!comment) return;
            button.disabled = true;
            try {
                const { ok, data } = await uiPost('/conversations/' + id + '/notes', { comment });
                if (ok) { sessionStorage.setItem('ui-side-tab', 'notes'); window.location.reload(); }
                else alert(data.message || data.error || 'Could not save note.');
            } catch (e) { alert('Network error while saving note.'); }
            finally { button.disabled = false; }
        }

        function uiOpenCrmSearch(type) {
            document.querySelectorAll('[data-crm-search]').forEach(box => {
                const show = box.dataset.crmSearch === type && box.hidden;
                box.hidden = !show;
                if (show) { const i = box.querySelector('input'); i.value = ''; i.focus(); uiCrmSearch(i, type); }
            });
        }

        let uiSearchTimer = null;
        function uiCrmSearch(input, type) {
            clearTimeout(uiSearchTimer);
            uiSearchTimer = setTimeout(async () => {
                const list = input.parentElement.querySelector('.ui-crm-results');
                list.innerHTML = '<li class="ui-muted">Searching…</li>';
                try {
                    const r = await fetch(UI_BASE + '/crm-search?type=' + type + '&q=' + encodeURIComponent(input.value.trim()), { headers: { 'Accept': 'application/json' } });
                    const { data } = await r.json();
                    list.innerHTML = '';
                    if (!data || !data.length) { list.innerHTML = '<li class="ui-muted">No matches</li>'; return; }
                    data.forEach(item => {
                        const li = document.createElement('li');
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.innerHTML = '<strong>' + escapeHtml(item.label) + '</strong>' + (item.sub ? '<small>' + escapeHtml(item.sub) + '</small>' : '');
                        b.onclick = () => uiLinkRecord(UI_CONV_ID, type, item.id);
                        li.appendChild(b);
                        list.appendChild(li);
                    });
                } catch (e) { list.innerHTML = '<li class="ui-muted">Search failed</li>'; }
            }, 250);
        }

        async function uiLinkRecord(id, type, recordId) {
            if (recordId === null && !confirm('Unlink this ' + type + ' from the conversation? The CRM record itself is not deleted.')) return;
            const { ok, data } = await uiPost('/conversations/' + id + '/link-record', { type, record_id: recordId });
            if (ok) { sessionStorage.setItem('ui-side-tab', 'crm'); window.location.reload(); }
            else alert(data.message || data.error || 'Could not update link.');
        }

        async function createOrganizationFromChat(id) {
            const name = prompt('Organization name');
            if (!name || !name.trim()) return;
            const { ok, data } = await uiPost('/conversations/' + id + '/organization', { name: name.trim() });
            if (ok) { sessionStorage.setItem('ui-side-tab', 'crm'); window.location.reload(); }
            else alert(data.message || data.error || 'Could not create organization.');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const tab = sessionStorage.getItem('ui-side-tab');
            if (tab) {
                sessionStorage.removeItem('ui-side-tab');
                document.querySelector('.ui-side-radio[data-side-tab="' + tab + '"]')?.click();
            }
        });


        // ---------- Phase 1: Meta-style inbox helpers ----------
        const UI_CSRF = "{{ csrf_token() }}";
        const uiQuickReplies = @js(($quickReplies ?? collect())->values());

        function uiTickSvg(status) {
            if (status === 'failed') return '<span class="ui-tick ui-tick-failed" title="Failed">!</span>';
            if (status === 'sent') return '<span class="ui-tick ui-tick-sent" title="Sent"><svg viewBox="0 0 16 11" width="15" height="11" fill="currentColor"><path d="M11.07.65 4.6 7.12 1.93 4.45.87 5.51l3.73 3.73L12.13 1.7z"/></svg></span>';
            return '<span class="ui-tick ui-tick-pending" title="Sending"><svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><path d="M8 5v3.5l2 1.5"/></svg></span>';
        }

        async function uiSetFlag(id, flag, value) {
            const { ok, data } = await uiPost('/conversations/' + id + '/flags', { [flag]: value });
            if (ok) window.location.reload();
            else alert(data.message || data.error || 'Could not update the conversation.');
        }

        function uiCurrentLabels() {
            try { return JSON.parse(document.getElementById('ui-label-list')?.dataset.labels || '[]'); } catch (e) { return []; }
        }

        async function uiSaveLabels(id, labels) {
            const { ok, data } = await uiPost('/conversations/' + id + '/labels', { labels });
            if (ok) window.location.reload();
            else alert(data.message || data.error || 'Could not update labels.');
        }

        function uiAddLabel(id, value) {
            const label = (value || '').trim();
            if (!label) return;
            const labels = uiCurrentLabels();
            if (!labels.includes(label)) labels.push(label);
            uiSaveLabels(id, labels);
        }

        function uiRemoveLabel(id, label) {
            uiSaveLabels(id, uiCurrentLabels().filter(l => l !== label));
        }

        // 24h window countdown (re-renders every minute; reloads when it expires so the composer locks).
        (function () {
            const box = document.getElementById('ui-window');
            const left = document.getElementById('ui-window-left');
            if (!box || !left || !box.dataset.ends) return;
            const ends = new Date(box.dataset.ends).getTime();
            const tick = () => {
                const ms = ends - Date.now();
                if (ms <= 0) { window.location.reload(); return; }
                const h = Math.floor(ms / 3600000), m = Math.floor((ms % 3600000) / 60000);
                left.textContent = h + 'h ' + String(m).padStart(2, '0') + 'm';
                if (ms < 3600000) box.classList.replace('ui-window-open', 'ui-window-closed');
            };
            tick();
            setInterval(tick, 30000);
        })();

        function uiOpenTemplateFor(recipient, name) {
            uiOpenCompose('whatsapp');
            document.getElementById('ui-compose-recipient').value = String(recipient || '').replace(/\D/g, '');
            document.getElementById('ui-compose-name').value = name || '';
        }

        // Emoji picker (no dependency).
        const UI_EMOJI = ['😀','😁','😂','🙂','😉','😊','😍','🤝','👍','👎','🙏','👏','🎉','✅','❌','⚠️','📞','📧','📅','⏰','💰','📦','🚚','🛒','💡','🔥','⭐','❤️','👌','🤔','😅','😢','🙌','👋','📍','📝','🔗','💬','✨','🎁'];
        function uiToggleEmoji(event) {
            event.stopPropagation();
            const pop = document.getElementById('ui-emoji');
            if (!pop.childElementCount) {
                UI_EMOJI.forEach(e => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = e;
                    b.onclick = () => uiInsertAtCursor(e);
                    pop.appendChild(b);
                });
            }
            pop.hidden = !pop.hidden;
        }
        document.addEventListener('click', e => {
            const pop = document.getElementById('ui-emoji');
            if (pop && !pop.hidden && !pop.contains(e.target)) pop.hidden = true;
        });

        function uiInsertAtCursor(text) {
            const input = document.getElementById('message-input');
            if (!input || input.readOnly) return;
            const start = input.selectionStart ?? input.value.length, end = input.selectionEnd ?? input.value.length;
            input.value = input.value.slice(0, start) + text + input.value.slice(end);
            input.selectionStart = input.selectionEnd = start + text.length;
            uiAutoGrow(input);
            input.focus();
        }

        // '/' quick reply shortcut.
        let uiSlashIndex = 0, uiSlashItems = [];
        function uiSlashCheck(input) {
            const pop = document.getElementById('ui-slash');
            const m = /(^|\s)\/([\w-]*)$/.exec(input.value.slice(0, input.selectionStart));
            if (!m || !uiQuickReplies.length) { pop.hidden = true; return; }
            const q = m[2].toLowerCase();
            uiSlashItems = uiQuickReplies.filter(r => !q || (r.shortcut || '').toLowerCase().startsWith(q) || r.title.toLowerCase().includes(q)).slice(0, 8);
            if (!uiSlashItems.length) { pop.hidden = true; return; }
            uiSlashIndex = Math.min(uiSlashIndex, uiSlashItems.length - 1);
            pop.replaceChildren();
            uiSlashItems.forEach((r, i) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = i === uiSlashIndex ? 'is-active' : '';
                const t = document.createElement('strong'); t.textContent = (r.shortcut ? '/' + r.shortcut + ' · ' : '') + r.title;
                const sm = document.createElement('small'); sm.textContent = r.body;
                b.append(t, sm);
                b.onmousedown = ev => { ev.preventDefault(); uiApplySlash(r); };
                pop.appendChild(b);
            });
            pop.hidden = false;
        }
        function uiApplySlash(reply) {
            const input = document.getElementById('message-input');
            const before = input.value.slice(0, input.selectionStart).replace(/\/[\w-]*$/, '');
            input.value = before + reply.body + input.value.slice(input.selectionStart);
            document.getElementById('ui-slash').hidden = true;
            uiAutoGrow(input);
            input.focus();
        }
        function uiSlashKey(event) {
            const pop = document.getElementById('ui-slash');
            if (!pop || pop.hidden) return false;
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                uiSlashIndex = (uiSlashIndex + (event.key === 'ArrowDown' ? 1 : uiSlashItems.length - 1)) % uiSlashItems.length;
                uiSlashCheck(event.target);
                return true;
            }
            if (event.key === 'Enter' || event.key === 'Tab') { event.preventDefault(); uiApplySlash(uiSlashItems[uiSlashIndex]); return true; }
            if (event.key === 'Escape') { pop.hidden = true; return true; }
            return false;
        }

        // Quick replies manager.
        function uiOpenQuickReplies() {
            uiRenderQuickReplies();
            uiResetQuickReplyForm();
            document.getElementById('ui-qr').showModal();
        }
        function uiRenderQuickReplies() {
            const list = document.getElementById('ui-qr-list');
            list.replaceChildren();
            if (!uiQuickReplies.length) {
                const p = document.createElement('p'); p.className = 'ui-muted';
                p.textContent = 'No saved replies yet. The default Greeting / Request received / Quote inquiry chips are shown until you add one.';
                list.appendChild(p);
                return;
            }
            uiQuickReplies.forEach(r => {
                const row = document.createElement('div'); row.className = 'ui-qr-row';
                const info = document.createElement('div');
                const t = document.createElement('strong'); t.textContent = r.title + (r.shortcut ? '  /' + r.shortcut : '');
                const b = document.createElement('p'); b.textContent = r.body;
                info.append(t, b);
                const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'ui-link-btn'; edit.textContent = 'Edit';
                edit.onclick = () => {
                    document.getElementById('ui-qr-id').value = r.id;
                    document.getElementById('ui-qr-title').value = r.title;
                    document.getElementById('ui-qr-shortcut').value = r.shortcut || '';
                    document.getElementById('ui-qr-body').value = r.body;
                };
                const del = document.createElement('button'); del.type = 'button'; del.className = 'ui-link-btn ui-link-danger'; del.textContent = 'Delete';
                del.onclick = () => uiDeleteQuickReply(r.id);
                row.append(info, edit, del);
                list.appendChild(row);
            });
        }
        function uiResetQuickReplyForm() {
            ['ui-qr-id', 'ui-qr-title', 'ui-qr-shortcut', 'ui-qr-body'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('ui-qr-error').hidden = true;
        }
        async function uiQrRequest(method, path, payload) {
            const r = await fetch(UI_BASE + path, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': UI_CSRF },
                body: payload ? JSON.stringify(payload) : undefined
            });
            let data = {}; try { data = await r.json(); } catch (e) {}
            return { ok: r.ok && data.success !== false, data };
        }
        async function uiSaveQuickReply() {
            const id = document.getElementById('ui-qr-id').value;
            const payload = {
                title: document.getElementById('ui-qr-title').value.trim(),
                shortcut: document.getElementById('ui-qr-shortcut').value.trim() || null,
                body: document.getElementById('ui-qr-body').value.trim()
            };
            const { ok, data } = await uiQrRequest(id ? 'PUT' : 'POST', '/quick-replies' + (id ? '/' + id : ''), payload);
            if (ok) { window.location.reload(); return; }
            const err = document.getElementById('ui-qr-error');
            err.hidden = false;
            err.textContent = data.message || data.error || 'Could not save the reply.';
        }
        async function uiDeleteQuickReply(id) {
            if (!confirm('Delete this saved reply?')) return;
            const { ok, data } = await uiQrRequest('DELETE', '/quick-replies/' + id);
            if (ok) window.location.reload();
            else alert(data.message || data.error || 'Could not delete the reply.');
        }

        // Attachments (WhatsApp Cloud API media).
        let uiPendingFile = null;
        function uiPickAttachment(input) {
            const file = input.files && input.files[0];
            if (!file) return;
            if (file.size > 16 * 1024 * 1024) { alert('Files up to 16 MB can be sent.'); input.value = ''; return; }
            uiPendingFile = file;
            document.getElementById('ui-attach-name').textContent = file.name + ' · ' + Math.ceil(file.size / 1024) + ' KB (message text becomes the caption)';
            document.getElementById('ui-attach-preview').hidden = false;
        }
        function uiClearAttachment() {
            uiPendingFile = null;
            const input = document.getElementById('ui-attach-input');
            if (input) input.value = '';
            document.getElementById('ui-attach-preview').hidden = true;
        }
        async function uiSendAttachment(id) {
            const input = document.getElementById('message-input');
            const button = document.getElementById('send-button');
            const form = new FormData();
            form.append('file', uiPendingFile);
            form.append('caption', input.value.trim());
            button.disabled = true;
            try {
                const r = await fetch(UI_BASE + '/conversations/' + id + '/attachments', {
                    method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': UI_CSRF }, body: form
                });
                let data = {}; try { data = await r.json(); } catch (e) {}
                if (r.ok && data.success) { window.location.reload(); return; }
                alert(data.error || data.message || 'Could not send the attachment.');
            } catch (e) {
                alert('Network error while sending the attachment.');
            } finally {
                button.disabled = false;
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
