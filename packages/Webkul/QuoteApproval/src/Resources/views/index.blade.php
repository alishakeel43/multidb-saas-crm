<x-admin::layouts>
    <x-slot:title>Quote approvals</x-slot>

    <p class="mb-4 text-2xl font-semibold dark:text-white">Quote approvals</p>

    <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
        <div class="space-y-3 text-sm dark:text-white">
            @forelse ($approvals as $approval)
                <div class="flex flex-wrap items-center justify-between gap-2 rounded border p-3 dark:border-gray-700">
                    <div>
                        <div class="font-medium">#{{ $approval->quote_id }} {{ $approval->quote?->subject }}</div>
                        <div class="text-gray-500">Status: {{ $approval->status }}</div>
                    </div>
                    <div class="flex gap-2">
                        @if ($approval->status === 'draft' || $approval->status === 'rejected')
                            <form method="POST" action="{{ route('admin.quotes.approvals.submit', $approval->quote_id) }}">
                                @csrf
                                <button class="secondary-button" type="submit">Submit</button>
                            </form>
                        @endif
                        @if ($approval->status === 'pending')
                            <form method="POST" action="{{ route('admin.quotes.approvals.approve', $approval->quote_id) }}">
                                @csrf
                                <button class="primary-button" type="submit">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.quotes.approvals.reject', $approval->quote_id) }}">
                                @csrf
                                <button class="transparent-button" type="submit">Reject</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-gray-500">No quote approvals yet. Create a quote to start the workflow.</p>
            @endforelse
        </div>
    </div>
</x-admin::layouts>
