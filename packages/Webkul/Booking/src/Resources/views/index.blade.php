@php
    $fieldClass = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200';

    $statusBadge = function ($booking): string {
        return match (true) {
            $booking->status->equals(\Webkul\Booking\States\Confirmed::class) => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            $booking->status->equals(\Webkul\Booking\States\Pending::class) => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            $booking->status->equals(\Webkul\Booking\States\Cancelled::class) => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            $booking->status->equals(\Webkul\Booking\States\Completed::class) => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    };
@endphp

<x-admin::layouts>
    <x-slot:title>@lang('booking::app.index.title')</x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <div class="text-xl font-bold dark:text-white">
                    @lang('booking::app.index.title')
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @lang('booking::app.index.subtitle')
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('bookings.public.show') }}"
                    target="_blank"
                    class="transparent-button"
                >
                    @lang('booking::app.index.public-link')
                </a>
                <a
                    href="{{ route('admin.settings.booking.index') }}"
                    class="secondary-button"
                >
                    @lang('booking::app.index.open-settings')
                </a>
            </div>
        </div>

        <div class="grid items-start gap-4 xl:grid-cols-12">
            {{-- Book form --}}
            <div class="box-shadow rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 xl:col-span-5">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('booking::app.index.create')
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @lang('booking::app.index.staff-hint')
                    </p>
                </div>

                <div class="p-5">
                    @if ($services->isEmpty() || $users->isEmpty())
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center dark:border-gray-700 dark:bg-gray-950">
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                                @lang('booking::app.index.no-services')
                            </p>
                            <a href="{{ route('admin.settings.booking.index') }}" class="primary-button inline-flex">
                                @lang('booking::app.index.open-settings')
                            </a>
                        </div>
                    @else
                        <x-admin::form :action="route('admin.bookings.store')">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        @lang('booking::app.index.service') <span class="text-red-500">*</span>
                                    </label>
                                    <select name="booking_service_id" class="{{ $fieldClass }}" required>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->id }}">
                                                {{ $service->name }} ({{ $service->duration_minutes }}m)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        @lang('booking::app.index.staff') <span class="text-red-500">*</span>
                                    </label>
                                    <select name="user_id" class="{{ $fieldClass }}" required>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        @lang('booking::app.index.starts-at') <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="datetime-local"
                                        name="starts_at"
                                        class="{{ $fieldClass }}"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        @lang('booking::app.index.guest-name')
                                    </label>
                                    <input
                                        type="text"
                                        name="guest_name"
                                        class="{{ $fieldClass }}"
                                        placeholder="@lang('booking::app.index.guest-name')"
                                    >
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        @lang('booking::app.index.guest-email')
                                    </label>
                                    <input
                                        type="email"
                                        name="guest_email"
                                        class="{{ $fieldClass }}"
                                        placeholder="name@example.com"
                                    >
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        @lang('booking::app.index.notes')
                                    </label>
                                    <textarea
                                        name="notes"
                                        rows="3"
                                        class="{{ $fieldClass }} min-h-[88px] resize-y"
                                        placeholder="@lang('booking::app.index.notes-placeholder')"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input
                                        type="checkbox"
                                        name="auto_confirm"
                                        value="1"
                                        checked
                                        class="rounded border-gray-300 text-brandColor focus:ring-brandColor"
                                    >
                                    @lang('booking::app.index.auto-confirm')
                                </label>

                                <button class="primary-button" type="submit">
                                    @lang('booking::app.index.save')
                                </button>
                            </div>
                        </x-admin::form>
                    @endif
                </div>
            </div>

            {{-- Appointments list --}}
            <div class="box-shadow rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 xl:col-span-7">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('booking::app.index.list')
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('booking::app.index.list-hint', ['count' => $bookings->count()])
                        </p>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($bookings as $booking)
                        <div class="flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-950/60 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $booking->service?->name ?? '—' }}
                                    </p>
                                    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-medium {{ $statusBadge($booking) }}">
                                        {{ $booking->status->label() }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="icon-calendar text-base"></span>
                                        {{ $booking->starts_at?->format('D, M j Y · H:i') }}
                                        –
                                        {{ $booking->ends_at?->format('H:i') }}
                                    </span>

                                    @if ($booking->user)
                                        <span class="inline-flex items-center gap-1">
                                            <span class="icon-user text-base"></span>
                                            {{ $booking->user->name }}
                                        </span>
                                    @endif

                                    @if ($booking->guest_name || $booking->guest_email)
                                        <span class="inline-flex items-center gap-1 truncate">
                                            <span class="icon-mail text-base"></span>
                                            {{ $booking->guest_name ?: $booking->guest_email }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                @if ($booking->status->equals(\Webkul\Booking\States\Pending::class))
                                    <form method="POST" action="{{ route('admin.bookings.confirm', $booking->id) }}">
                                        @csrf
                                        <button class="secondary-button !px-3 !py-1.5" type="submit">
                                            @lang('booking::app.index.confirm')
                                        </button>
                                    </form>
                                @endif

                                @if (! $booking->status->equals(\Webkul\Booking\States\Cancelled::class)
                                    && ! $booking->status->equals(\Webkul\Booking\States\Completed::class))
                                    <form method="POST" action="{{ route('admin.bookings.reschedule', $booking->id) }}" class="flex items-center gap-1">
                                        @csrf
                                        <input
                                            type="datetime-local"
                                            name="starts_at"
                                            required
                                            class="rounded border border-gray-300 px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-950"
                                            value="{{ $booking->starts_at?->format('Y-m-d\\TH:i') }}"
                                        >
                                        <button class="secondary-button !px-3 !py-1.5" type="submit">
                                            @lang('booking::app.index.reschedule')
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.bookings.cancel', $booking->id) }}">
                                        @csrf
                                        <button
                                            class="transparent-button !px-3 !py-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                            type="submit"
                                        >
                                            @lang('booking::app.index.cancel')
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                @lang('booking::app.index.empty')
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                @lang('booking::app.index.empty-hint')
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
