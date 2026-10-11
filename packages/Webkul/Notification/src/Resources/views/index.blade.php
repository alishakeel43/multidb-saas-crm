<x-admin::layouts>
    <x-slot:title>@lang('notification::app.index.title')</x-slot>

    <div class="mb-4 flex items-center justify-between">
        <p class="text-2xl font-semibold dark:text-white">@lang('notification::app.index.title')</p>
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf
            <button class="secondary-button" type="submit">@lang('notification::app.index.mark-all')</button>
        </form>
    </div>

    <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
        <div class="space-y-2 text-sm dark:text-white">
            @forelse ($notifications as $notification)
                <a
                    href="{{ $notification->link ?: '#' }}"
                    class="block rounded border p-3 dark:border-gray-700 {{ $notification->read_at ? 'opacity-60' : '' }}"
                >
                    <div class="font-medium">{{ $notification->title }}</div>
                    <div class="text-gray-500">{{ $notification->body }}</div>
                    <div class="mt-1 text-xs text-gray-400">{{ $notification->created_at?->diffForHumans() }} · {{ $notification->type }}</div>
                </a>
            @empty
                <p class="text-gray-500">@lang('notification::app.index.empty')</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    </div>
</x-admin::layouts>
