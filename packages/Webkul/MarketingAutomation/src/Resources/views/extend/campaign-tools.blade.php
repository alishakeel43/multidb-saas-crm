</div></div></div>

@php
    $ready = \Illuminate\Support\Facades\Schema::hasTable('ma_audiences');
    $audiences = $ready ? \Webkul\MarketingAutomation\Models\Audience::query()->latest('id')->limit(20)->get() : collect();
    $campaigns = $ready ? \Webkul\MarketingAutomation\Models\Campaign::query()->orderBy('name')->get() : collect();
    $sequences = $ready ? \Webkul\MarketingAutomation\Models\EmailSequence::query()->with(['steps', 'campaign', 'audience'])->latest('id')->limit(20)->get() : collect();
    $messages = $ready ? \Webkul\MarketingAutomation\Models\EmailMessage::query()->latest('id')->limit(20)->get() : collect();
    $attributions = $ready ? \Webkul\MarketingAutomation\Models\Attribution::query()->with('campaign')->latest('id')->limit(20)->get() : collect();
@endphp

@if ($ready)
    <div class="grid gap-4">
        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 text-base font-semibold dark:text-white">Audiences</p>
            <form method="POST" action="{{ route('admin.marketing_automation.audiences.store') }}" class="mb-3 grid gap-2 md:grid-cols-4">
                @csrf
                <input name="name" required placeholder="Audience name" class="rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <select name="rules[0][attribute]" class="rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <option value="name">Name</option>
                    <option value="job_title">Job title</option>
                    <option value="email">Email</option>
                </select>
                <input name="rules[0][operator]" value="contains" class="rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <input name="rules[0][value]" placeholder="Value" class="rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <button class="primary-button md:col-span-4" type="submit">Save audience</button>
            </form>
            <div class="space-y-2 text-sm dark:text-white">
                @forelse ($audiences as $audience)
                    <div class="flex items-center justify-between rounded border p-2 dark:border-gray-700">
                        <span>{{ $audience->name }}</span>
                        <form method="POST" action="{{ route('admin.marketing_automation.audiences.destroy', $audience->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="transparent-button" type="submit">Delete</button>
                        </form>
                    </div>
                @empty
                    <p class="text-gray-500">No audiences yet.</p>
                @endforelse
            </div>
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 text-base font-semibold dark:text-white">Email sequences</p>
            <form method="POST" action="{{ route('admin.marketing_automation.sequences.store') }}" class="mb-4 grid gap-2 md:grid-cols-4">
                @csrf
                <input name="name" required placeholder="Sequence name" class="rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <select name="status" class="rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="paused">Paused</option>
                </select>
                <select name="campaign_id" class="rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <option value="">No campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
                <select name="audience_id" class="rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <option value="">All contacts</option>
                    @foreach ($audiences as $audience)
                        <option value="{{ $audience->id }}">{{ $audience->name }}</option>
                    @endforeach
                </select>
                <button class="primary-button md:col-span-4" type="submit">Save sequence</button>
            </form>

            <div class="space-y-4 text-sm dark:text-white">
                @forelse ($sequences as $sequence)
                    <div class="rounded border p-3 dark:border-gray-700">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-medium">{{ $sequence->name }}</span>
                                <span class="text-gray-500">{{ $sequence->status }} · {{ $sequence->campaign->name ?? 'No campaign' }}</span>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.marketing_automation.sequences.enroll', $sequence->id) }}">
                                    @csrf
                                    <button class="transparent-button" type="submit">Enroll</button>
                                </form>
                                <form method="POST" action="{{ route('admin.marketing_automation.sequences.status', $sequence->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $sequence->status === 'active' ? 'paused' : 'active' }}">
                                    <button class="transparent-button" type="submit">{{ $sequence->status === 'active' ? 'Pause' : 'Activate' }}</button>
                                </form>
                            </div>
                        </div>
                        @foreach ($sequence->steps as $step)
                            <div class="mb-1 flex items-center justify-between text-gray-600 dark:text-gray-300">
                                <span>{{ $step->subject }} · {{ $step->delay_minutes }} min</span>
                                <form method="POST" action="{{ route('admin.marketing_automation.sequences.steps.destroy', [$sequence->id, $step->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="transparent-button" type="submit">Delete step</button>
                                </form>
                            </div>
                        @endforeach
                        <form method="POST" action="{{ route('admin.marketing_automation.sequences.steps.store', $sequence->id) }}" class="mt-2 grid gap-2 md:grid-cols-2">
                            @csrf
                            <input name="subject" required placeholder="Subject" class="rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <input name="delay_minutes" type="number" min="0" value="0" required class="rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <textarea name="body" required placeholder="Body" class="rounded border px-3 py-2 md:col-span-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white"></textarea>
                            <input name="link_url" placeholder="https://example.com" class="rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <input name="link_label" placeholder="Link label" class="rounded border px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <button class="primary-button md:col-span-2" type="submit">Add step</button>
                        </form>
                    </div>
                @empty
                    <p class="text-gray-500">No sequences yet. Save a campaign first if you want to attach one.</p>
                @endforelse
            </div>
        </div>

        <div class="box-shadow rounded-lg bg-white p-4 dark:bg-gray-900">
            <p class="mb-3 text-base font-semibold dark:text-white">Deliverability and attribution</p>
            <div class="mb-3 space-y-1 text-sm dark:text-white">
                @forelse ($messages as $message)
                    <div class="flex items-center justify-between rounded border p-2 dark:border-gray-700">
                        <span>{{ $message->email }} · {{ $message->status }}</span>
                        @if ($message->status !== 'bounced')
                            <form method="POST" action="{{ route('admin.marketing_automation.deliverability.bounce', $message->id) }}">
                                @csrf
                                <button class="transparent-button" type="submit">Mark bounced</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">No sequence messages yet.</p>
                @endforelse
            </div>
            <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                @foreach ($attributions as $row)
                    <div>{{ $row->utm_source }} / {{ $row->utm_campaign }} · {{ $row->campaign->name ?? 'Unmatched' }}</div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div><div><div>
