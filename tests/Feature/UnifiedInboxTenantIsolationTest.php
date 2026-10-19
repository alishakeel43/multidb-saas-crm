<?php

use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Webkul\UnifiedInbox\Http\Controllers\WebhookController;
use Webkul\UnifiedInbox\Models\ChannelConnection;
use Webkul\UnifiedInbox\Models\Conversation;
use Webkul\UnifiedInbox\Services\CentralWebhookRouter;

it('routes a signed webhook to its mapped tenant database and deduplicates retries', function () {
    $tenantOneDatabase = tempnam(sys_get_temp_dir(), 'inbox-tenant-one-');
    $tenantTwoDatabase = tempnam(sys_get_temp_dir(), 'inbox-tenant-two-');
    $tenantOneSlug = 'inbox-isolation-one-'.uniqid();
    $tenantTwoSlug = 'inbox-isolation-two-'.uniqid();

    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => null,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'unified-inbox.meta.app_secret' => 'two-tenant-test-secret',
    ]);
    DB::purge('landlord');
    DB::purge('tenant');

    try {
        Schema::connection('landlord')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('database');
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::connection('landlord')->create('tenant_channel_routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('channel', 40);
            $table->string('external_id', 100);
            $table->string('waba_id', 100)->nullable();
            $table->string('account_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['channel', 'external_id']);
        });

        $tenantOne = Tenant::create([
            'name' => 'Inbox Tenant One',
            'slug' => $tenantOneSlug,
            'database' => $tenantOneDatabase,
            'status' => 'active',
        ]);
        $tenantTwo = Tenant::create([
            'name' => 'Inbox Tenant Two',
            'slug' => $tenantTwoSlug,
            'database' => $tenantTwoDatabase,
            'status' => 'active',
        ]);

        $createTenantInboxTables = function (): void {
            Schema::connection('tenant')->create('unified_inbox_connections', function (Blueprint $table) {
                $table->increments('id');
                $table->string('channel', 40);
                $table->string('external_id', 100);
                $table->string('name');
                $table->string('status', 30)->default('pending');
                $table->text('credentials')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->unique(['channel', 'external_id']);
            });

            Schema::connection('tenant')->create('unified_inbox_conversations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('channel', 40);
                $table->string('account_id', 100);
                $table->string('external_id', 191);
                $table->string('customer_name');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('status', 20)->default('open');
                $table->unsignedInteger('unread_count')->default(0);
                $table->text('last_message_preview')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('last_incoming_at')->nullable();
                $table->unsignedInteger('person_id')->nullable();
                $table->unsignedInteger('lead_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['channel', 'account_id', 'external_id']);
            });

            Schema::connection('tenant')->create('unified_inbox_messages', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('conversation_id');
                $table->string('direction', 10);
                $table->string('external_id', 191)->nullable();
                $table->text('body')->nullable();
                $table->string('status', 20);
                $table->json('attachments')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->foreign('conversation_id')
                    ->references('id')
                    ->on('unified_inbox_conversations')
                    ->cascadeOnDelete();
                $table->unique(['conversation_id', 'external_id']);
            });

            Schema::connection('tenant')->create('persons', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->json('contact_numbers')->nullable();
            });
        };

        $tenantOne->execute($createTenantInboxTables);
        $tenantTwo->execute($createTenantInboxTables);
        $tenantTwo->execute(function () {
            ChannelConnection::create([
                'channel' => 'whatsapp',
                'external_id' => 'TENANT_TWO_PHONE',
                'name' => 'Tenant Two WhatsApp',
                'status' => 'connected',
                'credentials' => ['access_token' => 'tenant-two-test-token'],
            ]);
        });

        $router = app(CentralWebhookRouter::class);
        $router->registerRoute($tenantTwo->id, 'whatsapp', 'TENANT_TWO_PHONE', 'TENANT_TWO_WABA', 'Tenant Two WhatsApp');
        $tenantOne->makeCurrent();

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'TENANT_TWO_WABA',
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => 'TENANT_TWO_PHONE'],
                        'contacts' => [['wa_id' => 'external-sender', 'profile' => ['name' => 'Mapped Customer']]],
                        'messages' => [[
                            'from' => 'external-sender',
                            'id' => 'wamid.tenant-two-unique',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Only tenant two should receive this.'],
                        ]],
                    ],
                ]],
            ]],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'two-tenant-test-secret');
        $request = Request::create('/api/meta/webhook', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
        $controller = app(WebhookController::class);

        $controller->handle($request);
        $controller->handle($request);

        expect(Tenant::current()?->id)->toBe($tenantOne->id)
            ->and(Conversation::query()->count())->toBe(0);

        $tenantTwoConversation = null;
        $tenantTwo->execute(function () use (&$tenantTwoConversation) {
            $tenantTwoConversation = Conversation::with('messages')->first();
        });

        expect(Tenant::current()?->id)->toBe($tenantOne->id)
            ->and($tenantTwoConversation)->not->toBeNull()
            ->and($tenantTwoConversation->unread_count)->toBe(1)
            ->and($tenantTwoConversation->messages)->toHaveCount(1);
    } finally {
        Tenant::forgetCurrent();

        if (isset($router, $tenantTwo)) {
            $router->removeRoute($tenantTwo->id, 'whatsapp', 'TENANT_TWO_PHONE');
        }

        DB::purge('tenant');
        DB::purge('landlord');

        foreach ([$tenantOneDatabase, $tenantTwoDatabase] as $databasePath) {
            if (is_file($databasePath)) {
                unlink($databasePath);
            }
        }

        File::deleteDirectory(storage_path('tenants/'.$tenantOneSlug));
        File::deleteDirectory(storage_path('tenants/'.$tenantTwoSlug));
    }
});
