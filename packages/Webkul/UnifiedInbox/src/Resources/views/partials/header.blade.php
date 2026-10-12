<header class="ui-header">
    <div>
        <h1>@lang('unified_inbox::app.inbox.title')</h1>
        <p class="ui-muted">Omnichannel Hub</p>
    </div>
    <div class="ui-actions">
        <a class="primary-button" href="{{ route('admin.unified_inbox.channels.index') }}">
            ＋ @lang('unified_inbox::app.inbox.connect-channels')
        </a>
    </div>
</header>

<form method="GET" action="{{ route('admin.unified_inbox.index') }}" class="ui-filters">
    <label class="ui-search">
        <span class="sr-only">Search</span>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search chats, contacts..." maxlength="200">
    </label>
    <input type="hidden" name="channel" value="{{ request('channel') }}">
    <label class="inline-flex items-center gap-2 text-xs">
        <input type="checkbox" name="unread" value="1" @checked(request()->boolean('unread'))>
        Unread
    </label>
    <label>
        <span class="sr-only">Status</span>
        <select name="status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="open" @selected(request('status') === 'open')>Open</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="closed" @selected(request('status') === 'closed')>Closed</option>
        </select>
    </label>
    <button class="secondary-button" type="submit">Filter</button>
</form>

<nav class="ui-tabs" aria-label="Channels">
    <a class="ui-tab" href="{{ route('admin.unified_inbox.index', request()->only('search', 'status', 'unread')) }}" @if(!request('channel')) aria-current="page" @endif>
        <span class="ui-channel" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => 'all'])</span>
        <span>@lang('unified_inbox::app.inbox.all-channels')</span>
    </a>
    @foreach ($channels as $key => $channel)
        <a class="ui-tab" href="{{ route('admin.unified_inbox.index', array_merge(request()->only('search', 'status', 'unread'), ['channel' => $key])) }}" @if(request('channel') === $key) aria-current="page" @endif>
            <span class="ui-channel ui-channel-{{ $key }}" aria-hidden="true">@include('unified_inbox::partials.channel-icon', ['channel' => $key])</span>
            <span>{{ $channel['label'] ?? ucfirst($key) }}</span>
        </a>
    @endforeach
</nav>
