<x-admin::layouts>
    <x-slot:title>{{ $sequence->name }}</x-slot>

    @include('marketing_automation::partials.nav')

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <h1 class="text-2xl font-semibold dark:text-white">{{ $sequence->name }}</h1>
        <span class="text-sm text-gray-500">{{ $sequence->status }}</span>
        <form method="POST" action="{{ route('admin.marketing_automation.sequences.status', $sequence->id) }}">
            @csrf
            <input type="hidden" name="status" value="active">
            <button class="primary-button" type="submit">Resume</button>
        </form>
        <form method="POST" action="{{ route('admin.marketing_automation.sequences.status', $sequence->id) }}">
            @csrf
            <input type="hidden" name="status" value="paused">
            <button class="transparent-button" type="submit">Pause</button>
        </form>
        <form method="POST" action="{{ route('admin.marketing_automation.sequences.enroll', $sequence->id) }}">
            @csrf
            <button class="primary-button" type="submit">Enroll audience</button>
        </form>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div>
            <h2 class="mb-2 font-medium dark:text-white">Steps</h2>
            @foreach ($sequence->steps as $step)
                <div class="mb-2 rounded border p-3 dark:border-gray-700">
                    <div class="font-medium dark:text-white">{{ $step->subject }}</div>
                    <div class="text-sm text-gray-500">Wait {{ $step->delay_minutes }} minutes · {{ $step->link_url ?: 'no tracked link' }}</div>
                    <form method="POST" action="{{ route('admin.marketing_automation.sequences.steps.destroy', [$sequence->id, $step->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="transparent-button" type="submit">Delete step</button>
                    </form>
                </div>
            @endforeach
            <form class="box-shadow mt-3 rounded-lg bg-white p-4 dark:bg-gray-900" method="POST" action="{{ route('admin.marketing_automation.sequences.steps.store', $sequence->id) }}">
                @csrf
                <label class="mb-2 block text-sm dark:text-white">Delay minutes
                    <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="delay_minutes" type="number" min="0" value="0" required>
                </label>
                <label class="mb-2 block text-sm dark:text-white">Subject
                    <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="subject" required>
                </label>
                <label class="mb-2 block text-sm dark:text-white">Body
                    <textarea class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="body" rows="4" required></textarea>
                </label>
                <label class="mb-2 block text-sm dark:text-white">Tracked link
                    <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="link_url" placeholder="https://">
                </label>
                <label class="mb-3 block text-sm dark:text-white">Link label
                    <input class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" name="link_label">
                </label>
                <button class="primary-button" type="submit">Add step</button>
            </form>
        </div>
        <div>
            <h2 class="mb-2 font-medium dark:text-white">Enrollments</h2>
            @forelse ($sequence->enrollments as $enrollment)
                <div class="mb-2 flex items-center justify-between rounded border p-3 dark:border-gray-700">
                    <div class="text-sm dark:text-white">Person #{{ $enrollment->person_id }} · {{ $enrollment->status }} · step {{ $enrollment->current_step }}</div>
                    <form method="POST" action="{{ route('admin.marketing_automation.sequences.enrollments.status', [$sequence->id, $enrollment->id]) }}">
                        @csrf
                        <input type="hidden" name="status" value="{{ $enrollment->status === 'paused' ? 'active' : 'paused' }}">
                        <button class="transparent-button" type="submit">{{ $enrollment->status === 'paused' ? 'Resume' : 'Pause' }}</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">No one is enrolled. Activate the sequence and enroll the audience. Opted-out contacts are skipped.</p>
            @endforelse
        </div>
    </div>
</x-admin::layouts>
