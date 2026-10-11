<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.channels.title')
    </x-slot>

    <div class="flex flex-col gap-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.settings.index') }}" class="hover:underline">Settings</a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-gray-200">@lang('unified_inbox::app.channels.title')</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    @lang('unified_inbox::app.channels.title')
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @lang('unified_inbox::app.channels.description')
                </p>
            </div>

            <!-- Primary Connect Actions -->
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    onclick="document.getElementById('manual-connect-modal').classList.remove('hidden')"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                    Manual / Sandbox Connect
                </button>

                <button
                    type="button"
                    id="meta-es-btn"
                    onclick="launchMetaEmbeddedSignup()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#1877F2] px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:bg-[#166fe5] focus:ring-4 focus:ring-blue-200"
                >
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span>@lang('unified_inbox::app.channels.connect-meta-btn')</span>
                </button>
            </div>
        </div>

        <!-- Channels Grid -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach ($channels as $key => $channel)
                @php
                    $conn = $channel['connection'];
                    $isConnected = $conn && $conn->status === 'connected';
                @endphp
                <div class="relative flex flex-col justify-between overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl text-white shadow-inner" style="background-color: {{ $channel['color'] }}">
                                    @if ($key === 'whatsapp')
                                        <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24">
                                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.586 1.83.896 2.791.896 3.181 0 5.767-2.587 5.767-5.766.001-3.187-2.575-5.782-5.767-5.782zm9.969 5.828c0 5.518-4.482 10-10 10-1.748 0-3.389-.452-4.821-1.242l-5.179 1.355 1.378-5.044c-.878-1.478-1.378-3.21-1.378-5.069 0-5.518 4.482-10 10-10 5.518 0 10 4.482 10 10z"/>
                                        </svg>
                                    @elseif ($key === 'messenger')
                                        <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24">
                                            <path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.22 5.58 3.22 7.49V22l3.41-1.87c1.07.3 2.2.46 3.37.46 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm1.09 13.06l-2.61-2.78-5.09 2.78 5.6-5.94 2.67 2.78 5.03-2.78-5.6 5.94z"/>
                                        </svg>
                                    @else
                                        <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $channel['title'] }}
                                    </h3>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $isConnected ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $isConnected ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                        {{ $isConnected ? trans('unified_inbox::app.channels.connected') : trans('unified_inbox::app.channels.disconnected') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $channel['desc'] }}
                        </p>

                        @if ($isConnected)
                            <div class="rounded-lg bg-gray-50 p-3 text-xs text-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
                                <div class="flex justify-between py-1 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-gray-500">Connected Name:</span>
                                    <span class="font-semibold">{{ $conn->name }}</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-gray-500">ID:</span>
                                    <span class="font-mono">{{ $conn->external_id }}</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-gray-500">Connected At:</span>
                                    <span>{{ $conn->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                        @if ($isConnected)
                            <form action="{{ route('admin.unified_inbox.channels.disconnect', $conn->id) }}" method="POST" onsubmit="return confirm('{{ trans('unified_inbox::app.channels.disconnect-confirm') }}')">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400">
                                    @lang('unified_inbox::app.channels.disconnect')
                                </button>
                            </form>
                            <a href="{{ route('admin.unified_inbox.index', ['channel' => $key]) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">
                                Open in Inbox &rarr;
                            </a>
                        @else
                            <button
                                type="button"
                                onclick="launchMetaEmbeddedSignup('{{ $key }}')"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2 text-center text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                Connect {{ $channel['title'] }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Centralized Webhook Info Box for Meta Developer Console -->
        <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-5 dark:border-blue-900 dark:bg-blue-950/40">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 rounded-lg bg-blue-500 p-1.5 text-white shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex flex-col gap-1 text-xs text-blue-900 dark:text-blue-200">
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-sm">Centralized Meta Webhook (Multi-Tenant Auto-Routing)</h4>
                        <span class="rounded bg-blue-200/80 px-2 py-0.5 text-[10px] font-bold text-blue-800 dark:bg-blue-800 dark:text-blue-200">1 Webhook for All Tenants</span>
                    </div>
                    <p class="text-blue-800 dark:text-blue-300">
                        Configure this single central URL in your Meta App Dashboard. All tenant channels (WhatsApp, Messenger, Instagram) are automatically resolved and routed to their isolated tenant databases.
                    </p>
                    <div class="mt-2 flex flex-col gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-slate-500 font-semibold min-w-[90px]">Callback URL:</span>
                            <code class="rounded bg-white/90 px-2.5 py-1 font-mono text-[11px] font-bold text-blue-900 shadow-sm dark:bg-gray-900 dark:text-blue-200 border border-blue-200/50">
                                {{ $centralWebhookUrl }}
                            </code>
                            <button
                                type="button"
                                onclick="navigator.clipboard.writeText('{{ $centralWebhookUrl }}'); alert('Central Webhook URL copied to clipboard!');"
                                class="rounded bg-blue-600 px-2 py-0.5 text-[10px] font-bold text-white shadow hover:bg-blue-700 transition"
                            >
                                Copy
                            </button>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-slate-500 font-semibold min-w-[90px]">Verify Token:</span>
                            <code class="rounded bg-white/90 px-2.5 py-1 font-mono text-[11px] font-bold text-blue-900 shadow-sm dark:bg-gray-900 dark:text-blue-200 border border-blue-200/50">
                                {{ config('unified-inbox.meta.verify_token') ?: 'krayin_crm_unified_inbox' }}
                            </code>
                            <button
                                type="button"
                                onclick="navigator.clipboard.writeText('{{ config('unified-inbox.meta.verify_token') ?: 'krayin_crm_unified_inbox' }}'); alert('Verify Token copied to clipboard!');"
                                class="rounded bg-blue-600 px-2 py-0.5 text-[10px] font-bold text-white shadow hover:bg-blue-700 transition"
                            >
                                Copy
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] text-blue-700 dark:text-blue-300">
                            💡 <strong>Important:</strong> Enter this root platform URL into the Meta Developer App Dashboard. Meta will deliver all events here, and Krayin dynamically routes them to tenant databases based on phone number or page ID.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <!-- Manual Connection Modal -->
    <div id="manual-connect-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    @lang('unified_inbox::app.channels.manual-modal-title')
                </h3>
                <button type="button" onclick="document.getElementById('manual-connect-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.unified_inbox.channels.manual') }}" method="POST" class="mt-4 flex flex-col gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Channel</label>
                    <select name="channel" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="whatsapp">WhatsApp Business</option>
                        <option value="messenger">Facebook Messenger</option>
                        <option value="instagram">Instagram Direct</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Connection Name</label>
                    <input type="text" name="name" required placeholder="e.g. Sales WhatsApp Support" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">External ID (Phone Number ID / Page ID / IG ID)</label>
                    <input type="text" name="external_id" required placeholder="e.g. 109823482938472" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Parent ID (WABA ID or FB Page ID, optional)</label>
                    <input type="text" name="extra_id" placeholder="e.g. WABA ID" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Meta System User / Permanent Access Token</label>
                    <textarea name="access_token" required rows="3" placeholder="EAAB..." class="mt-1 w-full rounded-lg border border-gray-300 p-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('manual-connect-modal').classList.add('hidden')" class="rounded-lg px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                        @lang('unified_inbox::app.channels.save-connection')
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Meta JS SDK & Embedded Signup v4 Initialization -->
    @pushOnce('scripts')
    <script>
        window.fbAsyncInit = function() {
            FB.init({
                appId: '{{ $metaConfig["app_id"] ?: "" }}',
                autoLogAppEvents: true,
                xfbml: true,
                version: '{{ $metaConfig["version"] ?: "v21.0" }}'
            });
        };

        // Load Meta Facebook SDK
        (function(d, s, id){
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) {return;}
            js = d.createElement(s); js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));

        // Listen for Embedded Signup v4 session messages
        let sessionInfo = {};
        window.addEventListener('message', function(event) {
            if (event.origin.includes('facebook.com') || event.origin.includes('meta.com')) {
                try {
                    const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                    if (data.type === 'WA_EMBEDDED_SIGNUP') {
                        if (data.event === 'FINISH') {
                            sessionInfo = data.data || {};
                        }
                    }
                } catch(e) {}
            }
        });

        function launchMetaEmbeddedSignup(channel) {
            const appId = '{{ $metaConfig["app_id"] }}';
            const whatsappConfigId = '{{ $metaConfig["whatsapp_config_id"] }}';
            const messagingConfigId = '{{ $metaConfig["messaging_config_id"] }}';

            if (!appId) {
                alert('Meta App ID is not configured. Please configure UNIFIED_INBOX_META_APP_ID in .env or use Manual / Sandbox connect.');
                document.getElementById('manual-connect-modal').classList.remove('hidden');
                return;
            }

            const configId = (channel === 'whatsapp' && whatsappConfigId) 
                ? whatsappConfigId 
                : (messagingConfigId || whatsappConfigId);

            if (typeof FB === 'undefined') {
                alert('Meta SDK is loading, please try again in a few seconds or use Manual connect.');
                return;
            }

            const loginOptions = {
                response_type: 'code',
                override_default_response_type: true
            };

            if (configId) {
                loginOptions.config_id = configId;
            } else {
                loginOptions.scope = 'whatsapp_business_management,whatsapp_business_messaging,pages_show_list,pages_manage_metadata,pages_messaging,instagram_basic,instagram_manage_messages';
            }

            FB.login(function(response) {
                if (response.authResponse && response.authResponse.code) {
                    const code = response.authResponse.code;
                    let callbackUrl = '{{ route("admin.unified_inbox.channels.meta.callback") }}?code=' + encodeURIComponent(code);

                    if (sessionInfo.waba_id) {
                        callbackUrl += '&waba_id=' + encodeURIComponent(sessionInfo.waba_id);
                    }
                    if (sessionInfo.phone_number_id) {
                        callbackUrl += '&phone_number_id=' + encodeURIComponent(sessionInfo.phone_number_id);
                    }

                    window.location.href = callbackUrl;
                } else {
                    console.log('User cancelled login or did not fully authorize.');
                }
            }, loginOptions);
        }
    </script>
    @endpushOnce
</x-admin::layouts>
