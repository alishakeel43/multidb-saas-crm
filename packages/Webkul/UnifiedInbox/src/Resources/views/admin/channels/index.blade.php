<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.channels.title')
    </x-slot>

    @include('unified_inbox::partials.styles')

    <div class="ui-workspace">
        <header class="ui-header">
            <div>
                <h1>@lang('unified_inbox::app.channels.title')</h1>
                <p class="ui-muted">@lang('unified_inbox::app.channels.description')</p>
            </div>
            <div class="ui-actions">
                <a class="secondary-button" href="{{ route('admin.unified_inbox.index') }}">
                    ← @lang('unified_inbox::app.inbox.title')
                </a>
            </div>
        </header>

        <div class="ui-channel-content">
            <!-- 3 Channel Cards (WhatsApp, Messenger, Instagram) -->
            <div class="ui-channel-cards">
                @foreach ($channels as $key => $channel)
                    @php
                        $conn = $channel['connection'];
                        $isConnected = $conn && $conn->status === 'connected';
                    @endphp
                    <section class="ui-card">
                        <span class="ui-channel ui-channel-{{ $key }}" aria-hidden="true">
                            {{ strtoupper(substr($channel['title'], 0, 1)) }}
                        </span>
                        <div>
                            <h2 class="font-bold text-base">{{ $channel['title'] }}</h2>
                            <p class="ui-muted mt-1">{{ $channel['desc'] }}</p>
                        </div>

                        <div class="mt-auto pt-3 border-t border-slate-100 dark:border-slate-800">
                            @if ($isConnected)
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        @lang('unified_inbox::app.channels.connected')
                                    </span>
                                    <form action="{{ route('admin.unified_inbox.channels.disconnect', $conn->id) }}" method="POST" onsubmit="return confirm('{{ trans('unified_inbox::app.channels.disconnect-confirm') }}')">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">
                                            @lang('unified_inbox::app.channels.disconnect')
                                        </button>
                                    </form>
                                </div>
                            @else
                                <button
                                    type="button"
                                    onclick="launchMetaEmbeddedSignup('{{ $key }}')"
                                    class="primary-button w-full text-xs py-2"
                                >
                                    Connect {{ $channel['title'] }}
                                </button>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>

            <!-- Connected Accounts List -->
            <h2 class="font-bold text-base mb-3">Connected Channel Accounts</h2>
            @php
                $connectedList = collect($channels)->pluck('connection')->filter();
            @endphp
            @forelse ($connectedList as $conn)
                <section class="ui-card ui-connection mb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-sm">{{ $conn->name }} · {{ ucfirst($conn->channel) }}</h3>
                            <p class="ui-muted text-xs">External ID: <span class="font-mono">{{ $conn->external_id }}</span> · Connected {{ $conn->created_at->diffForHumans() }}</p>
                        </div>
                        <form action="{{ route('admin.unified_inbox.channels.disconnect', $conn->id) }}" method="POST" onsubmit="return confirm('{{ trans('unified_inbox::app.channels.disconnect-confirm') }}')">
                            @csrf
                            <button type="submit" class="secondary-button text-xs py-1.5 px-3 text-red-600">
                                Disconnect
                            </button>
                        </form>
                    </div>
                </section>
            @empty
                <p class="ui-muted text-xs mb-4">No channels connected yet. Click any button above to connect with Meta.</p>
            @endforelse

            <!-- Centralized Webhook Multi-Tenant Routing Info -->
            <details class="ui-setup" open>
                <summary>Centralized Meta Developer Setup (Multi-Tenant Routing)</summary>
                <p class="ui-muted">
                    Configure this single central URL in your Meta App Dashboard. All tenant channels (WhatsApp, Messenger, Instagram) are automatically resolved and routed to their isolated tenant databases.
                </p>

                <div class="mt-3 flex flex-col gap-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <strong class="min-w-[120px] text-xs">Callback URL:</strong>
                        <code class="rounded bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ $centralWebhookUrl }}
                        </code>
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ $centralWebhookUrl }}'); alert('Central Webhook URL copied!');"
                            class="secondary-button text-xs py-0.5 px-2"
                        >
                            Copy
                        </button>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <strong class="min-w-[120px] text-xs">Verify Token:</strong>
                        <code class="rounded bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ config('unified-inbox.meta.verify_token') ?: 'krayin_crm_unified_inbox' }}
                        </code>
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ config('unified-inbox.meta.verify_token') ?: 'krayin_crm_unified_inbox' }}'); alert('Verify Token copied!');"
                            class="secondary-button text-xs py-0.5 px-2"
                        >
                            Copy
                        </button>
                    </div>
                </div>

                <p class="ui-muted text-xs mt-3">
                    💡 <strong>Central Root Architecture:</strong> Incoming messages to WhatsApp, Messenger, and Instagram are securely received at this root URL and forwarded into the tenant database via landlord routing.
                </p>
            </details>
        </div>
    </div>

    @pushOnce('scripts')
    <script>
        const metaAppId = "{{ config('unified-inbox.meta.app_id') }}";
        const metaVersion = "{{ config('unified-inbox.meta.version', 'v21.0') }}";
        const waConfigId = "{{ config('unified-inbox.meta.whatsapp_config_id') }}";
        const msgConfigId = "{{ config('unified-inbox.meta.messaging_config_id') }}";

        window.fbAsyncInit = function() {
            if (metaAppId && window.FB) {
                FB.init({
                    appId: metaAppId,
                    cookie: true,
                    xfbml: false,
                    version: metaVersion
                });
            }
        };

        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));

        function launchMetaEmbeddedSignup(channel) {
            const configId = (channel === 'whatsapp') ? waConfigId : msgConfigId;

            if (!metaAppId) {
                alert('Meta App ID is not configured. Please set UNIFIED_INBOX_META_APP_ID in your environment.');
                return;
            }

            if (window.FB) {
                FB.login(function(response) {
                    if (response.authResponse && response.authResponse.code) {
                        window.location.href = "{{ route('admin.unified_inbox.channels.meta.callback') }}?code=" + response.authResponse.code + "&channel=" + channel;
                    }
                }, {
                    config_id: configId,
                    response_type: 'code',
                    override_default_response_type: true,
                    extras: {
                        sessionInfoVersion: '3'
                    }
                });
            } else {
                alert('Meta SDK is still loading. Please try again in a few moments.');
            }
        }
    </script>
    @endPushOnce
</x-admin::layouts>
