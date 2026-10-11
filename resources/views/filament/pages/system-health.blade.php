<x-filament-panels::page>
    <div class="saas-page-intro">
        <p>
            Live operational checks for landlord database connectivity, disk pressure,
            environment safety, and Stripe configuration. Run checks after deploys or
            when investigating platform incidents.
        </p>
    </div>

    <div class="space-y-3">
        @forelse ($results as $result)
            @php
                $status = strtolower((string) $result['status']);
                $badgeClass = match (true) {
                    in_array($status, ['ok', 'passed', 'success'], true) => 'saas-badge--ok',
                    in_array($status, ['warning', 'warn'], true) => 'saas-badge--warning',
                    in_array($status, ['failed', 'fail', 'error', 'crashed'], true) => 'saas-badge--failed',
                    default => 'saas-badge--skipped',
                };
            @endphp

            <div class="saas-check-card">
                <div>
                    <div class="saas-check-card__title">{{ $result['label'] }}</div>
                    @if ($result['shortSummary'])
                        <div class="saas-check-card__summary">{{ $result['shortSummary'] }}</div>
                    @endif
                    @if ($result['notificationMessage'])
                        <div class="saas-check-card__message">{{ $result['notificationMessage'] }}</div>
                    @endif
                </div>
                <span class="saas-badge {{ $badgeClass }}">{{ $result['status'] }}</span>
            </div>
        @empty
            <div class="saas-empty">
                No health results yet. Use <strong>Run health checks</strong> to generate the first snapshot.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
