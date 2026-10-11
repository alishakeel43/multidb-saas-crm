<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.inbox.title')
    </x-slot>

    <!-- Master Unified Inbox Container -->
    <div class="-m-4 flex h-[calc(100vh-65px)] overflow-hidden bg-slate-50 dark:bg-slate-950 font-sans" id="unified-inbox-app">

        <!-- ========================================================================= -->
        <!-- PANEL 1: CONVERSATIONS SIDEBAR (360px)                                    -->
        <!-- ========================================================================= -->
        <div class="flex w-[360px] flex-shrink-0 flex-col border-r border-slate-200/80 bg-white dark:border-slate-800/80 dark:bg-slate-900 select-none">
            
            <!-- Sidebar Header & Controls -->
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white shadow-sm shadow-blue-500/30">
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">
                                @lang('unified_inbox::app.inbox.title')
                            </h2>
                            <p class="text-[11px] font-medium text-slate-400">Omnichannel Hub</p>
                        </div>
                    </div>

                    <!-- Prominent Connect Channels Button -->
                    <a
                        href="{{ route('admin.unified_inbox.channels.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 active:scale-95"
                        title="Connect WhatsApp, Messenger, or Instagram Channels"
                    >
                        <svg class="h-3.5 w-3.5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Connect Channels</span>
                    </a>
                </div>

                @php
                    $waConn = $connections->firstWhere('channel', 'whatsapp');
                    $msgConn = $connections->firstWhere('channel', 'messenger');
                    $igConn = $connections->firstWhere('channel', 'instagram');
                    $connectedCount = $connections->where('status', 'connected')->count();
                @endphp

                <!-- Channel Connection Status Bar -->
                <div class="flex items-center justify-between rounded-xl bg-slate-50/90 px-3 py-2 border border-slate-200/70 text-xs dark:bg-slate-800/60 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <!-- WhatsApp status -->
                        <a
                            href="{{ route('admin.unified_inbox.channels.index') }}"
                            class="inline-flex items-center gap-1 transition hover:opacity-80"
                            title="{{ $waConn ? 'WhatsApp Connected: ' . $waConn->name : 'WhatsApp Disconnected - Click to Connect' }}"
                        >
                            <span class="h-2 w-2 rounded-full {{ $waConn ? 'bg-[#25D366] shadow-sm shadow-emerald-400' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                            <span class="text-[11px] font-semibold {{ $waConn ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500' }}">WA</span>
                        </a>

                        <span class="text-slate-300 dark:text-slate-700 text-[10px]">&bull;</span>

                        <!-- Messenger status -->
                        <a
                            href="{{ route('admin.unified_inbox.channels.index') }}"
                            class="inline-flex items-center gap-1 transition hover:opacity-80"
                            title="{{ $msgConn ? 'Messenger Connected: ' . $msgConn->name : 'Messenger Disconnected - Click to Connect' }}"
                        >
                            <span class="h-2 w-2 rounded-full {{ $msgConn ? 'bg-[#0084FF] shadow-sm shadow-blue-400' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                            <span class="text-[11px] font-semibold {{ $msgConn ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500' }}">Messenger</span>
                        </a>

                        <span class="text-slate-300 dark:text-slate-700 text-[10px]">&bull;</span>

                        <!-- Instagram status -->
                        <a
                            href="{{ route('admin.unified_inbox.channels.index') }}"
                            class="inline-flex items-center gap-1 transition hover:opacity-80"
                            title="{{ $igConn ? 'Instagram Connected: ' . $igConn->name : 'Instagram Disconnected - Click to Connect' }}"
                        >
                            <span class="h-2 w-2 rounded-full {{ $igConn ? 'bg-[#E1306C] shadow-sm shadow-pink-400' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                            <span class="text-[11px] font-semibold {{ $igConn ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500' }}">Instagram</span>
                        </a>
                    </div>

                    <a
                        href="{{ route('admin.unified_inbox.channels.index') }}"
                        class="text-[11px] font-bold text-blue-600 hover:text-blue-700 hover:underline dark:text-blue-400 inline-flex items-center gap-0.5"
                    >
                        <span>Manage</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                @if ($connectedCount === 0)
                    <!-- Notice if no channel is connected yet -->
                    <div class="flex items-center justify-between rounded-xl bg-amber-50 p-2.5 border border-amber-200/80 text-amber-900 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-200">
                        <div class="flex items-center gap-2">
                            <span class="text-sm">⚠️</span>
                            <div>
                                <p class="text-[11px] font-bold leading-tight">No Channels Connected</p>
                                <p class="text-[10px] text-amber-700 dark:text-amber-400">Connect to start receiving chats</p>
                            </div>
                        </div>
                        <a
                            href="{{ route('admin.unified_inbox.channels.index') }}"
                            class="rounded-lg bg-amber-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm hover:bg-amber-700 transition"
                        >
                            Connect &rarr;
                        </a>
                    </div>
                @endif

                <!-- Channel Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs no-scrollbar">
                    <a
                        href="{{ route('admin.unified_inbox.index', ['status' => $statusFilter, 'search' => $search]) }}"
                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 font-medium transition-all {{ empty($channelFilter) ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}"
                    >
                        <span>All</span>
                        <span class="rounded-full px-1 text-[10px] {{ empty($channelFilter) ? 'bg-slate-700 text-white dark:bg-slate-200 dark:text-slate-800' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">{{ $conversations->count() }}</span>
                    </a>
                    <a
                        href="{{ route('admin.unified_inbox.index', ['channel' => 'whatsapp', 'status' => $statusFilter, 'search' => $search]) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-medium transition-all {{ $channelFilter === 'whatsapp' ? 'bg-[#25D366] text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' }}"
                    >
                        <span class="h-2 w-2 rounded-full bg-[#25D366] {{ $channelFilter === 'whatsapp' ? 'bg-white' : '' }}"></span>
                        <span>WhatsApp</span>
                    </a>
                    <a
                        href="{{ route('admin.unified_inbox.index', ['channel' => 'messenger', 'status' => $statusFilter, 'search' => $search]) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-medium transition-all {{ $channelFilter === 'messenger' ? 'bg-[#0084FF] text-white shadow-sm' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300' }}"
                    >
                        <span class="h-2 w-2 rounded-full bg-[#0084FF] {{ $channelFilter === 'messenger' ? 'bg-white' : '' }}"></span>
                        <span>Messenger</span>
                    </a>
                    <a
                        href="{{ route('admin.unified_inbox.index', ['channel' => 'instagram', 'status' => $statusFilter, 'search' => $search]) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-medium transition-all {{ $channelFilter === 'instagram' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-sm' : 'bg-pink-50 text-pink-700 hover:bg-pink-100 dark:bg-pink-950/40 dark:text-pink-300' }}"
                    >
                        <span class="h-2 w-2 rounded-full bg-[#E1306C] {{ $channelFilter === 'instagram' ? 'bg-white' : '' }}"></span>
                        <span>Instagram</span>
                    </a>
                </div>

                <!-- Search Input & Status Selector -->
                <form action="{{ route('admin.unified_inbox.index') }}" method="GET" class="flex gap-2">
                    @if ($channelFilter)
                        <input type="hidden" name="channel" value="{{ $channelFilter }}">
                    @endif
                    <div class="relative flex-1">
                        <svg class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search chats, numbers..."
                            class="w-full rounded-lg border border-slate-200 bg-slate-50/70 py-1.5 pl-8 pr-7 text-xs text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-slate-800 dark:bg-slate-800/80 dark:text-white"
                        >
                        @if ($search)
                            <a href="{{ route('admin.unified_inbox.index', ['channel' => $channelFilter, 'status' => $statusFilter]) }}" class="absolute right-2 top-2 text-xs text-slate-400 hover:text-slate-600">&times;</a>
                        @endif
                    </div>
                    <select
                        name="status"
                        onchange="this.form.submit()"
                        class="rounded-lg border border-slate-200 bg-slate-50/70 px-2 py-1.5 text-xs font-semibold text-slate-700 transition focus:border-blue-500 focus:outline-none dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-300"
                    >
                        <option value="">All Status</option>
                        <option value="open" {{ $statusFilter === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="closed" {{ $statusFilter === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </form>
            </div>

            <!-- Conversation List Scroll Feed -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100/80 dark:divide-slate-800/60">
                @forelse ($conversations as $conversation)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conversation->id;
                        $channelColor = match($conversation->channel) {
                            'whatsapp' => '#25D366',
                            'messenger' => '#0084FF',
                            'instagram' => '#E1306C',
                            default => '#64748B'
                        };
                    @endphp
                    <a
                        href="{{ route('admin.unified_inbox.index', ['conversation_id' => $conversation->id, 'channel' => $channelFilter, 'status' => $statusFilter, 'search' => $search]) }}"
                        class="group relative flex items-start gap-3 p-3.5 transition-all duration-150 {{ $isActive ? 'bg-blue-50/80 dark:bg-blue-950/40 border-l-[3px] border-blue-600' : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/50' }}"
                    >
                        <!-- Avatar with channel badge overlay -->
                        <div class="relative flex-shrink-0">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-tr from-slate-200 to-slate-100 text-xs font-bold text-slate-700 shadow-sm dark:from-slate-700 dark:to-slate-800 dark:text-slate-200 ring-1 ring-black/5">
                                {{ strtoupper(substr($conversation->customer_name, 0, 2)) }}
                            </div>
                            <span
                                class="absolute -bottom-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-slate-900"
                                style="background-color: {{ $channelColor }}"
                            >
                                @if ($conversation->channel === 'whatsapp') W
                                @elseif ($conversation->channel === 'messenger') M
                                @else I
                                @endif
                            </span>
                        </div>

                        <!-- Conversation Details -->
                        <div class="flex flex-1 flex-col overflow-hidden">
                            <div class="flex items-baseline justify-between gap-1">
                                <span class="truncate text-[13px] font-bold text-slate-900 dark:text-slate-100 group-hover:text-blue-600 transition-colors">
                                    {{ $conversation->customer_name }}
                                </span>
                                <span class="flex-shrink-0 text-[10px] font-medium text-slate-400">
                                    {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans(null, true) : '' }}
                                </span>
                            </div>

                            <div class="mt-1 flex items-center justify-between gap-1">
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400 leading-normal">
                                    {{ $conversation->last_message_preview ?: 'No messages yet' }}
                                </p>
                                @if ($conversation->unread_count > 0)
                                    <span class="flex h-4 min-w-[16px] items-center justify-center rounded-full bg-blue-600 px-1.5 text-[10px] font-bold text-white shadow-sm shadow-blue-500/40">
                                        {{ $conversation->unread_count }}
                                    </span>
                                @endif
                            </div>

                            <!-- Lead Tag Badge if linked -->
                            @if ($conversation->lead)
                                <div class="mt-1.5 flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 border border-amber-200/60 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        {{ Str::limit($conversation->lead->title, 20) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="flex flex-col items-center justify-center p-8 text-center text-slate-400">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 mb-3 shadow-sm">
                            <svg class="h-6 w-6 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No conversations yet</p>
                        <p class="mt-1 text-[11px] text-slate-400 max-w-[200px]">Incoming WhatsApp, Messenger, and Instagram inquiries will stream right here.</p>
                        <a
                            href="{{ route('admin.unified_inbox.channels.index') }}"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 transition"
                        >
                            <span>+ Connect Channels</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PANEL 2: ACTIVE CHAT THREAD (FLEX-1)                                      -->
        <!-- ========================================================================= -->
        <div class="flex flex-1 flex-col overflow-hidden bg-white dark:bg-slate-900 border-r border-slate-200/80 dark:border-slate-800/80">
            @if ($activeConversation)
                <!-- Chat Canvas Header -->
                <div class="flex items-center justify-between border-b border-slate-200/80 px-6 py-3.5 dark:border-slate-800 bg-white dark:bg-slate-900 z-10">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-sm font-bold text-white shadow-md shadow-blue-500/20">
                            {{ strtoupper(substr($activeConversation->customer_name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $activeConversation->customer_name }}
                                </h3>
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold text-white shadow-sm" style="background-color: {{ match($activeConversation->channel) { 'whatsapp' => '#25D366', 'messenger' => '#0084FF', 'instagram' => '#E1306C', default => '#64748B' } }}">
                                    {{ ucfirst($activeConversation->channel) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] text-slate-400 font-mono">
                                <span>{{ $activeConversation->external_id }}</span>
                                <span>&bull;</span>
                                <span class="capitalize text-slate-500 font-sans font-medium">{{ $activeConversation->status }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Header Action Tools -->
                    <div class="flex items-center gap-3">
                        <!-- Status Picker -->
                        <div class="flex items-center gap-1.5">
                            <select
                                id="status-selector"
                                onchange="updateConversationStatus({{ $activeConversation->id }}, this.value)"
                                class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-white focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="open" {{ $activeConversation->status === 'open' ? 'selected' : '' }}>🟢 Open</option>
                                <option value="pending" {{ $activeConversation->status === 'pending' ? 'selected' : '' }}>🟡 Pending</option>
                                <option value="closed" {{ $activeConversation->status === 'closed' ? 'selected' : '' }}>⚪ Closed / Resolved</option>
                            </select>
                        </div>

                        <!-- Assignee Picker -->
                        <div class="flex items-center gap-1.5">
                            <select
                                id="agent-selector"
                                onchange="assignConversationAgent({{ $activeConversation->id }}, this.value)"
                                class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-white focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="">👤 Unassigned</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ $activeConversation->user_id === $user->id ? 'selected' : '' }}>
                                        👤 {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Toggle CRM Sidebar -->
                        <button
                            type="button"
                            onclick="toggleCrmSidebar()"
                            class="rounded-lg border border-slate-200 bg-slate-50 p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            title="Toggle CRM Sidebar"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages Feed Scroll Container -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/60 dark:bg-slate-950/60" id="messages-container">
                    
                    <!-- Date Divider Badge -->
                    <div class="flex items-center justify-center my-2">
                        <span class="rounded-full bg-slate-200/70 px-3 py-1 text-[10px] font-semibold tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400 shadow-sm">
                            Conversation History
                        </span>
                    </div>

                    @forelse ($activeMessages as $message)
                        @php
                            $isOutbound = $message->direction === 'outbound';
                        @endphp
                        <div class="flex flex-col {{ $isOutbound ? 'items-end' : 'items-start' }}">
                            <div class="flex items-end gap-2 max-w-[75%] lg:max-w-[65%]">
                                @if (! $isOutbound)
                                    <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-slate-200 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                        {{ strtoupper(substr($activeConversation->customer_name, 0, 1)) }}
                                    </div>
                                @endif

                                <div class="relative group rounded-2xl px-4 py-2.5 text-xs shadow-sm transition-shadow {{ $isOutbound ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-br-none shadow-blue-500/10' : 'bg-white text-slate-800 rounded-bl-none border border-slate-200/80 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-100 shadow-slate-200/40' }}">
                                    <p class="whitespace-pre-wrap leading-relaxed select-text">{{ $message->body }}</p>

                                    @if (! empty($message->attachments))
                                        <div class="mt-2 flex flex-col gap-1.5">
                                            @foreach ($message->attachments as $att)
                                                <div class="flex items-center gap-2 rounded-lg bg-black/10 px-2.5 py-1.5 text-[11px] font-medium backdrop-blur-sm">
                                                    <span>📎</span>
                                                    <span>{{ $att['type'] ?? 'Attachment' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-1.5 flex items-center justify-end gap-1.5 text-[10px] {{ $isOutbound ? 'text-blue-100' : 'text-slate-400' }}">
                                        <span>{{ $message->occurred_at ? $message->occurred_at->format('H:i') : '' }}</span>
                                        @if ($isOutbound)
                                            <span class="font-bold">
                                                @if ($message->status === 'delivered') ✓✓
                                                @elseif ($message->status === 'read') <span class="text-sky-200">✓✓</span>
                                                @elseif ($message->status === 'failed') <span class="text-rose-300">✕ failed</span>
                                                @else ✓
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full flex-col items-center justify-center text-center text-slate-400 p-8">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-2xl mb-2">👋</div>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Start the conversation</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">Type a message below or click a quick reply.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Canned Responses Quick Suggestions -->
                <div class="flex items-center gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-2 text-xs dark:border-slate-800/80 dark:bg-slate-900/40 overflow-x-auto no-scrollbar">
                    <span class="text-[11px] font-bold text-slate-400 flex-shrink-0">⚡ Quick:</span>
                    <button type="button" onclick="insertCanned('Hello! Thank you for reaching out. How can I assist you today?')" class="rounded-lg border border-slate-200/80 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 whitespace-nowrap">
                        👋 Greeting
                    </button>
                    <button type="button" onclick="insertCanned('We have received your requirement. A member of our sales team will connect shortly.')" class="rounded-lg border border-slate-200/80 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 whitespace-nowrap">
                        📋 Request Received
                    </button>
                    <button type="button" onclick="insertCanned('Could you please share your email and requirements so we can prepare a tailored quote?')" class="rounded-lg border border-slate-200/80 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 whitespace-nowrap">
                        💼 Request Quote Info
                    </button>
                </div>

                <!-- Chat Composer Area -->
                <div class="border-t border-slate-200/80 p-4 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <form id="message-form" onsubmit="event.preventDefault(); sendMessage({{ $activeConversation->id }});" class="flex items-end gap-3">
                        <div class="relative flex-1">
                            <textarea
                                id="message-input"
                                rows="2"
                                placeholder="@lang('unified_inbox::app.inbox.type-message')"
                                onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage({{ $activeConversation->id }}); }"
                                class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50/80 p-3 text-xs text-slate-900 transition placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            id="send-button"
                            class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/30 transition-all hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-4 focus:ring-blue-200 disabled:opacity-50"
                            title="Send Message (Enter)"
                        >
                            <svg class="h-4 w-4 fill-current rotate-90" viewBox="0 0 24 24">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <!-- Rich Onboarding / Channel Connection Canvas -->
                <div class="flex h-full flex-col items-center justify-center p-8 text-center bg-slate-50/50 dark:bg-slate-900/40 overflow-y-auto">
                    <div class="w-full max-w-xl flex flex-col items-center gap-6">
                        
                        <!-- Top Icon & Header -->
                        <div class="flex flex-col items-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/25 mb-3">
                                <svg class="h-8 w-8 fill-current" viewBox="0 0 24 24">
                                    <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
                                </svg>
                            </div>
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">
                                Unified Omnichannel Inbox
                            </h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 max-w-md">
                                Connect WhatsApp Business, Facebook Messenger, and Instagram Direct to chat with leads and customers in one unified place.
                            </p>
                        </div>

                        <!-- 3 Channel Status Cards Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 w-full text-left">
                            
                            <!-- WhatsApp Card -->
                            <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#25D366] text-white shadow-sm">
                                            <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.586 1.83.896 2.791.896 3.181 0 5.767-2.587 5.767-5.766.001-3.187-2.575-5.782-5.767-5.782zm9.969 5.828c0 5.518-4.482 10-10 10-1.748 0-3.389-.452-4.821-1.242l-5.179 1.355 1.378-5.044c-.878-1.478-1.378-3.21-1.378-5.069 0-5.518 4.482-10 10-10 5.518 0 10 4.482 10 10z"/>
                                            </svg>
                                        </div>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $waConn ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $waConn ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $waConn ? 'Connected' : 'Not Connected' }}
                                        </span>
                                    </div>
                                    <h4 class="mt-2.5 text-xs font-bold text-slate-900 dark:text-white">WhatsApp Business</h4>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                        Cloud API messaging with verified WABA and phone number.
                                    </p>
                                </div>
                                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                                    <a
                                        href="{{ route('admin.unified_inbox.channels.index') }}"
                                        class="text-[11px] font-bold text-emerald-600 hover:underline dark:text-emerald-400 inline-flex items-center gap-1"
                                    >
                                        <span>{{ $waConn ? 'Manage WA' : 'Connect WhatsApp' }}</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Messenger Card -->
                            <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#0084FF] text-white shadow-sm">
                                            <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                                                <path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.22 5.58 3.22 7.49V22l3.41-1.87c1.07.3 2.2.46 3.37.46 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm1.09 13.06l-2.61-2.78-5.09 2.78 5.6-5.94 2.67 2.78 5.03-2.78-5.6 5.94z"/>
                                            </svg>
                                        </div>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $msgConn ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $msgConn ? 'bg-blue-500' : 'bg-slate-400' }}"></span>
                                            {{ $msgConn ? 'Connected' : 'Not Connected' }}
                                        </span>
                                    </div>
                                    <h4 class="mt-2.5 text-xs font-bold text-slate-900 dark:text-white">Facebook Messenger</h4>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                        Facebook Page DMs and customer inquiries.
                                    </p>
                                </div>
                                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                                    <a
                                        href="{{ route('admin.unified_inbox.channels.index') }}"
                                        class="text-[11px] font-bold text-blue-600 hover:underline dark:text-blue-400 inline-flex items-center gap-1"
                                    >
                                        <span>{{ $msgConn ? 'Manage Messenger' : 'Connect Messenger' }}</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Instagram Card -->
                            <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 text-white shadow-sm">
                                            <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                            </svg>
                                        </div>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $igConn ? 'bg-pink-100 text-pink-800 dark:bg-pink-950/60 dark:text-pink-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $igConn ? 'bg-pink-500' : 'bg-slate-400' }}"></span>
                                            {{ $igConn ? 'Connected' : 'Not Connected' }}
                                        </span>
                                    </div>
                                    <h4 class="mt-2.5 text-xs font-bold text-slate-900 dark:text-white">Instagram Direct</h4>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                        Instagram Professional account direct messaging.
                                    </p>
                                </div>
                                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                                    <a
                                        href="{{ route('admin.unified_inbox.channels.index') }}"
                                        class="text-[11px] font-bold text-pink-600 hover:underline dark:text-pink-400 inline-flex items-center gap-1"
                                    >
                                        <span>{{ $igConn ? 'Manage IG' : 'Connect Instagram' }}</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Primary CTA Button & Central Webhook info -->
                        <div class="flex flex-col items-center gap-3">
                            <a
                                href="{{ route('admin.unified_inbox.channels.index') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-xs font-bold text-white shadow-md shadow-blue-500/30 transition hover:bg-blue-700 hover:scale-105 active:scale-95"
                            >
                                <svg class="h-4 w-4 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Connect Channels with Meta</span>
                                <span>&rarr;</span>
                            </a>
                            <p class="text-[11px] text-slate-400 flex items-center gap-1">
                                <span>🌐 Meta Central Webhook:</span>
                                <code class="rounded bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-slate-600 dark:text-slate-300">
                                    {{ $centralWebhookUrl }}
                                </code>
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- PANEL 3: CRM CONTEXT & INTELLIGENCE DRAWER (320px)                         -->
        <!-- ========================================================================= -->
        @if ($activeConversation)
            <div id="crm-sidebar" class="flex w-[320px] flex-shrink-0 flex-col overflow-y-auto bg-slate-50/70 p-5 dark:bg-slate-900/60 border-l border-slate-200/80 dark:border-slate-800/80">
                <div class="flex flex-col gap-5">
                    
                    <!-- Customer Profile Header Card -->
                    <div class="flex flex-col items-center text-center p-4 rounded-2xl bg-white border border-slate-200/70 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-purple-600 text-lg font-bold text-white shadow-md shadow-blue-500/20">
                            {{ strtoupper(substr($activeConversation->customer_name, 0, 2)) }}
                        </div>
                        <h4 class="mt-3 text-sm font-bold text-slate-900 dark:text-white">
                            {{ $activeConversation->customer_name }}
                        </h4>
                        <span class="mt-0.5 text-xs text-slate-500 font-mono">
                            {{ $activeConversation->external_id }}
                        </span>
                        <div class="mt-2.5 flex items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold text-white" style="background-color: {{ match($activeConversation->channel) { 'whatsapp' => '#25D366', 'messenger' => '#0084FF', 'instagram' => '#E1306C', default => '#64748B' } }}">
                                {{ ucfirst($activeConversation->channel) }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                Acc: {{ Str::limit($activeConversation->account_id, 12) }}
                            </span>
                        </div>
                    </div>

                    <!-- CRM Person Contact Details -->
                    <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span>👤</span> @lang('unified_inbox::app.inbox.crm-person')
                            </span>
                            @if ($activeConversation->person)
                                <a href="{{ route('admin.contacts.persons.edit', $activeConversation->person->id) }}" class="text-[11px] font-semibold text-blue-600 hover:underline">
                                    View Profile &rarr;
                                </a>
                            @endif
                        </div>

                        <div class="mt-3">
                            @if ($activeConversation->person)
                                <div class="text-xs space-y-2">
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Name</span>
                                        <p class="font-bold text-slate-800 dark:text-slate-200">{{ $activeConversation->person->name }}</p>
                                    </div>
                                    @if (! empty($activeConversation->person->contact_numbers))
                                        <div>
                                            <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Phone</span>
                                            <p class="text-slate-700 dark:text-slate-300 font-mono text-[11px]">
                                                {{ is_array($activeConversation->person->contact_numbers) ? ($activeConversation->person->contact_numbers[0]['value'] ?? '') : $activeConversation->person->contact_numbers }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-xs text-slate-500">@lang('unified_inbox::app.inbox.no-person')</p>
                                <button
                                    type="button"
                                    onclick="quickLinkPerson({{ $activeConversation->id }})"
                                    class="mt-2.5 w-full rounded-xl bg-slate-50 border border-slate-200 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    + @lang('unified_inbox::app.inbox.create-person')
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- CRM Lead Deal Details -->
                    <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span>🎯</span> @lang('unified_inbox::app.inbox.crm-lead')
                            </span>
                            @if ($activeConversation->lead)
                                <a href="{{ route('admin.leads.view', $activeConversation->lead->id) }}" class="text-[11px] font-semibold text-blue-600 hover:underline">
                                    View Deal &rarr;
                                </a>
                            @endif
                        </div>

                        <div class="mt-3">
                            @if ($activeConversation->lead)
                                <div class="text-xs space-y-2">
                                    <div>
                                        <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Deal Title</span>
                                        <p class="font-bold text-slate-800 dark:text-slate-200">{{ $activeConversation->lead->title }}</p>
                                    </div>
                                    <div class="flex items-center justify-between pt-1">
                                        <span class="text-slate-500">Value:</span>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ core()->formatBasePrice($activeConversation->lead->lead_value) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500">Stage:</span>
                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                            {{ $activeConversation->lead->stage?->name ?: 'Pipeline' }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-slate-500">@lang('unified_inbox::app.inbox.no-lead')</p>
                                
                                <form id="quick-lead-form" onsubmit="event.preventDefault(); quickCreateLead({{ $activeConversation->id }});" class="mt-3 space-y-2.5">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Deal Title</label>
                                        <input
                                            type="text"
                                            id="quick-lead-title"
                                            value="Chat Deal - {{ $activeConversation->customer_name }}"
                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs font-medium text-slate-800 transition focus:border-blue-500 focus:bg-white focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Estimated Value ($)</label>
                                        <input
                                            type="number"
                                            id="quick-lead-value"
                                            placeholder="e.g. 5000"
                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs font-medium text-slate-800 transition focus:border-blue-500 focus:bg-white focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        >
                                    </div>
                                    <button
                                        type="submit"
                                        class="w-full rounded-xl bg-blue-600 py-2 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 active:scale-95"
                                    >
                                        🚀 @lang('unified_inbox::app.inbox.create-lead')
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Conversation State Actions -->
                    <div class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col gap-2">
                        <span class="text-xs font-bold text-slate-900 dark:text-white">Quick Actions</span>
                        @if ($activeConversation->status !== 'closed')
                            <button
                                type="button"
                                onclick="updateConversationStatus({{ $activeConversation->id }}, 'closed')"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >
                                ✓ @lang('unified_inbox::app.inbox.mark-closed')
                            </button>
                        @else
                            <button
                                type="button"
                                onclick="updateConversationStatus({{ $activeConversation->id }}, 'open')"
                                class="w-full rounded-xl border border-emerald-200 bg-emerald-50 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300"
                            >
                                ↺ @lang('unified_inbox::app.inbox.reopen')
                            </button>
                        @endif
                    </div>

                </div>
            </div>
        @endif

    </div>

    <!-- Live Chat AJAX Interaction Scripts -->
    @pushOnce('scripts')
    <script>
        function scrollChatToBottom() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
        window.addEventListener('load', scrollChatToBottom);
        scrollChatToBottom();

        function insertCanned(text) {
            const input = document.getElementById('message-input');
            if (input) {
                input.value = text;
                input.focus();
            }
        }

        function toggleCrmSidebar() {
            const sidebar = document.getElementById('crm-sidebar');
            if (sidebar) {
                sidebar.classList.toggle('hidden');
            }
        }

        async function sendMessage(conversationId) {
            const input = document.getElementById('message-input');
            const button = document.getElementById('send-button');
            const text = input.value.trim();

            if (!text) return;

            button.disabled = true;

            try {
                const response = await fetch(`{{ url(config('app.admin_path')) }}/unified-inbox/conversations/${conversationId}/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ body: text })
                });

                const data = await response.json();

                if (data.success && data.message) {
                    input.value = '';

                    const container = document.getElementById('messages-container');
                    const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    const bubble = document.createElement('div');
                    bubble.className = 'flex flex-col items-end';
                    bubble.innerHTML = `
                        <div class="flex items-end gap-2 max-w-[75%] lg:max-w-[65%]">
                            <div class="rounded-2xl px-4 py-2.5 text-xs shadow-sm bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-br-none shadow-blue-500/10">
                                <p class="whitespace-pre-wrap leading-relaxed select-text">${escapeHtml(data.message.body)}</p>
                                <div class="mt-1.5 flex items-center justify-end gap-1 text-[10px] text-blue-100">
                                    <span>${timeStr}</span>
                                    <span class="font-bold">✓</span>
                                </div>
                            </div>
                        </div>
                    `;
                    container.appendChild(bubble);
                    scrollChatToBottom();
                } else {
                    alert(data.error || 'Failed to send message.');
                }
            } catch (err) {
                console.error(err);
                alert('Error sending message.');
            } finally {
                button.disabled = false;
            }
        }

        async function updateConversationStatus(conversationId, newStatus) {
            try {
                const response = await fetch(`{{ url(config('app.admin_path')) }}/unified-inbox/conversations/${conversationId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch(e) {
                console.error(e);
            }
        }

        async function assignConversationAgent(conversationId, userId) {
            try {
                const response = await fetch(`{{ url(config('app.admin_path')) }}/unified-inbox/conversations/${conversationId}/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId })
                });
                const data = await response.json();
                if (data.success) {
                    // Feedback
                }
            } catch(e) {
                console.error(e);
            }
        }

        async function quickLinkPerson(conversationId) {
            try {
                const response = await fetch(`{{ url(config('app.admin_path')) }}/unified-inbox/conversations/${conversationId}/link-person`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch(e) {
                console.error(e);
            }
        }

        async function quickCreateLead(conversationId) {
            const title = document.getElementById('quick-lead-title').value;
            const leadValue = document.getElementById('quick-lead-value').value;

            try {
                const response = await fetch(`{{ url(config('app.admin_path')) }}/unified-inbox/conversations/${conversationId}/link-lead`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ title: title, lead_value: leadValue })
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch(e) {
                console.error(e);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    @endpushOnce
</x-admin::layouts>
