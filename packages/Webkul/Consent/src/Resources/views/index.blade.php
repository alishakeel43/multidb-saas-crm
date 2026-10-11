<x-admin::layouts>
    <x-slot:title>Consent</x-slot>

    <p class="mb-4 text-2xl font-semibold dark:text-white">Consent preferences</p>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <x-admin::form :action="route('admin.settings.consent.store')">
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Person ID</label>
                    <input class="w-100" type="number" name="person_id" required>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Channel</label>
                    <select class="w-100" name="channel" required>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel }}">{{ ucfirst($channel) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Status</label>
                    <select class="w-100" name="is_opted_in" required>
                        <option value="1">Opted in</option>
                        <option value="0">Opted out</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="text-sm dark:text-white">Source</label>
                    <input class="w-100" type="text" name="source" value="admin">
                </div>
                <button class="primary-button" type="submit">Save</button>
            </x-admin::form>
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <div class="space-y-2 text-sm dark:text-white">
                @forelse ($consents as $consent)
                    <div class="rounded border p-2 dark:border-gray-700">
                        #{{ $consent->person_id }} {{ $consent->person?->name }}
                        · {{ $consent->channel }}
                        · {{ $consent->is_opted_in ? 'opted-in' : 'opted-out' }}
                    </div>
                @empty
                    <p class="text-gray-500">No consent records yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin::layouts>
