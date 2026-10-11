<x-filament-panels::page>
    <div class="saas-page-intro">
        <p>
            Per-tenant database dumps and storage snapshots. Nightly backups are scheduled
            automatically; use this console to trigger an on-demand backup or verify recent
            restore points.
        </p>
    </div>

    <div class="saas-table-wrap">
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Restore point</th>
                    <th>Created</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($backups as $backup)
                    <tr>
                        <td>
                            <div class="font-semibold">{{ $backup['tenant_slug'] }}</div>
                        </td>
                        <td>
                            <span class="saas-mono">{{ $backup['timestamp'] }}</span>
                        </td>
                        <td>{{ $backup['created_at'] ?? '—' }}</td>
                        <td>
                            <span class="saas-mono">{{ $backup['path'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="saas-empty" style="border:0;box-shadow:none;background:transparent;padding:2rem 1rem">
                                No tenant backups found yet. Run <strong>Backup all tenants</strong> to create the first restore points.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
