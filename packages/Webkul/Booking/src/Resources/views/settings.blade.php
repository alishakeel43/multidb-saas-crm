<x-admin::layouts>
    <x-slot:title>@lang('booking::app.menu.settings')</x-slot>

    <p class="mb-4 text-2xl font-semibold dark:text-white">@lang('booking::app.menu.settings')</p>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 font-medium dark:text-white">Add service</p>
            <x-admin::form :action="route('admin.settings.booking.services.store')">
                <input class="mb-2 w-100" type="text" name="name" placeholder="Service name" required>
                <input class="mb-2 w-100" type="number" name="duration_minutes" value="30" min="5" required>
                <input class="mb-2 w-100" type="number" name="buffer_minutes" value="0" min="0">
                <button class="primary-button" type="submit">Save service</button>
            </x-admin::form>

            <ul class="mt-4 space-y-1 text-sm dark:text-white">
                @foreach ($services as $service)
                    <li>{{ $service->name }} — {{ $service->duration_minutes }} min</li>
                @endforeach
            </ul>
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 font-medium dark:text-white">Staff availability</p>
            <x-admin::form :action="route('admin.settings.booking.availability.store')">
                <select class="mb-2 w-100" name="user_id" required>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <select class="mb-2 w-100" name="weekday" required>
                    @foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $day)
                        <option value="{{ $i }}">{{ $day }}</option>
                    @endforeach
                </select>
                <input class="mb-2 w-100" type="time" name="starts_at" value="09:00" required>
                <input class="mb-2 w-100" type="time" name="ends_at" value="17:00" required>
                <button class="primary-button" type="submit">Save availability</button>
            </x-admin::form>

            <ul class="mt-4 space-y-1 text-sm dark:text-white">
                @foreach ($availabilities as $row)
                    <li>
                        {{ $row->user?->name }} · {{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$row->weekday] }}
                        {{ substr($row->starts_at, 0, 5) }}-{{ substr($row->ends_at, 0, 5) }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-admin::layouts>
