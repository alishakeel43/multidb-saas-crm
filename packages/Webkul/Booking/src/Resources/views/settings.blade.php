@php
    $fieldClass = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200';
    $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $weekdayShort = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
@endphp

<x-admin::layouts>
    <x-slot:title>@lang('booking::app.menu.settings')</x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <div class="text-xl font-bold dark:text-white">
                    @lang('booking::app.menu.settings')
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @lang('booking::app.menu.settings-info')
                </p>
            </div>

            <a href="{{ route('admin.bookings.index') }}" class="secondary-button">
                @lang('booking::app.index.title')
            </a>
        </div>

        <div class="grid items-start gap-4 xl:grid-cols-2">
            <div class="box-shadow rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('booking::app.settings.add-service')
                    </p>
                </div>

                <div class="p-5">
                    <x-admin::form :action="route('admin.settings.booking.services.store')">
                        <div class="mb-4">
                            <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                @lang('booking::app.settings.service-name') <span class="text-red-500">*</span>
                            </label>
                            <input class="{{ $fieldClass }}" type="text" name="name" required>
                        </div>
                        <div class="mb-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    @lang('booking::app.settings.duration')
                                </label>
                                <input class="{{ $fieldClass }}" type="number" name="duration_minutes" value="30" min="5" required>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    @lang('booking::app.settings.buffer')
                                </label>
                                <input class="{{ $fieldClass }}" type="number" name="buffer_minutes" value="0" min="0">
                            </div>
                        </div>
                        <button class="primary-button" type="submit">@lang('booking::app.settings.save-service')</button>
                    </x-admin::form>

                    <div class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            @lang('booking::app.settings.services-list')
                        </p>
                        <ul class="space-y-2">
                            @forelse ($services as $service)
                                <li class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2.5 text-sm dark:border-gray-800">
                                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ $service->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $service->duration_minutes }} min</span>
                                </li>
                            @empty
                                <li class="text-sm text-gray-500">@lang('booking::app.settings.no-services')</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('booking::app.settings.staff-availability')
                    </p>
                </div>

                <div class="p-5">
                    @if ($users->isEmpty())
                        <p class="text-sm text-gray-500">@lang('booking::app.settings.no-staff')</p>
                    @else
                        <x-admin::form :action="route('admin.settings.booking.availability.store')">
                            <div class="mb-4">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    @lang('booking::app.index.staff')
                                </label>
                                <select class="{{ $fieldClass }}" name="user_id" required>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    @lang('booking::app.settings.weekday')
                                </label>
                                <select class="{{ $fieldClass }}" name="weekday" required>
                                    @foreach ($weekdays as $i => $day)
                                        <option value="{{ $i }}" @selected($i === 1)>{{ $day }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        @lang('booking::app.settings.starts')
                                    </label>
                                    <input class="{{ $fieldClass }}" type="time" name="starts_at" value="09:00" required>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        @lang('booking::app.settings.ends')
                                    </label>
                                    <input class="{{ $fieldClass }}" type="time" name="ends_at" value="17:00" required>
                                </div>
                            </div>
                            <button class="primary-button" type="submit">@lang('booking::app.settings.save-availability')</button>
                        </x-admin::form>

                        <x-admin::form
                            class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800"
                            :action="route('admin.settings.booking.availability.defaults')"
                        >
                            <div class="mb-3">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    @lang('booking::app.index.staff')
                                </label>
                                <select class="{{ $fieldClass }}" name="user_id" required>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="secondary-button" type="submit">
                                @lang('booking::app.settings.seed-defaults')
                            </button>
                        </x-admin::form>
                    @endif

                    <div class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            @lang('booking::app.settings.hours-list')
                        </p>
                        <ul class="space-y-2">
                            @forelse ($availabilities as $row)
                                <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-100 px-3 py-2.5 text-sm dark:border-gray-800">
                                    <span class="font-medium text-gray-800 dark:text-gray-100">
                                        {{ $row->user?->name ?? '—' }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ $weekdayShort[$row->weekday] ?? $row->weekday }}
                                        {{ substr((string) $row->starts_at, 0, 5) }}–{{ substr((string) $row->ends_at, 0, 5) }}
                                    </span>
                                </li>
                            @empty
                                <li class="text-sm text-gray-500">@lang('booking::app.settings.no-availability')</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
