<?php

use App\Models\Tenant;
use App\Services\FeatureEntitlementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Lead\Models\Lead;
use Webkul\UnifiedInbox\Models\ChannelConnection;
use Webkul\UnifiedInbox\Models\Conversation;
use Webkul\UnifiedInbox\Models\Message;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->tenant = Tenant::first();
    if ($this->tenant) {
        $this->tenant->makeCurrent();
    }

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

it('registers unified_inbox in menus, ACL, and feature entitlements', function () {
    $menuKeys = collect(config('menu.admin'))->pluck('key');
    expect($menuKeys)->toContain('unified_inbox')
        ->and($menuKeys)->toContain('settings.other_settings.unified_inbox_channels');

    $aclKeys = collect(config('acl'))->pluck('key');
    expect($aclKeys)->toContain('unified_inbox')
        ->and($aclKeys)->toContain('settings.other_settings.unified_inbox_channels');

    $entitlementService = app(FeatureEntitlementService::class);
    expect($entitlementService->featureForRoute('admin.unified_inbox.index'))->toBe('unified_inbox')
        ->and($entitlementService->featureForRoute('admin.unified_inbox.channels.index'))->toBe('unified_inbox');
});

it('renders the messaging channels settings page', function () {
    $response = $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.channels.index'));

    $response->assertSuccessful();
    $response->assertSee('Messaging Channels');
    $response->assertSee('WhatsApp Business');
    $response->assertSee('Facebook Messenger');
    $response->assertSee('Instagram Direct');
});

it('verifies Meta webhook challenge handshake', function () {
    config()->set('unified-inbox.meta.verify_token', 'test_meta_token_123');

    $response = $this->get('/unified-inbox/meta/webhook?hub_mode=subscribe&hub_verify_token=test_meta_token_123&hub_challenge=CHALLENGE_ACCEPTED_999');

    $response->assertStatus(200);
    expect($response->getContent())->toBe('CHALLENGE_ACCEPTED_999');
});

it('receives an inbound WhatsApp message via Meta webhook and creates conversation and message', function () {
    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [
            [
                'id' => 'WABA_1029384756',
                'changes' => [
                    [
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => [
                                'display_phone_number' => '+15551234567',
                                'phone_number_id' => 'PHONE_ID_888',
                            ],
                            'contacts' => [
                                [
                                    'profile' => ['name' => 'Sarah Connor'],
                                    'wa_id' => '15559876543',
                                ],
                            ],
                            'messages' => [
                                [
                                    'from' => '15559876543',
                                    'id' => 'wamid.HBgLMTU1NTk4NzY1NDMVAgASGBQzQUR',
                                    'timestamp' => '1710000000',
                                    'text' => [
                                        'body' => 'Hi, I need a CRM demo for our team.',
                                    ],
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'field' => 'messages',
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/unified-inbox/meta/webhook', $payload);

    $response->assertStatus(200)
        ->assertJson(['status' => 'EVENT_RECEIVED']);

    $conversation = Conversation::where('channel', 'whatsapp')
        ->where('external_id', '15559876543')
        ->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation->customer_name)->toBe('Sarah Connor')
        ->and($conversation->last_message_preview)->toBe('Hi, I need a CRM demo for our team.')
        ->and($conversation->unread_count)->toBe(1);

    $message = Message::where('conversation_id', $conversation->id)->first();
    expect($message)->not->toBeNull()
        ->and($message->direction)->toBe('inbound')
        ->and($message->body)->toBe('Hi, I need a CRM demo for our team.')
        ->and($message->status)->toBe('received');
});

it('routes central webhook to specific tenant database via landlord channel route', function () {
    $tenant = Tenant::first();
    if (! $tenant) {
        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme_'.uniqid(),
            'database' => 'tenant_test_'.uniqid(),
            'status' => 'active',
        ]);
    }

    $router = app(CentralWebhookRouter::class);
    $router->registerRoute($tenant->id, 'whatsapp', 'PHONE_TENANT_ROUTE_99', 'WABA_99', 'Acme Support');

    $resolved = $router->resolveTenant('whatsapp', 'PHONE_TENANT_ROUTE_99', 'WABA_99');

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($tenant->id);

    // Clean up
    $router->removeRoute('whatsapp', 'PHONE_TENANT_ROUTE_99');
});

it('renders unified inbox and can send an outbound message', function () {
    // 1. Create a channel connection
    ChannelConnection::updateOrCreate(
        ['channel' => 'whatsapp', 'external_id' => 'PHONE_ID_888'],
        [
            'name' => 'Support WhatsApp',
            'status' => 'connected',
            'credentials' => [
                'access_token' => 'dummy_token',
                'phone_number_id' => 'PHONE_ID_888',
            ],
        ]
    );

    // 2. Create a test conversation
    $conversation = Conversation::create([
        'channel' => 'whatsapp',
        'account_id' => 'PHONE_ID_888',
        'external_id' => '15559998888',
        'customer_name' => 'Alex Miller',
        'status' => 'open',
        'last_message_preview' => 'Hello!',
        'last_message_at' => now(),
    ]);

    // 3. Render Inbox view
    $viewResponse = $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.index', ['conversation_id' => $conversation->id]));

    $viewResponse->assertSuccessful();
    $viewResponse->assertSee('Alex Miller');
    $viewResponse->assertSee('Unified Inbox');

    // 4. Send outbound message
    $sendResponse = $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.send', $conversation->id), [
            'body' => 'Thanks for reaching out Alex! How many users in your team?',
        ]);

    $sendResponse->assertSuccessful()
        ->assertJson(['success' => true]);

    $outbound = Message::where('conversation_id', $conversation->id)
        ->where('direction', 'outbound')
        ->first();

    expect($outbound)->not->toBeNull()
        ->and($outbound->body)->toBe('Thanks for reaching out Alex! How many users in your team?');
});

it('can update conversation status and assign to user', function () {
    $conversation = Conversation::create([
        'channel' => 'messenger',
        'account_id' => 'PAGE_123',
        'external_id' => 'PSID_999',
        'customer_name' => 'Michael Scott',
        'status' => 'open',
    ]);

    // Update status to pending
    $statusResponse = $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.status', $conversation->id), [
            'status' => 'pending',
        ]);

    $statusResponse->assertSuccessful()
        ->assertJson(['success' => true, 'status' => 'pending']);

    expect($conversation->fresh()->status)->toBe('pending');

    // Assign agent
    $assignResponse = $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.assign', $conversation->id), [
            'user_id' => $this->user->id,
        ]);

    $assignResponse->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($conversation->fresh()->user_id)->toBe($this->user->id);
});

