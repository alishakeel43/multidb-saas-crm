<x-admin::layouts>
    <x-slot:title>@lang('booking::app.index.title')</x-slot>

    <div class="mb-4 flex items-center justify-between">
        <p class="text-2xl font-semibold dark:text-white">@lang('booking::app.index.title')</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 font-medium dark:text-white">@lang('booking::app.index.create')</p>
            <x-admin::form :action="route('admin.bookings.store')">
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Service</label>
                    <select name="booking_service_id" class="w-100" required>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->duration_minutes }}m)</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Staff</label>
                    <select name="user_id" class="w-100">
                        <option value="">—</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Starts at</label>
                    <input type="datetime-local" name="starts_at" class="w-100" required>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Guest name</label>
                    <input type="text" name="guest_name" class="w-100">
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Guest email</label>
                    <input type="email" name="guest_email" class="w-100">
                </div>
                <label class="mb-3 flex items-center gap-2 text-sm dark:text-white">
                    <input type="checkbox" name="auto_confirm" value="1" checked> Auto-confirm
                </label>
                <button class="primary-button" type="submit">Save</button>
            </x-admin::form>
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <div class="space-y-3">
                @forelse ($bookings as $booking)
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="font-medium dark:text-white">
                            {{ $booking->service?->name }} · {{ $booking->status->label() }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $booking->starts_at }} → {{ $booking->ends_at }}
                            @if ($booking->user) · {{ $booking->user->name }} @endif
                        </p>
                        <div class="mt-2 flex gap-2">
                            @if ($booking->status->equals(\Webkul\Booking\States\Pending::class))
                                <form method="POST" action="{{ route('admin.bookings.confirm', $booking->id) }}">
                                    @csrf
                                    <button class="primary-button" type="submit">Confirm</button>
                                </form>
                            @endif
                            @if (! $booking->status->equals(\Webkul\Booking\States\Cancelled::class))
                                <form method="POST" action="{{ route('admin.bookings.cancel', $booking->id) }}">
                                    @csrf
                                    <button class="transparent-button" type="submit">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No bookings yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin::layouts>
