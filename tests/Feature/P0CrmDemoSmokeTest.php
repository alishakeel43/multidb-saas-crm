<?php

use App\Models\Tenant;
use App\Services\SalesReportService;
use Illuminate\Support\Facades\DB;
use Webkul\User\Models\User;

/**
 * Live smoke against the provisioned `demo` tenant (skipped when absent).
 * Confirms P0 demo seed inventory and that key CRM admin routes render for a user.
 */
it('has P0 demo seed inventory and reachable CRM admin pages on the demo tenant', function () {
    $tenant = Tenant::query()->where('slug', 'demo')->first();

    if (! $tenant) {
        test()->markTestSkipped('Demo tenant is not provisioned on this environment.');
    }

    $tenant->makeCurrent();

    try {
        $connection = DB::connection('tenant');

        expect($connection->table('leads')->where('title', 'like', 'DEMO - %')->count())->toBeGreaterThanOrEqual(6)
            ->and($connection->table('assignment_rules')->where('name', 'like', 'DEMO - %')->count())->toBeGreaterThanOrEqual(1)
            ->and($connection->table('booking_services')->where('name', 'like', 'DEMO - %')->count())->toBeGreaterThanOrEqual(1)
            ->and($connection->table('bookings')->where('notes', 'like', 'DEMO - %')->count())->toBeGreaterThanOrEqual(3)
            ->and($connection->table('person_consents')->count())->toBeGreaterThanOrEqual(5)
            ->and($connection->table('quotes')->where('subject', 'like', 'DEMO - %')->count())->toBe(3)
            ->and($connection->table('quote_approvals')->where('delivery_status', 'converted')->count())->toBeGreaterThanOrEqual(1)
            ->and($connection->table('lead_scoring_rules')->where('name', 'like', 'DEMO - %')->count())->toBe(3)
            ->and($connection->table('user_notifications')->where('title', 'like', 'DEMO - %')->count())->toBe(3);

        $snapshot = app(SalesReportService::class)->snapshot();

        expect($snapshot['open_leads'])->toBeGreaterThan(0)
            ->and($snapshot['won_leads'])->toBeGreaterThan(0)
            ->and($snapshot['lost_leads'])->toBeGreaterThan(0)
            ->and($snapshot['quotes_converted'])->toBeGreaterThan(0)
            ->and($snapshot['bookings_upcoming'])->toBeGreaterThan(0)
            ->and($snapshot['avg_lead_score'])->toBeGreaterThan(0);

        $user = User::query()->orderBy('id')->first();

        if (! $user) {
            test()->markTestSkipped('Demo tenant has no CRM user.');
        }

        $admin = trim((string) config('app.admin_path', 'admin'), '/');

        foreach ([
            "{$admin}/notifications",
            "{$admin}/sales-reports",
            "{$admin}/bookings",
            "{$admin}/settings/lead-scoring",
            "{$admin}/quotes/approvals",
            "{$admin}/settings/assignment",
            "{$admin}/settings/consent",
            'book',
        ] as $path) {
            $this->actingAs($user, 'user')
                ->get('/'.$path)
                ->assertOk();
        }
    } finally {
        Tenant::forgetCurrent();
    }
});
