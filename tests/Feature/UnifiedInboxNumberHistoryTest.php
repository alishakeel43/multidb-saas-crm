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


function nhSign(string $body): string
{
    return 'sha256='.hash_hmac('sha256', $body, 'test_meta_app_secret');
}

function nhOldAndNew(): array
{
    $old = ChannelConnection::create([
        'channel' => 'whatsapp', 'external_id' => 'PHONE_OLD_1', 'name' => 'Old WA', 'status' => 'connected',
        'credentials' => ['access_token' => 'x', 'phone_number_id' => 'PHONE_OLD_1', 'display_phone_number' => '+92 300 1111111', 'waba_id' => 'WABA_NH'],
    ]);
    $oldConversation = Conversation::create([
        'channel' => 'whatsapp', 'account_id' => 'PHONE_OLD_1', 'external_id' => '923001234567',
        'customer_name' => 'History Hina', 'status' => 'open', 'person_id' => null,
        'last_message_preview' => 'Old chat', 'last_message_at' => now()->subDays(3), 'last_incoming_at' => now()->subDays(3),
    ]);
    Message::create(['conversation_id' => $oldConversation->id, 'direction' => 'inbound', 'external_id' => 'wamid.old.1', 'body' => 'Message on the old number', 'status' => 'received', 'occurred_at' => now()->subDays(3)]);

    return [$old, $oldConversation];
}

it('keeps the old number and its history when WhatsApp is disconnected and a new number connected', function () {
    [$old, $oldConversation] = nhOldAndNew();

    // Disconnect old (same path the channels page uses), connect a replacement number.
    $this->actingAs($this->user, 'user')->post(route('admin.unified_inbox.channels.disconnect', $old->id));
    ChannelConnection::create([
        'channel' => 'whatsapp', 'external_id' => 'PHONE_NEW_2', 'name' => 'New WA', 'status' => 'connected',
        'credentials' => ['access_token' => 'y', 'phone_number_id' => 'PHONE_NEW_2', 'display_phone_number' => '+92 300 2222222'],
    ]);

    $old->refresh();
    expect($old->status)->toBe('disconnected')
        ->and($old->disconnected_at)->not->toBeNull()
        ->and($old->display_phone_number)->toBe('+92 300 1111111')
        ->and(Conversation::find($oldConversation->id))->not->toBeNull()
        ->and(Message::where('conversation_id', $oldConversation->id)->count())->toBe(1);

    $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.index'))
        ->assertSuccessful()
        ->assertSee('History Hina')
        ->assertSee('via old +92 300 1111111');
});

it('continues the same customer history when they message the new number', function () {
    config()->set('unified-inbox.meta.app_secret', 'test_meta_app_secret');
    [$old, $oldConversation] = nhOldAndNew();
    $person = \Webkul\Contact\Models\Person::create(['name' => 'History Hina', 'emails' => [], 'contact_numbers' => []]);
    $oldConversation->update(['person_id' => $person->id]);
    $old->update(['status' => 'disconnected']);
    ChannelConnection::create(['channel' => 'whatsapp', 'external_id' => 'PHONE_NEW_2', 'name' => 'New WA', 'status' => 'connected', 'credentials' => ['access_token' => 'y']]);

    $router = app(CentralWebhookRouter::class);
    $router->registerRoute($this->tenant->id, 'whatsapp', 'PHONE_NEW_2', 'WABA_NH', 'New WA');

    try {
        $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['id' => 'WABA_NH', 'changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp',
            'metadata' => ['phone_number_id' => 'PHONE_NEW_2', 'display_phone_number' => '+92 300 2222222'],
            'contacts' => [['profile' => ['name' => 'History Hina'], 'wa_id' => '923001234567']],
            'messages' => [['from' => '923001234567', 'id' => 'wamid.new.1', 'timestamp' => (string) time(), 'type' => 'text', 'text' => ['body' => 'Hi on the new number']]],
        ]]]]]]);
        $this->call('POST', '/api/meta/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => nhSign($body)], $body)->assertStatus(200);
    } finally {
        $router->removeRoute($this->tenant->id, 'whatsapp', 'PHONE_NEW_2');
    }

    $new = Conversation::where('account_id', 'PHONE_NEW_2')->where('external_id', '923001234567')->first();
    expect($new)->not->toBeNull()
        ->and($new->person_id)->toBe($person->id)
        ->and($new->metadata['previous_conversation_id'] ?? null)->toBe($oldConversation->id)
        ->and($oldConversation->fresh()->account_id)->toBe('PHONE_OLD_1');

    $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.index', ['conversation_id' => $new->id]))
        ->assertSuccessful()
        ->assertSee('Earlier history on old number')
        ->assertSee('Message on the old number');
});

it('sends a reply from an old-number chat via the current number', function () {
    Http::fake(['https://graph.facebook.com/*/PHONE_NEW_2/messages' => Http::response(['messages' => [['id' => 'wamid.reply.new']]], 200)]);
    [$old, $oldConversation] = nhOldAndNew();
    $old->update(['status' => 'disconnected']);
    ChannelConnection::create(['channel' => 'whatsapp', 'external_id' => 'PHONE_NEW_2', 'name' => 'New WA', 'status' => 'connected', 'credentials' => ['access_token' => 'y', 'phone_number_id' => 'PHONE_NEW_2']]);

    $response = $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.send', $oldConversation->id), ['body' => 'Following up from our new number'])
        ->assertSuccessful()
        ->assertJson(['success' => true, 'moved_to_current_number' => true]);

    $new = Conversation::where('account_id', 'PHONE_NEW_2')->where('external_id', '923001234567')->first();
    expect($new)->not->toBeNull()
        ->and(Message::where('external_id', 'wamid.reply.new')->value('conversation_id'))->toBe($new->id)
        ->and(Message::where('conversation_id', $oldConversation->id)->count())->toBe(1);
    Http::assertSent(fn ($r) => str_contains($r->url(), '/PHONE_NEW_2/messages'));
});
