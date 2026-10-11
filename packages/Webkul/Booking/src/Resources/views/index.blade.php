<x-admin::layouts>
    <x-slot:title>@lang('booking::app.index.title')</x-slot>

    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-2xl font-semibold dark:text-white">@lang('booking::app.index.title')</p>
        <a
            href="{{ route('admin.settings.booking.index') }}"
            class="transparent-button"
        >
            @lang('booking::app.index.open-settings')
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 font-medium dark:text-white">@lang('booking::app.index.create')</p>

            @if ($services->isEmpty() || $users->isEmpty())
                <p class="mb-3 text-sm text-red-600 dark:text-red-400">
                    @lang('booking::app.index.no-services')
                </p>
                <a href="{{ route('admin.settings.booking.index') }}" class="primary-button inline-flex">
                    @lang('booking::app.index.open-settings')
                </a>
            @else
                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                    @lang('booking::app.index.staff-hint')
                </p>

                <x-admin::form :action="route('admin.bookings.store')">
                    <div class="mb-2">
                        <label class="text-sm dark:text-white">@lang('booking::app.index.service')</label>
                        <select name="booking_service_id" class="w-100" required>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">
                                    {{ $service->name }} ({{ $service->duration_minutes }}m)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="text-sm dark:text-white">@lang('booking::app.index.staff') *</label>
                        <select name="user_id" class="w-100" required>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="text-sm dark:text-white">@lang('booking::app.index.starts-at')</label>
                        <input type="datetime-local" name="starts_at" class="w-100" required>
                    </div>
                    <div class="mb-2">
                        <label class="text-sm dark:text-white">@lang('booking::app.index.guest-name')</label>
                        <input type="text" name="guest_name" class="w-100">
                    </div>
                    <div class="mb-2">
                        <label class="text-sm dark:text-white">@lang('booking::app.index.guest-email')</label>
                        <input type="email" name="guest_email" class="w-100">
                    </div>
                    <div class="mb-2">
                        <label class="text-sm dark:text-white">@lang('booking::app.index.notes')</label>
                        <textarea name="notes" class="w-100" rows="2"></textarea>
                    </div>
                    <label class="mb-3 flex items-center gap-2 text-sm dark:text-white">
                        <input type="checkbox" name="auto_confirm" value="1" checked>
                        @lang('booking::app.index.auto-confirm')
                    </label>
                    <button class="primary-button" type="submit">@lang('booking::app.index.save')</button>
                </x-admin::form>
            @endif
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 font-medium dark:text-white">@lang('booking::app.index.list')</p>
            <div class="space-y-3">
                @forelse ($bookings as $booking)
                    <div class="rounded border p-3 dark:border-gray-700">
                        <p class="font-medium dark:text-white">
                            {{ $booking->service?->name }} · {{ $booking->status->label() }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $booking->starts_at }} → {{ $booking->ends_at }}
                            @if ($booking->user)
                                · {{ $booking->user->name }}
                            @endif
                            @if ($booking->guest_name)
                                · {{ $booking->guest_name }}
                            @endif
                        </p>
                        <div class="mt-2 flex gap-2">
                            @if ($booking->status->equals(\Webkul\Booking\States\Pending::class))
                                <form method="POST" action="{{ route('admin.bookings.confirm', $booking->id) }}">
                                    @csrf
                                    <button class="primary-button" type="submit">@lang('booking::app.index.confirm')</button>
                                </form>
                            @endif
                            @if (! $booking->status->equals(\Webkul\Booking\States\Cancelled::class))
                                <form method="POST" action="{{ route('admin.bookings.cancel', $booking->id) }}">
                                    @csrf
                                    <button class="transparent-button" type="submit">@lang('booking::app.index.cancel')</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">@lang('booking::app.index.empty')</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin::layouts>
