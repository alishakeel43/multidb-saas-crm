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
        throw new RuntimeException("UnifiedInboxTest refused to run against database [{$tenantDb}]. Use the isolated test DB (see phpunit.xml).");
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


function uiRouterPost($test, array $payload, ?string $secret = 'test_meta_app_secret')
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $server = ['CONTENT_TYPE' => 'application/json'];
    if ($secret !== null) {
        $server['HTTP_X_HUB_SIGNATURE_256'] = 'sha256='.hash_hmac('sha256', $body, $secret);
    }

    return $test->call('POST', '/api/meta/webhook', [], [], [], $server, $body);
}

function uiWaPayload(string $phoneId, array $value): array
{
    return [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA_ROUTER_TEST',
            'changes' => [[
                'field' => 'messages',
                'value' => array_merge([
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['display_phone_number' => '+15550001111', 'phone_number_id' => $phoneId],
                ], $value),
            ]],
        ]],
    ];
}

it('verifies the central WhatsApp webhook alias with the configured verify token', function () {
    config()->set('unified-inbox.meta.verify_token', 'router_token');

    $ok = $this->get('/api/meta/webhook?hub_mode=subscribe&hub_verify_token=router_token&hub_challenge=CH_1');
    $ok->assertStatus(200);
    expect($ok->getContent())->toBe('CH_1');

    $this->get('/api/meta/webhook?hub_mode=subscribe&hub_verify_token=nope&hub_challenge=CH_1')->assertForbidden();
});

it('rejects central WhatsApp webhook posts with a bad signature', function () {
    config()->set('unified-inbox.meta.app_secret', 'test_meta_app_secret');

    uiRouterPost($this, uiWaPayload('PHONE_SIG', []), 'wrong_secret')->assertForbidden();
    uiRouterPost($this, uiWaPayload('PHONE_SIG', []), null)->assertForbidden();
});

it('acknowledges an unknown phone_number_id with 200 and stores nothing', function () {
    config()->set('unified-inbox.meta.app_secret', 'test_meta_app_secret');

    $response = uiRouterPost($this, uiWaPayload('PHONE_UNKNOWN_404', [
        'contacts' => [['profile' => ['name' => 'Ghost'], 'wa_id' => '15550009999']],
        'messages' => [['from' => '15550009999', 'id' => 'wamid.unknown', 'timestamp' => '1710000000', 'type' => 'text', 'text' => ['body' => 'hi']]],
    ]));

    $response->assertStatus(200)->assertJson(['status' => 'EVENT_RECEIVED']);
    expect(Message::where('external_id', 'wamid.unknown')->exists())->toBeFalse();
});

it('routes a known phone_number_id to its tenant and applies status updates', function () {
    expect($this->tenant)->not->toBeNull();
    config()->set('unified-inbox.meta.app_secret', 'test_meta_app_secret');

    ChannelConnection::updateOrCreate(
        ['channel' => 'whatsapp', 'external_id' => 'PHONE_ROUTER_1'],
        ['name' => 'Router WA', 'status' => 'connected', 'credentials' => ['access_token' => 'x', 'phone_number_id' => 'PHONE_ROUTER_1']]
    );
    $router = app(CentralWebhookRouter::class);
    $router->registerRoute($this->tenant->id, 'whatsapp', 'PHONE_ROUTER_1', 'WABA_ROUTER_TEST', 'Router WA', '+15550001111');

    try {
        uiRouterPost($this, uiWaPayload('PHONE_ROUTER_1', [
            'contacts' => [['profile' => ['name' => 'Routed Rita'], 'wa_id' => '15550002222']],
            'messages' => [['from' => '15550002222', 'id' => 'wamid.routed.in', 'timestamp' => '1710000000', 'type' => 'text', 'text' => ['body' => 'Routed hello']]],
        ]))->assertStatus(200);

        $conversation = Conversation::where('account_id', 'PHONE_ROUTER_1')->where('external_id', '15550002222')->first();
        expect($conversation)->not->toBeNull()
            ->and($conversation->customer_name)->toBe('Routed Rita');

        $outbound = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'external_id' => 'wamid.routed.out',
            'body' => 'Reply',
            'status' => 'sent',
            'occurred_at' => now(),
        ]);

        uiRouterPost($this, uiWaPayload('PHONE_ROUTER_1', [
            'statuses' => [['id' => 'wamid.routed.out', 'status' => 'delivered', 'timestamp' => '1710000100', 'recipient_id' => '15550002222']],
        ]))->assertStatus(200);
        expect($outbound->fresh()->status)->toBe('delivered');

        uiRouterPost($this, uiWaPayload('PHONE_ROUTER_1', [
            'statuses' => [['id' => 'wamid.routed.out', 'status' => 'read', 'timestamp' => '1710000200', 'recipient_id' => '15550002222']],
        ]))->assertStatus(200);
        expect($outbound->fresh()->status)->toBe('read');

        // Deactivated mappings stop routing (disconnect flow) but the row is kept.
        $router->deactivateRoute($this->tenant->id, 'whatsapp', 'PHONE_ROUTER_1');
        expect($router->resolveTenant('whatsapp', 'PHONE_ROUTER_1'))->toBeNull()
            ->and(\App\Models\TenantChannelRoute::where('external_id', 'PHONE_ROUTER_1')->exists())->toBeTrue();

        // Reconnect re-activates the same mapping.
        $router->registerRoute($this->tenant->id, 'whatsapp', 'PHONE_ROUTER_1', 'WABA_ROUTER_TEST', 'Router WA');
        expect($router->resolveTenant('whatsapp', 'PHONE_ROUTER_1')?->id)->toBe($this->tenant->id);
    } finally {
        $router->removeRoute($this->tenant->id, 'whatsapp', 'PHONE_ROUTER_1');
    }
});
