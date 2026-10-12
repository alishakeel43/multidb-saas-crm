@php
    $person = $person ?? null;
    $numbers = is_array($person->contact_numbers ?? null) ? $person->contact_numbers : [];
    $hasCredentials = (bool) core()->getConfigData('telephony.credentials.account_sid');
@endphp

@if ($person && ! empty($numbers))
    <div class="mt-4 w-full" x-data="{
        loading: false,
        current: null,
        async call(number) {
            if (!{{ $hasCredentials ? 'true' : 'false' }}) {
                return;
            }

            this.loading = true;
            this.current = number;

            const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';

            const response = await fetch('{{ route('admin.telephony.call_logs.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    to: number,
                    contact_id: {{ (int) ($person->id ?? 0) }},
                }),
            });

            this.loading = false;
            this.current = null;

            if (! response.ok) {
                console.error('Telephony call failed');
            }
        }
    }">
        @foreach ($numbers as $number)
            @php $phone = trim((string) $number); @endphp

            @if ($phone !== '')
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-brandColor bg-brandColor px-3 py-2 text-xs font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:border-gray-300 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:border-gray-700 dark:disabled:bg-gray-800 dark:disabled:text-gray-500"
                    @if ($hasCredentials)
                        @click="call('{{ addslashes($phone) }}')"
                    @else
                        disabled
                    @endif
                    :disabled="loading || !{{ json_encode($hasCredentials) }}"
                >
                    <span class="icon-phone text-sm"></span>

                    <span x-show="current !== '{{ addslashes($phone) }}' || !loading">
                        {{ $hasCredentials ? __('Call now') : __('Call unavailable') }}
                    </span>

                    <span x-show="current === '{{ addslashes($phone) }}' && loading" x-cloak>
                        Calling...
                    </span>
                </button>
            @endif
        @endforeach
    </div>
@endif
