<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.channels.title')
    </x-slot>

    @include('unified_inbox::partials.styles')
    @include('unified_inbox::partials.styles-pro')

    <div class="ui-workspace" v-pre>
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
            <div class="ui-channels">
                <div class="ui-channels-head">
                    <h2>Messaging channels</h2>
                    <p>Connect one account per channel to receive and reply to messages in Unified Inbox.</p>
                </div>

                <div class="ui-channels-grid">
                    @foreach ($channels as $key => $channel)
                        @php
                            $conn = $channel['connection'];
                            $isConnected = $conn && $conn->status === 'connected';
                        @endphp
                        <section class="ui-channels-card {{ $isConnected ? 'is-connected' : '' }}">
                            <div class="ui-channels-body">
                                <div class="ui-channels-top">
                                    <span class="ui-channel ui-channel-{{ $key }} ui-channels-icon" aria-hidden="true">
                                        @include('unified_inbox::partials.channel-icon', ['channel' => $key])
                                    </span>
                                    <span class="ui-channels-badge {{ $isConnected ? 'is-on' : '' }}"><i></i>{{ $isConnected ? trans('unified_inbox::app.channels.connected') : 'Not connected' }}</span>
                                </div>

                                <h3 class="ui-channels-title">{{ $channel['title'] }}</h3>

                                @if ($isConnected)
                                    <p class="ui-channels-account" title="{{ $conn->external_id }}">{{ $conn->name ?: $conn->external_id }}</p>
                                @endif

                                <p class="ui-channels-desc">{{ $channel['desc'] }}</p>
                            </div>

                            <div class="ui-channels-foot">
                                @if ($isConnected)
                                    <form action="{{ route('admin.unified_inbox.channels.disconnect', $conn->id) }}" method="POST" onsubmit="return confirm('{{ trans('unified_inbox::app.channels.disconnect-confirm') }}')">
                                        @csrf
                                        <button type="submit" class="ui-channels-btn ui-channels-disconnect">@lang('unified_inbox::app.channels.disconnect')</button>
                                    </form>
                                @else
                                    <button type="button" onclick="launchMetaEmbeddedSignup('{{ $key }}')" class="ui-channels-btn ui-channels-connect">Connect</button>
                                @endif
                                <a href="{{ route('admin.unified_inbox.channels.logs', $key) }}" class="secondary-button ui-channels-logs">@lang('unified_inbox::app.channels.open-logs')</a>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    @pushOnce('scripts')
    <script>
        const metaAppId = "{{ config('unified-inbox.meta.app_id') }}";
        const metaVersion = "{{ config('unified-inbox.meta.version', 'v21.0') }}";
        const waConfigId = "{{ config('unified-inbox.meta.whatsapp_config_id') }}";
        const msgConfigId = "{{ config('unified-inbox.meta.messaging_config_id') }}";
        const metaOAuthStates = @js($oauthStates);
        const metaCallbackUrl = @js(route('admin.unified_inbox.channels.meta.callback'));
        let activeMetaSignup = null;

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

        window.addEventListener('message', function(event) {
            if (!activeMetaSignup || !['https://www.facebook.com', 'https://web.facebook.com'].includes(event.origin)) {
                return;
            }

            let data;
            try {
                data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
            } catch (error) {
                return;
            }

            if (!data || data.type !== 'WA_EMBEDDED_SIGNUP') {
                return;
            }

            if (data.event === 'CANCEL' || data.event === 'ERROR') {
                activeMetaSignup = null;
                alert('Meta signup was cancelled or could not be completed.');
                return;
            }

            if (typeof data.event === 'string' && (data.event === 'FINISH' || data.event.startsWith('FINISH_'))) {
                const signupData = data.data && typeof data.data === 'object' ? data.data : data;
                const wabaId = signupData.waba_id || signupData.wabaId;
                const phoneNumberId = signupData.phone_number_id || signupData.phoneNumberId;

                if (wabaId && phoneNumberId) {
                    activeMetaSignup.wabaId = String(wabaId);
                    activeMetaSignup.phoneNumberId = String(phoneNumberId);
                    finishMetaSignup();
                }
            }
        });

        function finishMetaSignup() {
            if (!activeMetaSignup || activeMetaSignup.redirected || !activeMetaSignup.code) {
                return;
            }

            if (activeMetaSignup.channel === 'whatsapp' && (!activeMetaSignup.wabaId || !activeMetaSignup.phoneNumberId)) {
                return;
            }

            const params = new URLSearchParams({
                code: activeMetaSignup.code,
                channel: activeMetaSignup.channel,
                state: activeMetaSignup.state
            });

            if (activeMetaSignup.wabaId) {
                params.set('waba_id', activeMetaSignup.wabaId);
                params.set('phone_number_id', activeMetaSignup.phoneNumberId);
            }

            activeMetaSignup.redirected = true;
            window.location.assign(metaCallbackUrl + '?' + params.toString());
        }

        function launchMetaEmbeddedSignup(channel) {
            const configId = (channel === 'whatsapp') ? waConfigId : msgConfigId;

            if (!metaAppId) {
                alert('Meta App ID is not configured. Please set UNIFIED_INBOX_META_APP_ID in your environment.');
                return;
            }

            if (!metaOAuthStates[channel]) {
                alert('The Meta signup session expired. Reload this page and try again.');
                return;
            }

            activeMetaSignup = {
                channel: channel,
                state: metaOAuthStates[channel],
                code: null,
                wabaId: null,
                phoneNumberId: null,
                redirected: false
            };

            if (window.FB) {
                FB.login(function(response) {
                    if (response.authResponse && response.authResponse.code && activeMetaSignup) {
                        activeMetaSignup.code = response.authResponse.code;
                        finishMetaSignup();
                    } else {
                        activeMetaSignup = null;
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
