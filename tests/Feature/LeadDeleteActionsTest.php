<?php

use App\Models\Tenant;
use App\Services\FeatureEntitlementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Webkul\Lead\Models\Lead;
use Webkul\UnifiedInbox\Models\ChannelConnection;
use Webkul\UnifiedInbox\Models\Conversation;
use Webkul\UnifiedInbox\Models\Message;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

afterEach(function () {
    $tenant = \Illuminate\Support\Facades\DB::connection('tenant');
    while ($tenant->transactionLevel() > 0) {
        $tenant->rollBack();
    }
});

beforeEach(function () {
    $this->tenant = Tenant::first();
    if ($this->tenant) {
        $this->tenant->makeCurrent();
    }

    // Hard guard: makeCurrent() reconnects the tenant connection to the tenant's DB.
    // Never let this suite wipe real local data (it deletes all inbox rows below).
    $tenantDb = (string) \Illuminate\Support\Facades\DB::connection('tenant')->getDatabaseName();
    if (in_array($tenantDb, ['tenant_demo', 'tenant_bootstrap'], true) || ! str_starts_with($tenantDb, 'tenant_test')) {
        throw new RuntimeException("LeadDeleteActionsTest refused to run against database [{$tenantDb}]. Use the isolated test DB (see phpunit.xml).");
    }

    // The reconnect above escapes DatabaseTransactions; wrap the tenant connection again.
    \Illuminate\Support\Facades\DB::connection('tenant')->beginTransaction();

    Message::query()->delete();
    Conversation::query()->delete();
    ChannelConnection::query()->delete();

    $role = Role::firstOrCreate(
        ['permission_type' => 'all'],
        ['name' => 'Administrator', 'description' => 'Admin Role']
    );

    $this->user = User::first() ?? User::create([
        'name' => 'Admin User',
        'email' => 'admin_test_'.uniqid().'@example.com',
        'password' => bcrypt('secret123'),
        'status' => 1,
        'role_id' => $role->id,
        'view_permission' => 'global',
    ]);
});


function makeDeleteTestLead(): Lead
{
    $pipeline = \Webkul\Lead\Models\Pipeline::query()->with('stages')->first();
    if (! $pipeline || $pipeline->stages->isEmpty()) {
        test()->markTestSkipped('No pipeline in test DB.');
    }

    return Lead::create([
        'title' => 'Delete Action Lead '.uniqid(),
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $pipeline->stages->first()->id,
        'user_id' => test()->user->id,
        'status' => 1,
        'lead_value' => 0,
    ]);
}

it('shows the delete action on the lead detail page and kanban for users with leads.delete', function () {
    $lead = makeDeleteTestLead();

    $this->actingAs($this->user, 'user')
        ->get(route('admin.leads.view', $lead->id))
        ->assertSuccessful()
        ->assertSee('v-lead-delete-action', false)
        ->assertSee(route('admin.leads.delete', $lead->id), false);

    $this->actingAs($this->user, 'user')
        ->get(route('admin.leads.index'))
        ->assertSuccessful()
        ->assertSee('deleteLead(element)', false);
});

it('deletes a single lead and mass deletes leads via the existing routes', function () {
    $one = makeDeleteTestLead();
    $two = makeDeleteTestLead();

    $this->actingAs($this->user, 'user')->deleteJson(route('admin.leads.delete', $one->id))->assertSuccessful();
    expect(Lead::find($one->id))->toBeNull();

    $this->actingAs($this->user, 'user')
        ->postJson(route('admin.leads.mass_delete'), ['indices' => [$two->id]])
        ->assertSuccessful();
    expect(Lead::find($two->id))->toBeNull();
});