it('can convert a conversation into a CRM lead', function () {
    $conversation = Conversation::create([
        'channel' => 'whatsapp',
        'account_id' => 'PHONE_123',
        'external_id' => '15552223333',
        'customer_name' => 'Dwight Schrute',
        'status' => 'open',
        'last_message_preview' => 'Interested in 50 licenses.',
    ]);

    $leadResponse = $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.link_lead', $conversation->id), [
            'title' => 'Big Beet Farm Deal',
            'lead_value' => 5000,
        ]);

    $leadResponse->assertSuccessful()
        ->assertJson(['success' => true]);

    $freshConv = $conversation->fresh();
    expect($freshConv->lead_id)->not->toBeNull();

    $lead = Lead::find($freshConv->lead_id);
    expect($lead)->not->toBeNull()
        ->and($lead->title)->toBe('Big Beet Farm Deal')
        ->and((int) $lead->lead_value)->toBe(5000);
});

it('displays Connect Channels button and channel statuses in Unified Inbox view', function () {
    $response = $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.index'));

    $response->assertSuccessful()
        ->assertSee('Connect Channels')
        ->assertSee(route('admin.unified_inbox.channels.index'), false)
        ->assertSee('Omnichannel Hub')
        ->assertSee('WhatsApp')
        ->assertSee('Messenger')
        ->assertSee('Instagram');
});

it('ensures central webhook URL always targets the root platform domain and strips tenant subdomains', function () {
    $router = app(CentralWebhookRouter::class);

    // Default configured app.url
    $url = $router->getCentralWebhookUrl();
    expect($url)->toBe('http://krayinsaas.localhost:8888/unified-inbox/meta/webhook')
        ->and($url)->not->toContain('demo.');

    // When tenant subdomain exists in app.url, it is stripped to root domain
    config(['app.url' => 'http://demo.krayinsaas.localhost:8888']);
    config(['multitenancy.tenant_base_domain' => 'krayinsaas.localhost']);
    $strippedUrl = $router->getCentralWebhookUrl();
    expect($strippedUrl)->toBe('http://krayinsaas.localhost:8888/unified-inbox/meta/webhook')
        ->and($strippedUrl)->not->toContain('demo.');
});
