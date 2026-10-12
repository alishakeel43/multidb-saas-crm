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
                                <button type="button" class="ui-composer-tab" role="tab" aria-selected="true" data-composer-mode="reply" onclick="uiSetComposerMode('reply')">Reply</button>
                                <button type="button" class="ui-composer-tab" role="tab" aria-selected="false" data-composer-mode="note" onclick="uiSetComposerMode('note')" title="Visible to your team only — never sent to the customer">Internal Note</button>
                                <span class="ui-muted ui-composer-hint" id="composer-hint">Enter to send · Shift+Enter for new line</span>
                            </div>
                            <div class="ui-suggest-row">
                            <div class="ui-quick">
                                <button type="button" onclick="insertCanned('Hello! Thank you for contacting us. How can I assist you today?')" class="ui-chip">Greeting</button>
                                <button type="button" onclick="insertCanned('We have received your requirement. A team member will follow up shortly.')" class="ui-chip">Request received</button>
                                <button type="button" onclick="insertCanned('Could you please share your requirements so we can prepare a tailored quote?')" class="ui-chip">Quote inquiry</button>
                            </div>
                            <div class="ui-ai" id="ui-ai" data-state="loading" aria-live="polite">
                                <div class="ui-ai-head">
                                    <span class="ui-ai-label" title="AI suggestions — click to insert (never auto-sent)"><span aria-hidden="true">✨</span> AI</span>
                                    <button type="button" class="ui-ai-refresh" id="ui-ai-refresh" onclick="uiLoadAiSuggestions(true)" title="Get new suggestions" aria-label="Refresh AI suggestions">↻</button>
                                </div>
                                <div class="ui-ai-list" id="ui-ai-list"><span class="ui-muted">Thinking…</span></div>
                            </div>
                            </div>
                            <form id="message-form" onsubmit="event.preventDefault(); uiSubmitComposer({{ $activeConversation->id }});">
                                <textarea
                                    id="message-input"
                                    rows="1"
                                    oninput="uiAutoGrow(this)"
                                    placeholder="@lang('unified_inbox::app.inbox.type-message')"
                                    onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); uiSubmitComposer({{ $activeConversation->id }}); }"
                                ></textarea>
                                <button type="submit" id="send-button" class="primary-button"><span>Send</span></button>
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
                                                        <p v-pre class="ui-tl-comment">{{ \Illuminate\Support\Str::limit($act->comment, 280) }}</p>
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
                                                    <div class="ui-note-body">{{ $note->comment }}</div>
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
                        <p class="ui-muted">Your channels are connected. New messages will show up here automatically.</p>
                    @endif
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
                            <span class="ui-msg-status ui-msg-status-sent" title="Sent">✓</span>
                        </div>
                    `;
                    history.querySelector('.ui-history-empty')?.remove();
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
