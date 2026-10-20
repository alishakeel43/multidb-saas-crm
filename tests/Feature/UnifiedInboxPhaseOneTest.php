<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Webkul\UnifiedInbox\Models\ChannelConnection;
use Webkul\UnifiedInbox\Models\Conversation;
use Webkul\UnifiedInbox\Models\Message;
use Webkul\UnifiedInbox\Models\QuickReply;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

afterEach(function () {
    $tenant = DB::connection('tenant');
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
    $tenantDb = (string) DB::connection('tenant')->getDatabaseName();
    if (in_array($tenantDb, ['tenant_demo', 'tenant_bootstrap'], true) || ! str_starts_with($tenantDb, 'tenant_test')) {
        throw new RuntimeException("UnifiedInboxTest refused to run against database [{$tenantDb}]. Use the isolated test DB (see phpunit.xml).");
    }

    // The reconnect above escapes DatabaseTransactions; wrap the tenant connection again.
    DB::connection('tenant')->beginTransaction();

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

function uiP1Setup(): Conversation
{
    ChannelConnection::updateOrCreate(
        ['channel' => 'whatsapp', 'external_id' => 'PHONE_P1'],
        ['name' => 'P1 WA', 'status' => 'connected', 'credentials' => ['access_token' => 'dummy', 'phone_number_id' => 'PHONE_P1']]
    );

    return Conversation::create([
        'channel' => 'whatsapp',
        'account_id' => 'PHONE_P1',
        'external_id' => '15550101010',
        'customer_name' => 'Phase One Pat',
        'status' => 'open',
        'last_message_preview' => 'Hi',
        'last_message_at' => now(),
        'last_incoming_at' => now()->subHours(2),
    ]);
}

it('renders the Meta-style rail, ticks, window indicator and labels', function () {
    $conversation = uiP1Setup();
    $conversation->update(['labels' => ['VIP']]);
    Message::create(['conversation_id' => $conversation->id, 'direction' => 'outbound', 'external_id' => 'wamid.p1', 'body' => 'Read me', 'status' => 'read', 'occurred_at' => now()]);

    $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.index', ['conversation_id' => $conversation->id]))
        ->assertSuccessful()
        ->assertSee('ui-rail', false)
        ->assertSee('Follow up')
        ->assertSee('ui-tick-read', false)
        ->assertSee('24h reply window open')
        ->assertSee('VIP');
});

it('filters by folder and label and toggles follow up / spam flags', function () {
    $conversation = uiP1Setup();

    $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.flags', $conversation->id), ['is_follow_up' => true])
        ->assertSuccessful()->assertJson(['is_follow_up' => true]);

    $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.labels', $conversation->id), ['labels' => ['Hot', 'Hot', ' ']])
        ->assertSuccessful()->assertJson(['labels' => ['Hot']]);

    $this->actingAs($this->user, 'user')->get(route('admin.unified_inbox.index', ['folder' => 'follow_up']))->assertSee('Phase One Pat');
    $this->actingAs($this->user, 'user')->get(route('admin.unified_inbox.index', ['label' => 'Hot']))->assertSee('Phase One Pat');
    $this->actingAs($this->user, 'user')->get(route('admin.unified_inbox.index', ['folder' => 'done']))->assertDontSee('Phase One Pat');

    $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.flags', $conversation->id), ['is_spam' => true])
        ->assertSuccessful();

    $this->actingAs($this->user, 'user')->get(route('admin.unified_inbox.index', ['folder' => 'inbox']))->assertDontSee('Phase One Pat');
    $this->actingAs($this->user, 'user')->get(route('admin.unified_inbox.index', ['folder' => 'spam']))->assertSee('Phase One Pat');
});

it('blocks free text after the WhatsApp 24h window closes', function () {
    Http::fake();
    $conversation = uiP1Setup();
    $conversation->update(['last_incoming_at' => now()->subHours(25)]);

    $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.conversations.send', $conversation->id), ['body' => 'late'])
        ->assertStatus(422)->assertJson(['window_closed' => true]);

    Http::assertNothingSent();

    $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.index', ['conversation_id' => $conversation->id]))
        ->assertSee('Send template');
});

it('manages saved quick replies', function () {
    $create = $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.quick_replies.store'), ['title' => 'Hello P1', 'shortcut' => 'hp1', 'body' => 'Hello from P1'])
        ->assertSuccessful();
    $id = $create->json('data.id');

    $this->actingAs($this->user, 'user')
        ->putJson(route('admin.unified_inbox.quick_replies.update', $id), ['title' => 'Hello P1b', 'shortcut' => 'hp1', 'body' => 'Updated'])
        ->assertSuccessful();

    expect(QuickReply::find($id)->body)->toBe('Updated');

    $this->actingAs($this->user, 'user')
        ->postJson(route('admin.unified_inbox.quick_replies.store'), ['title' => 'Bad', 'shortcut' => 'has space', 'body' => 'x'])
        ->assertStatus(422);

    $this->actingAs($this->user, 'user')->deleteJson(route('admin.unified_inbox.quick_replies.destroy', $id))->assertSuccessful();
    expect(QuickReply::find($id))->toBeNull();
});

it('deletes a message from the inbox on every channel without calling Meta', function (string $channel) {
    Http::fake();
    $conversation = uiP1Setup();
    $conversation->update(['channel' => $channel]);
    $older = Message::create(['conversation_id' => $conversation->id, 'direction' => 'inbound', 'external_id' => 'mid.keep', 'body' => 'Keep me', 'status' => 'received', 'occurred_at' => now()->subMinute()]);
    $message = Message::create(['conversation_id' => $conversation->id, 'direction' => 'outbound', 'external_id' => 'mid.delete', 'body' => 'Secret price', 'status' => 'sent', 'occurred_at' => now()]);
    $conversation->update(['last_message_preview' => 'Secret price']);

    $this->actingAs($this->user, 'user')
        ->deleteJson(route('admin.unified_inbox.conversations.messages.destroy', [$conversation->id, $message->id]))
        ->assertSuccessful()->assertJson(['success' => true]);

    $message->refresh();
    expect($message->isDeleted())->toBeTrue()
        ->and($message->body)->toBe(Message::DELETED_BODY)
        ->and($message->external_id)->toBe('mid.delete')
        ->and($message->metadata['deleted_by'])->toBe($this->user->id)
        ->and($older->fresh()->body)->toBe('Keep me')
        ->and($conversation->fresh()->last_message_preview)->toBe(Message::DELETED_BODY);

    Http::assertNothingSent();

    $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.index', ['conversation_id' => $conversation->id]))
        ->assertSee('You deleted this message')
        ->assertDontSee('Secret price');
})->with(['whatsapp', 'messenger', 'instagram']);

it('does not delete a message through another conversation', function () {
    $conversation = uiP1Setup();
    $other = Conversation::create(['channel' => 'whatsapp', 'account_id' => 'PHONE_P1', 'external_id' => '15550202020', 'customer_name' => 'Other', 'status' => 'open']);
    $message = Message::create(['conversation_id' => $other->id, 'direction' => 'inbound', 'external_id' => 'wamid.other', 'body' => 'Not yours', 'status' => 'received', 'occurred_at' => now()]);

    $this->actingAs($this->user, 'user')
        ->deleteJson(route('admin.unified_inbox.conversations.messages.destroy', [$conversation->id, $message->id]))
        ->assertNotFound();

    expect($message->fresh()->isDeleted())->toBeFalse();
});

it('marks a message the customer unsent on Instagram as deleted instead of storing a new one', function () {
    expect($this->tenant)->not->toBeNull();
    config()->set('unified-inbox.meta.app_secret', 'test_meta_app_secret');

    ChannelConnection::updateOrCreate(
        ['channel' => 'instagram', 'external_id' => 'IG_UNSEND_TEST'],
        ['name' => 'Unsend IG', 'status' => 'connected', 'credentials' => ['access_token' => 'dummy']]
    );
    $conversation = Conversation::create(['channel' => 'instagram', 'account_id' => 'IG_UNSEND_TEST', 'external_id' => 'IGSID_UNSEND', 'customer_name' => 'Unsend Ula', 'status' => 'open']);
    $message = Message::create(['conversation_id' => $conversation->id, 'direction' => 'inbound', 'external_id' => 'mid.unsent', 'body' => 'Oops', 'status' => 'received', 'occurred_at' => now()]);

    $router = app(CentralWebhookRouter::class);
    $router->registerRoute($this->tenant->id, 'instagram', 'IG_UNSEND_TEST', null, 'Unsend IG');

    $body = json_encode([
        'object' => 'instagram',
        'entry' => [[
            'id' => 'IG_UNSEND_TEST',
            'messaging' => [[
                'sender' => ['id' => 'IGSID_UNSEND'],
                'recipient' => ['id' => 'IG_UNSEND_TEST'],
                'timestamp' => now()->getTimestampMs(),
                'message' => ['mid' => 'mid.unsent', 'is_deleted' => true],
            ]],
        ]],
    ], JSON_THROW_ON_ERROR);

    $response = $this->call('POST', '/api/meta/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'test_meta_app_secret'),
    ], $body);
    $router->removeRoute($this->tenant->id, 'instagram', 'IG_UNSEND_TEST');

    $response->assertStatus(200);
    $message->refresh();
    expect($message->isDeleted())->toBeTrue()
        ->and($message->metadata['deleted_source'])->toBe('customer')
        ->and($conversation->messages()->count())->toBe(1);
});

it('uploads and sends a WhatsApp document attachment via Cloud API media', function () {
    Storage::fake('local');
    Http::fake([
        'https://graph.facebook.com/*/PHONE_P1/media' => Http::response(['id' => '9988776655'], 200),
        'https://graph.facebook.com/*/PHONE_P1/messages' => Http::response(['messages' => [['id' => 'wamid.doc']]], 200),
    ]);
    $conversation = uiP1Setup();

    $this->actingAs($this->user, 'user')
        ->post(route('admin.unified_inbox.conversations.attachments.store', $conversation->id), [
            'file' => UploadedFile::fake()->create('quote.pdf', 20, 'application/pdf'),
            'caption' => 'Your quote',
        ], ['Accept' => 'application/json'])
        ->assertSuccessful()->assertJson(['success' => true]);

    $message = Message::where('external_id', 'wamid.doc')->first();
    expect($message)->not->toBeNull()
        ->and($message->attachments[0]['id'])->toBe('9988776655')
        ->and($message->attachments[0]['type'])->toBe('document');

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/messages') && $r['type'] === 'document' && $r['document']['id'] === '9988776655');

    $this->actingAs($this->user, 'user')
        ->get(route('admin.unified_inbox.conversations.attachments.show', [$conversation->id, $message->id, 0]))
        ->assertSuccessful();
});
