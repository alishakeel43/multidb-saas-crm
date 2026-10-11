<x-admin::layouts>
    <x-slot:title>@lang('booking::app.menu.settings')</x-slot>

    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-2xl font-semibold dark:text-white">@lang('booking::app.menu.settings')</p>
        <a href="{{ route('admin.bookings.index') }}" class="transparent-button">
            @lang('booking::app.index.title')
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 font-medium dark:text-white">@lang('booking::app.settings.add-service')</p>
            <x-admin::form :action="route('admin.settings.booking.services.store')">
                <input
                    class="mb-2 w-100"
                    type="text"
                    name="name"
                    placeholder="@lang('booking::app.settings.service-name')"
                    required
                >
                <label class="mb-1 block text-xs text-gray-500">@lang('booking::app.settings.duration')</label>
                <input class="mb-2 w-100" type="number" name="duration_minutes" value="30" min="5" required>
                <label class="mb-1 block text-xs text-gray-500">@lang('booking::app.settings.buffer')</label>
                <input class="mb-2 w-100" type="number" name="buffer_minutes" value="0" min="0">
                <button class="primary-button" type="submit">@lang('booking::app.settings.save-service')</button>
            </x-admin::form>

            <ul class="mt-4 space-y-1 text-sm dark:text-white">
                @forelse ($services as $service)
                    <li>{{ $service->name }} — {{ $service->duration_minutes }} min</li>
                @empty
                    <li class="text-gray-500">@lang('booking::app.settings.no-services')</li>
                @endforelse
            </ul>
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 font-medium dark:text-white">@lang('booking::app.settings.staff-availability')</p>

            @if ($users->isEmpty())
                <p class="text-sm text-gray-500">No active staff users found.</p>
            @else
                <x-admin::form :action="route('admin.settings.booking.availability.store')">
                    <select class="mb-2 w-100" name="user_id" required>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <select class="mb-2 w-100" name="weekday" required>
                        @foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $day)
                            <option value="{{ $i }}" @selected($i === 1)>{{ $day }}</option>
                        @endforeach
                    </select>
                    <label class="mb-1 block text-xs text-gray-500">@lang('booking::app.settings.starts')</label>
                    <input class="mb-2 w-100" type="time" name="starts_at" value="09:00" required>
                    <label class="mb-1 block text-xs text-gray-500">@lang('booking::app.settings.ends')</label>
                    <input class="mb-2 w-100" type="time" name="ends_at" value="17:00" required>
                    <button class="primary-button" type="submit">@lang('booking::app.settings.save-availability')</button>
                </x-admin::form>

                <x-admin::form
                    class="mt-3"
                    :action="route('admin.settings.booking.availability.defaults')"
                >
                    <select class="mb-2 w-100" name="user_id" required>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <button class="transparent-button" type="submit">@lang('booking::app.settings.seed-defaults')</button>
                </x-admin::form>
            @endif

            <ul class="mt-4 space-y-1 text-sm dark:text-white">
                @forelse ($availabilities as $row)
                    <li>
                        {{ $row->user?->name }} · {{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$row->weekday] }}
                        {{ substr((string) $row->starts_at, 0, 5) }}-{{ substr((string) $row->ends_at, 0, 5) }}
                    </li>
                @empty
                    <li class="text-gray-500">@lang('booking::app.settings.no-availability')</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin::layouts>
