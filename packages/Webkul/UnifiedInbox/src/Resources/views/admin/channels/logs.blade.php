<x-admin::layouts>
    <x-slot:title>
        @lang('unified_inbox::app.channels.logs-title', ['channel' => $title])
    </x-slot>

    @include('unified_inbox::partials.styles')
    @include('unified_inbox::partials.styles-pro')

    <div class="ui-workspace" v-pre>
        <header class="ui-header">
            <div>
                <h1>@lang('unified_inbox::app.channels.logs-title', ['channel' => $title])</h1>
                <p class="ui-muted">@lang('unified_inbox::app.channels.logs-description')</p>
            </div>
            <div class="ui-actions">
                <a class="secondary-button" href="{{ route('admin.unified_inbox.channels.index') }}">
                    ← @lang('unified_inbox::app.channels.back-to-channels')
                </a>
            </div>
        </header>

        <div class="ui-channel-content">
            <section class="ui-channels ui-numhist">
                <div class="ui-channels-head">
                    <h2>@lang('unified_inbox::app.channels.logs-history')</h2>
                    <p class="font-mono ui-muted">Webhook: {{ $webhookUrl }}</p>
                </div>
                @if (count($history))
                    <table>
                        <thead>
                            <tr><th>Number / account</th><th>ID</th><th>Status</th><th>Connected</th><th>Disconnected</th><th>Conversations</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="font-mono">{{ $row['external_id'] }}@if ($row['waba_id']) <span class="ui-muted">· WABA {{ $row['waba_id'] }}</span>@endif</td>
                                    <td class="{{ $row['current'] ? 'is-current' : 'ui-muted' }}">{{ $row['current'] ? 'Current' : 'Archived' }}</td>
                                    <td>{{ $row['connected_at']?->format('d M Y, H:i') ?? '—' }}</td>
                                    <td>{{ $row['disconnected_at']?->format('d M Y, H:i') ?? ($row['current'] ? '—' : 'Unknown') }}</td>
                                    <td><a href="{{ route('admin.unified_inbox.index', ['channel' => $channel]) }}">{{ $row['conversations'] }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="ui-muted">@lang('unified_inbox::app.channels.logs-empty')</p>
                @endif
            </section>

            <section class="ui-channels ui-numhist">
                <div class="ui-channels-head">
                    <h2>@lang('unified_inbox::app.channels.logs-events')</h2>
                    <p>@lang('unified_inbox::app.channels.logs-events-note')</p>
                    @if ($stats->count())
                        <div class="ui-logs-stats">
                            @foreach ($stats as $s)
                                <span>{{ ucfirst($s->direction) }} · {{ $s->status }}: <strong>{{ $s->total }}</strong></span>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($events->count())
                    <table>
                        <thead>
                            <tr><th>Time</th><th>Direction</th><th>Contact</th><th>Account</th><th>Message</th><th>Status</th><th>Status updated</th><th>Message ID</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $e)
                                <tr>
                                    <td>{{ $e->occurred_at?->format('d M Y, H:i') }}</td>
                                    <td>{{ $e->direction === 'inbound' ? '↓ Inbound' : '↑ Outbound' }}</td>
                                    <td>{{ $e->conversation?->customer_name ?? '—' }}</td>
                                    <td class="font-mono">{{ $e->conversation?->account_id }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit((string) $e->body, 80) ?: (! empty($e->attachments) ? '[attachment]' : '—') }}</td>
                                    <td class="{{ $e->status === 'failed' ? 'is-failed' : '' }}">{{ $e->status }}</td>
                                    <td>{{ $e->updated_at?->format('d M Y, H:i') }}</td>
                                    <td class="font-mono ui-muted">{{ \Illuminate\Support\Str::limit((string) $e->external_id, 28) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div style="margin-top:12px">{{ $events->links() }}</div>
                @else
                    <p class="ui-muted">@lang('unified_inbox::app.channels.logs-empty')</p>
                @endif
            </section>
        </div>
    </div>
</x-admin::layouts>
