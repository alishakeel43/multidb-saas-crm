<x-admin::layouts>
    <x-slot:title>Deliverability</x-slot>

    @include('marketing_automation::partials.nav')

    <div class="mb-4 grid gap-3 md:grid-cols-4">
        @foreach ($counts as $label => $count)
            <div class="box-shadow rounded-lg bg-white p-3 dark:bg-gray-900">
                <div class="text-xs uppercase text-gray-500">{{ $label }}</div>
                <div class="text-xl font-semibold dark:text-white">{{ $count }}</div>
            </div>
        @endforeach
    </div>

    <div class="space-y-2">
        @forelse ($messages as $message)
            <div class="box-shadow flex items-center justify-between rounded-lg bg-white p-3 text-sm dark:bg-gray-900 dark:text-white">
                <div>
                    {{ $message->email }} · {{ $message->subject }} · {{ $message->status }}
                    @if ($message->sent_at) · sent {{ $message->sent_at->format('d M H:i') }} @endif
                </div>
                @if ($message->status !== 'bounced')
                    <form method="POST" action="{{ route('admin.marketing_automation.deliverability.bounce', $message->id) }}">
                        @csrf
                        <button class="transparent-button" type="submit">Mark bounced</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">No messages yet.</p>
        @endforelse
    </div>
</x-admin::layouts>
