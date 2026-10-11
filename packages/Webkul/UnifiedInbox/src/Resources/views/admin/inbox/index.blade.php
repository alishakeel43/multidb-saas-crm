<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.inbox.title')
    </x-slot>

    <!-- Main Container: Full height 3-panel layout -->
    <div class="-m-4 flex h-[calc(100vh-65px)] overflow-hidden bg-gray-50 dark:bg-gray-950" id="unified-inbox-app">

        <!-- ==================== PANEL 1: CONVERSATIONS LIST ==================== -->
        <div class="flex w-80 flex-shrink-0 flex-col border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 lg:w-96">
            
            <!-- Top Controls: Search & Channel Tabs -->
            <div class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        @lang('unified_inbox::app.inbox.title')
                    </h2>
                    <a
                        href="{{ route('admin.unified_inbox.channels.index') }}"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        title="Manage Channels"
                    >
                        <i class="icon-setting text-xl"></i>
                    </a>
                </div>

                <!-- Channel Filter Pills -->
                <div class="flex items-center gap-1 overflow-x-auto pb-1 text-xs font-semibold no-scrollbar">
                    <a
                        href="{{ route('admin.unified_inbox.index', ['status' => $statusFilter, 'search' => $search]) }}"
                        class="rounded-full px-3 py-1.5 transition {{ empty($channelFilter) ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
                    >
                        All
                    </a>
                    <a
                        href="{{ route('admin.unified_inbox.index', ['channel' => 'whatsapp', 'status' => $statusFilter, 'search' => $search]) }}"
                        class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 transition {{ $channelFilter === 'whatsapp' ? 'bg-[#25D366] text-white shadow-sm' : 'bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-950/40 dark:text-green-300' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 {{ $channelFilter === 'whatsapp' ? 'bg-white' : '' }}"></span>
                        WhatsApp
                    </a>
                    <a
                        href="{{ route('admin.unified_inbox.index', ['channel' => 'messenger', 'status' => $statusFilter, 'search' => $search]) }}"
                        class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 transition {{ $channelFilter === 'messenger' ? 'bg-[#0084FF] text-white shadow-sm' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500 {{ $channelFilter === 'messenger' ? 'bg-white' : '' }}"></span>
                        Messenger
                    </a>
                    <a
                        href="{{ route('admin.unified_inbox.index', ['channel' => 'instagram', 'status' => $statusFilter, 'search' => $search]) }}"
                        class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 transition {{ $channelFilter === 'instagram' ? 'bg-[#E1306C] text-white shadow-sm' : 'bg-pink-50 text-pink-700 hover:bg-pink-100 dark:bg-pink-950/40 dark:text-pink-300' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-pink-500 {{ $channelFilter === 'instagram' ? 'bg-white' : '' }}"></span>
                        Instagram
                    </a>
                </div>

                <!-- Search Input & Status Filter -->
                <form action="{{ route('admin.unified_inbox.index') }}" method="GET" class="flex gap-2">
                    @if ($channelFilter)
                        <input type="hidden" name="channel" value="{{ $channelFilter }}">
                    @endif
                    <div class="relative flex-1">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="@lang('unified_inbox::app.inbox.search-placeholder')"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 py-1.5 pl-3 pr-8 text-xs text-gray-900 focus:border-blue-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                        @if ($search)
                            <a href="{{ route('admin.unified_inbox.index', ['channel' => $channelFilter, 'status' => $statusFilter]) }}" class="absolute right-2 top-2 text-xs text-gray-400 hover:text-gray-600">&times;</a>
                        @endif
                    </div>
                    <select
                        name="status"
                        onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 px-2 py-1.5 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <option value="">Status: All</option>
                        <option value="open" {{ $statusFilter === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="closed" {{ $statusFilter === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </form>
            </div>

            <!-- Conversations Scroll List -->
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($conversations as $conversation)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conversation->id;
                        $channelColor = match($conversation->channel) {
                            'whatsapp' => '#25D366',
                            'messenger' => '#0084FF',
                            'instagram' => '#E1306C',
                            default => '#6B7280'
                        };
                    @endphp
                    <a
                        href="{{ route('admin.unified_inbox.index', ['conversation_id' => $conversation->id, 'channel' => $channelFilter, 'status' => $statusFilter, 'search' => $search]) }}"
                        class="flex items-start gap-3 p-3.5 transition hover:bg-gray-50 dark:hover:bg-gray-800/60 {{ $isActive ? 'bg-blue-50/80 dark:bg-blue-950/30 border-l-4 border-blue-600' : '' }}"
                    >
                        <!-- Avatar with channel badge overlay -->
                        <div class="relative flex-shrink-0">
                            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-tr from-gray-200 to-gray-300 font-bold text-gray-700 dark:from-gray-700 dark:to-gray-800 dark:text-gray-200">
                                {{ strtoupper(substr($conversation->customer_name, 0, 2)) }}
                            </div>
                            <span
                                class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full text-[9px] text-white shadow-sm"
                                style="background-color: {{ $channelColor }}"
                            >
                                @if ($conversation->channel === 'whatsapp') W
                                @elseif ($conversation->channel === 'messenger') M
                                @else I
                                @endif
                            </span>
                        </div>

                        <!-- Info & Preview -->
                        <div class="flex flex-1 flex-col overflow-hidden">
                            <div class="flex items-center justify-between">
                                <span class="truncate text-xs font-bold text-gray-900 dark:text-white">
                                    {{ $conversation->customer_name }}
                                </span>
                                <span class="text-[10px] text-gray-400">
                                    {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans(null, true) : '' }}
                                </span>
                            </div>

                            <div class="mt-0.5 flex items-center justify-between gap-1">
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                    {{ $conversation->last_message_preview ?: 'No messages yet' }}
                                </p>
                                @if ($conversation->unread_count > 0)
                                    <span class="flex h-4 min-w-[16px] items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white">
                                        {{ $conversation->unread_count }}
                                    </span>
                                @endif
                            </div>

                            <!-- Lead Tag if linked -->
                            @if ($conversation->lead)
                                <div class="mt-1 flex items-center gap-1">
                                    <span class="inline-flex items-center rounded bg-amber-100 px-1.5 py-0.2 text-[9px] font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        Lead: {{ Str::limit($conversation->lead->title, 18) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="flex flex-col items-center justify-center p-8 text-center text-gray-400">
                        <i class="icon-mail text-3xl mb-2"></i>
                        <p class="text-sm font-semibold">@lang('unified_inbox::app.inbox.no-conversations')</p>
                        <p class="mt-1 text-xs text-gray-500">@lang('unified_inbox::app.inbox.no-conversations-desc')</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ==================== PANEL 2: ACTIVE CHAT THREAD ==================== -->
        <div class="flex flex-1 flex-col overflow-hidden bg-white dark:bg-gray-900">
            @if ($activeConversation)
                <!-- Chat Header -->
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-3 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                            {{ strtoupper(substr($activeConversation->customer_name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    {{ $activeConversation->customer_name }}
                                </h3>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white" style="background-color: {{ match($activeConversation->channel) { 'whatsapp' => '#25D366', 'messenger' => '#0084FF', 'instagram' => '#E1306C', default => '#6B7280' } }}">
                                    {{ $activeConversation->channel }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                ID: {{ $activeConversation->external_id }}
                            </span>
                        </div>
                    </div>

                    <!-- Header Action Pills: Status & Assignee -->
                    <div class="flex items-center gap-3">
                        <!-- Status Selector -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-500">Status:</span>
                            <select
                                id="status-selector"
                                onchange="updateConversationStatus({{ $activeConversation->id }}, this.value)"
                                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="open" {{ $activeConversation->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="pending" {{ $activeConversation->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="closed" {{ $activeConversation->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>

                        <!-- Assignee Selector -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-500">Assign:</span>
                            <select
                                id="agent-selector"
                                onchange="assignConversationAgent({{ $activeConversation->id }}, this.value)"
                                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="">Unassigned</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ $activeConversation->user_id === $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Messages Feed Scroll Container -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50/50 dark:bg-gray-950/40" id="messages-container">
                    @forelse ($activeMessages as $message)
                        @php
                            $isOutbound = $message->direction === 'outbound';
                        @endphp
                        <div class="flex flex-col {{ $isOutbound ? 'items-end' : 'items-start' }}">
                            <div class="flex items-end gap-2 max-w-[70%]">
                                @if (! $isOutbound)
                                    <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-[10px] font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ strtoupper(substr($activeConversation->customer_name, 0, 1)) }}
                                    </div>
                                @endif

                                <div class="rounded-2xl px-4 py-2.5 text-xs shadow-sm {{ $isOutbound ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white text-gray-800 rounded-bl-none border border-gray-200 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100' }}">
                                    <p class="whitespace-pre-wrap leading-relaxed">{{ $message->body }}</p>

                                    @if (! empty($message->attachments))
                                        <div class="mt-2 flex flex-col gap-1">
                                            @foreach ($message->attachments as $att)
                                                <span class="inline-flex items-center gap-1 rounded bg-black/10 px-2 py-1 text-[10px]">
                                                    📎 {{ $att['type'] ?? 'Attachment' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-1 flex items-center justify-end gap-1 text-[10px] {{ $isOutbound ? 'text-blue-100' : 'text-gray-400' }}">
                                        <span>{{ $message->occurred_at ? $message->occurred_at->format('H:i') : '' }}</span>
                                        @if ($isOutbound)
                                            <span>
                                                @if ($message->status === 'delivered') ✓✓
                                                @elseif ($message->status === 'read') <span class="text-cyan-200">✓✓</span>
                                                @elseif ($message->status === 'failed') <span class="text-red-300">✕</span>
                                                @else ✓
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full items-center justify-center text-center text-xs text-gray-400">
                            No messages in this conversation yet. Send a message below to start chatting.
                        </div>
                    @endforelse
                </div>

                <!-- Canned Responses Quick Bar -->
                <div class="flex items-center gap-2 border-t border-gray-100 bg-gray-50/80 px-4 py-2 text-xs dark:border-gray-800 dark:bg-gray-900/60 overflow-x-auto no-scrollbar">
                    <span class="text-gray-400 font-medium">Quick:</span>
                    <button type="button" onclick="insertCanned('Hello! Thank you for reaching out. How can I assist you today?')" class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Greeting
                    </button>
                    <button type="button" onclick="insertCanned('We have received your request and our sales team is reviewing it.')" class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Request Received
                    </button>
                    <button type="button" onclick="insertCanned('Could you please share your email address and requirement details so we can send a custom quote?')" class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Ask for Quote Info
                    </button>
                </div>

                <!-- Chat Composer -->
                <div class="border-t border-gray-200 p-4 dark:border-gray-800">
                    <form id="message-form" onsubmit="event.preventDefault(); sendMessage({{ $activeConversation->id }});" class="flex items-end gap-3">
                        <textarea
                            id="message-input"
                            rows="2"
                            placeholder="@lang('unified_inbox::app.inbox.type-message')"
                            onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage({{ $activeConversation->id }}); }"
                            class="flex-1 resize-none rounded-xl border border-gray-300 bg-gray-50 p-3 text-xs text-gray-900 focus:border-blue-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        ></textarea>

                        <button
                            type="submit"
                            id="send-button"
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 disabled:opacity-50"
                        >
                            <svg class="h-4 w-4 fill-current rotate-90" viewBox="0 0 24 24">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex h-full flex-col items-center justify-center p-8 text-center text-gray-400">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-3xl dark:bg-gray-800">
                        💬
                    </div>
                    <h3 class="mt-4 text-base font-bold text-gray-700 dark:text-gray-200">
                        @lang('unified_inbox::app.inbox.select-conversation')
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        @lang('unified_inbox::app.inbox.select-conversation-desc')
                    </p>
                </div>
            @endif
        </div>

        <!-- ==================== PANEL 3: CRM CONTEXT SIDEBAR ==================== -->
        @if ($activeConversation)
            <div class="hidden w-80 flex-shrink-0 flex-col overflow-y-auto border-l border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 xl:flex">
                <div class="flex flex-col gap-6">
                    
                    <!-- Customer Profile Card -->
                    <div class="flex flex-col items-center text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 text-xl font-bold text-white shadow-md">
                            {{ strtoupper(substr($activeConversation->customer_name, 0, 2)) }}
                        </div>
                        <h4 class="mt-3 text-sm font-bold text-gray-900 dark:text-white">
                            {{ $activeConversation->customer_name }}
                        </h4>
                        <span class="text-xs text-gray-500 font-mono">
                            {{ $activeConversation->external_id }}
                        </span>
                        <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-[10px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Channel: {{ ucfirst($activeConversation->channel) }}
                        </span>
                    </div>

                    <!-- CRM Person Card -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                @lang('unified_inbox::app.inbox.crm-person')
                            </span>
                            @if ($activeConversation->person)
                                <a href="{{ route('admin.contacts.persons.edit', $activeConversation->person->id) }}" class="text-[11px] font-semibold text-blue-600 hover:underline">
                                    View &rarr;
                                </a>
                            @endif
                        </div>

                        <div class="mt-3">
                            @if ($activeConversation->person)
                                <div class="text-xs space-y-1">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $activeConversation->person->name }}</p>
                                    @if (! empty($activeConversation->person->contact_numbers))
                                        <p class="text-gray-600 dark:text-gray-400">
                                            📞 {{ is_array($activeConversation->person->contact_numbers) ? ($activeConversation->person->contact_numbers[0]['value'] ?? '') : $activeConversation->person->contact_numbers }}
                                        </p>
                                    @endif
                                </div>
                            @else
                                <p class="text-xs text-gray-500">@lang('unified_inbox::app.inbox.no-person')</p>
                                <button
                                    type="button"
                                    onclick="quickLinkPerson({{ $activeConversation->id }})"
                                    class="mt-2 w-full rounded-lg bg-white border border-gray-300 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    + @lang('unified_inbox::app.inbox.create-person')
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- CRM Lead Card -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                @lang('unified_inbox::app.inbox.crm-lead')
                            </span>
                            @if ($activeConversation->lead)
                                <a href="{{ route('admin.leads.view', $activeConversation->lead->id) }}" class="text-[11px] font-semibold text-blue-600 hover:underline">
                                    View &rarr;
                                </a>
                            @endif
                        </div>

                        <div class="mt-3">
                            @if ($activeConversation->lead)
                                <div class="text-xs space-y-1.5">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $activeConversation->lead->title }}</p>
                                    <div class="flex justify-between text-gray-500">
                                        <span>Value:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ core()->formatBasePrice($activeConversation->lead->lead_value) }}</span>
                                    </div>
                                    <div class="flex justify-between text-gray-500">
                                        <span>Stage:</span>
                                        <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                                            {{ $activeConversation->lead->stage?->name ?: 'Pipeline' }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-gray-500">@lang('unified_inbox::app.inbox.no-lead')</p>
                                
                                <form id="quick-lead-form" onsubmit="event.preventDefault(); quickCreateLead({{ $activeConversation->id }});" class="mt-3 space-y-2">
                                    <input
                                        type="text"
                                        id="quick-lead-title"
                                        placeholder="Lead Title (e.g. Inbound {{ $activeConversation->customer_name }})"
                                        value="Chat Lead - {{ $activeConversation->customer_name }}"
                                        class="w-full rounded-lg border border-gray-300 p-2 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    >
                                    <input
                                        type="number"
                                        id="quick-lead-value"
                                        placeholder="Estimated Value ($)"
                                        class="w-full rounded-lg border border-gray-300 p-2 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    >
                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-blue-600 py-1.5 text-xs font-semibold text-white shadow hover:bg-blue-700"
                                    >
                                        🚀 @lang('unified_inbox::app.inbox.create-lead')
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Conversation Management -->
                    <div class="flex flex-col gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Actions</span>
                        @if ($activeConversation->status !== 'closed')
                            <button
                                type="button"
                                onclick="updateConversationStatus({{ $activeConversation->id }}, 'closed')"
                                class="rounded-lg border border-gray-200 bg-white py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                ✓ @lang('unified_inbox::app.inbox.mark-closed')
                            </button>
                        @else
                            <button
                                type="button"
                                onclick="updateConversationStatus({{ $activeConversation->id }}, 'open')"
                                class="rounded-lg border border-green-200 bg-green-50 py-1.5 text-xs font-semibold text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300"
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
        // Scroll messages feed to bottom on load
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

                    // Append message bubble locally
                    const container = document.getElementById('messages-container');
                    const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    const bubble = document.createElement('div');
                    bubble.className = 'flex flex-col items-end';
                    bubble.innerHTML = `
                        <div class="flex items-end gap-2 max-w-[70%]">
                            <div class="rounded-2xl px-4 py-2.5 text-xs shadow-sm bg-blue-600 text-white rounded-br-none">
                                <p class="whitespace-pre-wrap leading-relaxed">${escapeHtml(data.message.body)}</p>
                                <div class="mt-1 flex items-center justify-end gap-1 text-[10px] text-blue-100">
                                    <span>${timeStr}</span>
                                    <span>✓</span>
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
                    // Success feedback
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
