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
            <div class="flex flex-col gap-3.5 border-b border-slate-100 p-4 dark:border-slate-800">
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

                    <div class="flex items-center gap-1">
                        <a
                            href="{{ route('admin.unified_inbox.channels.index') }}"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800"
                            title="Manage Messaging Channels"
                        >
                            <svg class="h-4 w-4 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </a>
                    </div>
                </div>

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
                    <div class="flex flex-col items-center justify-center p-12 text-center text-slate-400">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                            <svg class="h-6 w-6 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No conversations yet</p>
                        <p class="mt-1 text-[11px] text-slate-400 max-w-[200px]">Incoming WhatsApp, Messenger, and Instagram inquiries will stream right here.</p>
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
                <!-- Empty State When No Chat is Selected -->
                <div class="flex h-full flex-col items-center justify-center p-8 text-center text-slate-400">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 mb-4 shadow-sm">
                        <svg class="h-8 w-8 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
                        @lang('unified_inbox::app.inbox.select-conversation')
                    </h3>
                    <p class="mt-1 text-xs text-slate-400 max-w-sm">
                        @lang('unified_inbox::app.inbox.select-conversation-desc')
                    </p>
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
