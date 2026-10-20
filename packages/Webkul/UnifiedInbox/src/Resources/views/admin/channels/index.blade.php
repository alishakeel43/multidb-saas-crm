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

                                @if (! empty($pendingSelection) && $pendingSelection['channel'] === $key && ! empty($pendingSelection['assets']))
                                    <div class="ui-channel-picker" aria-label="{{ __('unified_inbox::app.channels.picker-title') }}">
                                        <h4>{{ __('unified_inbox::app.channels.picker-title') }}</h4>
                                        <p>
                                            {{ $key === 'instagram'
                                                ? __('unified_inbox::app.channels.picker-help-instagram')
                                                : __('unified_inbox::app.channels.picker-help-messenger') }}
                                        </p>

                                        <form method="POST" action="{{ route('admin.unified_inbox.channels.complete_selection') }}" class="ui-channel-picker-form">
                                            @csrf
                                            <input type="hidden" name="selection_id" value="{{ $pendingSelection['selection_id'] }}">
                                            <label>
                                                <span>{{ __('unified_inbox::app.channels.picker-select') }}</span>
                                                <select name="asset_id" required>
                                                    @foreach ($pendingSelection['assets'] as $asset)
                                                        <option value="{{ $asset['id'] }}">{{ $asset['name'] }} ({{ $asset['id'] }})</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <div class="ui-channel-picker-actions">
                                                <button type="submit" class="ui-channels-btn ui-channels-connect">
                                                    {{ __('unified_inbox::app.channels.picker-confirm') }}
                                                </button>
                                                <button type="submit" formaction="{{ route('admin.unified_inbox.channels.cancel_selection') }}" class="ui-channels-btn ui-channels-disconnect">
                                                    {{ __('unified_inbox::app.channels.picker-cancel') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
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
        const useDevScopesLogin = @js((bool) ($allowManualConnect ?? false));
        const metaOAuthStates = @js($oauthStates);
        const metaCallbackUrl = @js($centralOAuthRedirectUrl ?? route('unified_inbox.meta_webhook.oauth.callback'));
        const metaAcceptTokenUrl = @js(route('admin.unified_inbox.channels.accept_token'));
        const metaCsrfToken = @js(csrf_token());
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

            if (!data || typeof data !== 'object') {
                return;
            }

            // WhatsApp uses WA_EMBEDDED_SIGNUP; Messaging configs may omit type or reuse it.
            const isWhatsAppEvent = data.type === 'WA_EMBEDDED_SIGNUP';
            const isMessagingEvent = activeMetaSignup.channel !== 'whatsapp'
                && (isWhatsAppEvent || data.type === undefined || data.type === null || data.type === 'FB_EMBEDDED_SIGNUP');

            if (! isWhatsAppEvent && ! isMessagingEvent) {
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
                const rawPageIds = signupData.page_ids || signupData.pageIds || signupData.pages;
                const singlePageId = signupData.page_id || signupData.pageId;

                if (wabaId && phoneNumberId) {
                    activeMetaSignup.wabaId = String(wabaId);
                    activeMetaSignup.phoneNumberId = String(phoneNumberId);
                }

                let pageIds = [];
                if (Array.isArray(rawPageIds)) {
                    pageIds = rawPageIds.map(String).filter(Boolean);
                } else if (rawPageIds && typeof rawPageIds === 'object') {
                    pageIds = Object.values(rawPageIds).map(String).filter(Boolean);
                }
                if (singlePageId) {
                    pageIds.push(String(singlePageId));
                }
                if (pageIds.length) {
                    activeMetaSignup.pageIds = Array.from(new Set(pageIds));
                }

                activeMetaSignup.sessionFinished = true;
                finishMetaSignup();
            }
        });

        function submitMetaUserToken(accessToken) {
            if (!activeMetaSignup || activeMetaSignup.redirected) {
                return;
            }

            activeMetaSignup.redirected = true;
            if (activeMetaSignup.finishTimer) {
                clearTimeout(activeMetaSignup.finishTimer);
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = metaAcceptTokenUrl;
            form.style.display = 'none';

            const fields = {
                _token: metaCsrfToken,
                channel: activeMetaSignup.channel,
                state: activeMetaSignup.state,
                access_token: accessToken
            };

            Object.keys(fields).forEach(function (key) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }

        function finishMetaSignup() {
            if (!activeMetaSignup || activeMetaSignup.redirected || !activeMetaSignup.code) {
                return;
            }

            if (activeMetaSignup.channel === 'whatsapp' && (!activeMetaSignup.wabaId || !activeMetaSignup.phoneNumberId)) {
                return;
            }

            // Messaging: wait briefly for sessionInfo page_ids before redirecting.
            if (activeMetaSignup.channel !== 'whatsapp'
                && !activeMetaSignup.sessionFinished
                && !activeMetaSignup.forceFinish
            ) {
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

            if (Array.isArray(activeMetaSignup.pageIds)) {
                activeMetaSignup.pageIds.forEach(function (pageId) {
                    params.append('page_ids[]', pageId);
                });
            }

            activeMetaSignup.redirected = true;
            if (activeMetaSignup.finishTimer) {
                clearTimeout(activeMetaSignup.finishTimer);
            }
            window.location.assign(metaCallbackUrl + '?' + params.toString());
        }

        function scheduleMessagingFinishFallback() {
            if (!activeMetaSignup || activeMetaSignup.channel === 'whatsapp') {
                return;
            }

            if (activeMetaSignup.finishTimer) {
                clearTimeout(activeMetaSignup.finishTimer);
            }

            activeMetaSignup.finishTimer = setTimeout(function () {
                if (!activeMetaSignup || activeMetaSignup.redirected) {
                    return;
                }

                activeMetaSignup.forceFinish = true;
                finishMetaSignup();
            }, 2500);
        }

        function launchMetaEmbeddedSignup(channel) {
            const configId = (channel === 'whatsapp') ? waConfigId : msgConfigId;
            const usingScopes = channel !== 'whatsapp' && useDevScopesLogin;

            if (!metaAppId) {
                alert('Meta App ID is not configured. Please set UNIFIED_INBOX_META_APP_ID in your environment.');
                return;
            }

            if (! usingScopes && ! configId) {
                alert(channel === 'whatsapp'
                    ? 'Meta WhatsApp configuration ID is missing. Set UNIFIED_INBOX_WHATSAPP_CONFIG_ID in the environment.'
                    : 'Meta Messaging configuration ID is missing. Set UNIFIED_INBOX_MESSAGING_CONFIG_ID in the environment.');
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
                pageIds: [],
                sessionFinished: false,
                forceFinish: false,
                finishTimer: null,
                redirected: false
            };

            if (window.FB) {
                // Development scopes: use short-lived access token (avoids JS SDK redirect_uri mismatch).
                // Embedded Signup (WhatsApp / production messaging): authorization code + empty redirect_uri exchange.
                const loginOptions = (channel !== 'whatsapp' && useDevScopesLogin)
                    ? {
                        // Popup returns accessToken (no per-tenant redirect). Meta App still
                        // only needs the one central Valid OAuth Redirect URI below for code flows.
                        scope: channel === 'instagram'
                            ? 'pages_show_list,pages_messaging,pages_manage_metadata,business_management,instagram_basic,instagram_manage_messages'
                            : 'pages_show_list,pages_messaging,pages_manage_metadata,business_management',
                        return_scopes: true,
                        auth_type: 'rerequest'
                    }
                    : {
                        config_id: configId,
                        response_type: 'code',
                        override_default_response_type: true,
                        extras: {
                            setup: {},
                            sessionInfoVersion: '3',
                            // One central URI for every tenant — register once in Meta App.
                            fallback_redirect_uri: metaCallbackUrl
                        }
                    };

                FB.login(function(response) {
                    if (!activeMetaSignup) {
                        return;
                    }

                    if (usingScopes && response.authResponse && response.authResponse.accessToken) {
                        submitMetaUserToken(response.authResponse.accessToken);
                        return;
                    }

                    if (response.authResponse && response.authResponse.code) {
                        activeMetaSignup.code = response.authResponse.code;
                        if (activeMetaSignup.channel === 'whatsapp') {
                            finishMetaSignup();
                        } else {
                            finishMetaSignup();
                            scheduleMessagingFinishFallback();
                        }
                        return;
                    }

                    activeMetaSignup = null;
                }, loginOptions);
            } else {
                alert('Meta SDK is still loading. Please try again in a few moments.');
            }
        }
    </script>
    @endPushOnce
</x-admin::layouts>
