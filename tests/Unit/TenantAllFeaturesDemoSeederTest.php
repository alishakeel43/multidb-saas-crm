<?php

use Database\Seeders\TenantAllFeaturesDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'tenant',
        'database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [],
    ]);

    DB::purge('tenant');
    DB::setDefaultConnection('tenant');

    foreach ([
        'countries',
        'country_states',
    ] as $table) {
        Schema::create($table, fn ($blueprint) => $blueprint->increments('id'));
        DB::table($table)->insert(['id' => 1]);
    }

    Schema::create('attributes', function ($table) {
        $table->increments('id');
        $table->string('code');
        $table->string('entity_type');
    });
    DB::table('attributes')->insert([
        ['id' => 1, 'code' => 'title', 'entity_type' => 'leads'],
        ['id' => 2, 'code' => 'name', 'entity_type' => 'persons'],
        ['id' => 3, 'code' => 'emails', 'entity_type' => 'persons'],
        ['id' => 4, 'code' => 'organization_id', 'entity_type' => 'persons'],
        ['id' => 5, 'code' => 'name', 'entity_type' => 'organizations'],
        ['id' => 6, 'code' => 'name', 'entity_type' => 'products'],
        ['id' => 7, 'code' => 'name', 'entity_type' => 'quotes'],
    ]);

    Schema::create('email_templates', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('subject');
        $table->text('content');
        $table->timestamps();
    });
    DB::table('email_templates')->insert([
        'id' => 1,
        'name' => 'Activity created',
        'subject' => 'Activity',
        'content' => '<p>Hi</p>',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Schema::create('workflows', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('description')->nullable();
        $table->string('entity_type');
        $table->string('event');
        $table->string('condition_type')->default('and');
        $table->json('conditions')->nullable();
        $table->json('actions')->nullable();
        $table->timestamps();
    });
    DB::table('workflows')->insert([
        'id' => 1,
        'name' => 'Existing workflow',
        'entity_type' => 'leads',
        'event' => 'lead.create.after',
        'condition_type' => 'and',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Schema::create('lead_pipelines', function ($table) {
        $table->increments('id');
        $table->boolean('is_default')->default(false);
    });
    Schema::create('lead_pipeline_stages', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('lead_pipeline_id');
        $table->string('code');
    });
    Schema::create('lead_types', fn ($table) => $table->increments('id'));
    Schema::create('lead_sources', fn ($table) => $table->increments('id'));

    Schema::create('roles', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('permission_type');
    });
    DB::table('roles')->insert(['id' => 1, 'name' => 'Administrator', 'permission_type' => 'all']);

    Schema::create('users', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
    });
    DB::table('users')->insert(['id' => 1, 'name' => 'Tenant Admin', 'email' => 'admin@example.test']);

    Schema::create('organizations', function ($table) {
        $table->increments('id');
        $table->string('name')->unique();
        $table->json('address')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->timestamps();
    });
    Schema::create('persons', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->json('emails');
        $table->json('contact_numbers')->nullable();
        $table->unsignedInteger('organization_id')->nullable();
        $table->string('job_title')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->string('unique_id')->nullable()->unique();
        $table->timestamps();
    });
    Schema::create('leads', function ($table) {
        $table->increments('id');
        $table->string('title');
        $table->text('description')->nullable();
        $table->decimal('lead_value', 12, 4)->nullable();
        $table->boolean('status')->nullable();
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('person_id');
        $table->unsignedInteger('lead_source_id');
        $table->unsignedInteger('lead_type_id');
        $table->unsignedInteger('lead_pipeline_id')->nullable();
        $table->unsignedInteger('lead_pipeline_stage_id')->nullable();
        $table->date('expected_close_date')->nullable();
        $table->timestamps();
    });
    Schema::create('activities', function ($table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->string('type');
        $table->text('comment')->nullable();
        $table->datetime('schedule_from')->nullable();
        $table->datetime('schedule_to')->nullable();
        $table->boolean('is_done')->default(false);
        $table->unsignedInteger('user_id');
        $table->timestamps();
    });
    Schema::create('lead_activities', function ($table) {
        $table->unsignedInteger('lead_id');
        $table->unsignedInteger('activity_id');
    });
    Schema::create('person_activities', function ($table) {
        $table->unsignedInteger('person_id');
        $table->unsignedInteger('activity_id');
    });
    Schema::create('activity_participants', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('activity_id');
        $table->unsignedInteger('person_id')->nullable();
        $table->unsignedInteger('user_id')->nullable();
    });

    Schema::create('quotes', function ($table) {
        $table->increments('id');
        $table->string('subject');
        $table->text('description')->nullable();
        $table->decimal('discount_percent', 12, 4)->default(0);
        $table->decimal('discount_amount', 12, 4)->default(0);
        $table->decimal('tax_amount', 12, 4)->default(0);
        $table->decimal('adjustment_amount', 12, 4)->default(0);
        $table->decimal('sub_total', 12, 4)->default(0);
        $table->decimal('grand_total', 12, 4)->default(0);
        $table->date('expired_at')->nullable();
        $table->unsignedInteger('person_id')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->timestamps();
    });
    Schema::create('quote_items', function ($table) {
        $table->increments('id');
        $table->string('sku')->nullable();
        $table->string('name')->nullable();
        $table->integer('quantity')->default(0);
        $table->decimal('price', 12, 4)->default(0);
        $table->decimal('total', 12, 4)->default(0);
        $table->unsignedInteger('product_id');
        $table->unsignedInteger('quote_id');
        $table->timestamps();
    });
    Schema::create('products', function ($table) {
        $table->increments('id');
        $table->string('sku')->unique();
        $table->string('name')->nullable();
        $table->string('description')->nullable();
        $table->integer('quantity')->default(0);
        $table->decimal('price', 12, 4)->nullable();
        $table->timestamps();
    });
    Schema::create('warehouses', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('contact_name');
        $table->json('contact_emails');
        $table->json('contact_numbers');
        $table->json('contact_address');
        $table->timestamps();
    });
    Schema::create('warehouse_locations', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->unsignedInteger('warehouse_id');
        $table->timestamps();
    });
    Schema::create('product_inventories', function ($table) {
        $table->increments('id');
        $table->integer('in_stock')->default(0);
        $table->integer('allocated')->default(0);
        $table->unsignedInteger('product_id');
        $table->unsignedInteger('warehouse_id')->nullable();
        $table->unsignedInteger('warehouse_location_id')->nullable();
        $table->timestamps();
    });
    Schema::create('tags', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('color')->nullable();
        $table->unsignedInteger('user_id');
        $table->timestamps();
    });
    Schema::create('lead_tags', function ($table) {
        $table->unsignedInteger('tag_id');
        $table->unsignedInteger('lead_id');
    });
    Schema::create('person_tags', function ($table) {
        $table->unsignedInteger('tag_id');
        $table->unsignedInteger('person_id');
    });
    Schema::create('groups', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('description')->nullable();
        $table->timestamps();
    });
    Schema::create('user_groups', function ($table) {
        $table->unsignedInteger('group_id');
        $table->unsignedInteger('user_id');
    });
    Schema::create('marketing_events', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('description');
        $table->date('date');
        $table->timestamps();
    });
    Schema::create('marketing_campaigns', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('subject');
        $table->boolean('status')->default(0);
        $table->string('type');
        $table->string('mail_to');
        $table->unsignedInteger('marketing_template_id')->nullable();
        $table->unsignedInteger('marketing_event_id')->nullable();
        $table->timestamps();
    });
    Schema::create('web_forms', function ($table) {
        $table->increments('id');
        $table->string('form_id')->unique();
        $table->string('title');
        $table->text('description')->nullable();
        $table->text('submit_button_label');
        $table->string('submit_success_action');
        $table->string('submit_success_content');
        $table->boolean('create_lead')->default(0);
        $table->unsignedInteger('lead_pipeline_id')->nullable();
        $table->timestamps();
    });
    Schema::create('web_form_attributes', function ($table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->string('placeholder')->nullable();
        $table->boolean('is_required')->default(0);
        $table->boolean('is_hidden')->default(0);
        $table->integer('sort_order')->nullable();
        $table->unsignedInteger('attribute_id');
        $table->unsignedInteger('web_form_id');
    });
    Schema::create('emails', function ($table) {
        $table->increments('id');
        $table->string('subject')->nullable();
        $table->string('source');
        $table->string('user_type');
        $table->text('reply')->nullable();
        $table->boolean('is_read')->default(0);
        $table->json('folders')->nullable();
        $table->string('message_id')->unique();
        $table->unsignedInteger('person_id')->nullable();
        $table->unsignedInteger('lead_id')->nullable();
        $table->timestamps();
    });
    Schema::create('email_tags', function ($table) {
        $table->unsignedInteger('tag_id');
        $table->unsignedInteger('email_id');
    });
    Schema::create('ma_audiences', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->json('rules')->nullable();
        $table->timestamps();
    });
    Schema::create('ma_campaigns', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('status', 32)->default('draft');
        $table->decimal('budget', 12, 2)->default(0);
        $table->decimal('spent', 12, 2)->default(0);
        $table->string('currency', 8)->default('USD');
        $table->unsignedInteger('audience_id')->nullable();
        $table->string('utm_campaign')->nullable();
        $table->timestamps();
    });
    Schema::create('ma_attributions', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('lead_id')->nullable();
        $table->unsignedInteger('person_id')->nullable();
        $table->unsignedInteger('campaign_id')->nullable();
        $table->unsignedInteger('web_form_id')->nullable();
        $table->string('utm_campaign')->nullable();
        $table->timestamps();
    });
    Schema::create('call_logs', function ($table) {
        $table->increments('id');
        $table->string('provider_call_id')->unique();
        $table->string('direction');
        $table->string('status');
        $table->unsignedInteger('contact_id')->nullable();
        $table->unsignedInteger('lead_id')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->timestamps();
    });
    Schema::create('ai_knowledge_documents', function ($table) {
        $table->increments('id');
        $table->string('original_filename');
        $table->string('storage_path')->unique();
        $table->string('mime_type');
        $table->string('extension', 16);
        $table->unsignedBigInteger('size_bytes');
        $table->char('sha256', 64);
        $table->string('status', 32)->default('pending');
        $table->unsignedInteger('uploaded_by')->nullable();
        $table->timestamps();
    });
    Schema::create('ai_knowledge_chunks', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('document_id');
        $table->unsignedInteger('chunk_index');
        $table->longText('content');
        $table->char('content_hash', 64);
        $table->json('metadata')->nullable();
        $table->timestamps();
    });
    Schema::create('unified_inbox_connections', function ($table) {
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
    Schema::create('unified_inbox_conversations', function ($table) {
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
        $table->unsignedInteger('organization_id')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
        $table->unique(['channel', 'account_id', 'external_id']);
    });
    Schema::create('unified_inbox_messages', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('conversation_id');
        $table->string('direction', 10);
        $table->string('external_id', 191)->nullable();
        $table->text('body')->nullable();
        $table->string('status', 20);
        $table->json('metadata')->nullable();
        $table->timestamp('occurred_at');
        $table->timestamps();
        $table->unique(['conversation_id', 'external_id']);
    });

    DB::table('lead_pipelines')->insert(['id' => 1, 'is_default' => true]);
    DB::table('lead_pipeline_stages')->insert(['id' => 1, 'lead_pipeline_id' => 1, 'code' => 'new']);
    DB::table('lead_types')->insert(['id' => 1]);
    DB::table('lead_sources')->insert([
        ['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5],
    ]);
});

afterEach(function () {
    DB::purge('tenant');
});

it('seeds linked dummy data for every feature including unified channels', function () {
    $seeder = app(TenantAllFeaturesDemoSeeder::class);

    $seeder->run();
    $seeder->run();

    expect(DB::table('products')->count())->toBe(3)
        ->and(DB::table('warehouses')->count())->toBe(1)
        ->and(DB::table('product_inventories')->count())->toBe(3)
        ->and(DB::table('product_inventories')->whereNotIn('product_id', DB::table('products')->select('id'))->count())->toBe(0)
        ->and(DB::table('product_inventories')->whereNotIn('warehouse_id', DB::table('warehouses')->select('id'))->count())->toBe(0)
        ->and(DB::table('quote_items')->count())->toBe(3)
        ->and(DB::table('quote_items')->whereNotIn('quote_id', DB::table('quotes')->select('id'))->count())->toBe(0)
        ->and(DB::table('quote_items')->whereNotIn('product_id', DB::table('products')->select('id'))->count())->toBe(0)
        ->and(DB::table('lead_tags')->whereNotIn('lead_id', DB::table('leads')->select('id'))->count())->toBe(0)
        ->and(DB::table('person_tags')->whereNotIn('person_id', DB::table('persons')->select('id'))->count())->toBe(0)
        ->and(DB::table('user_groups')->count())->toBe(1)
        ->and(DB::table('marketing_campaigns')->whereNotIn('marketing_event_id', DB::table('marketing_events')->select('id'))->count())->toBe(0)
        ->and(DB::table('marketing_campaigns')->whereNotIn('marketing_template_id', DB::table('email_templates')->select('id'))->count())->toBe(0)
        ->and(DB::table('web_form_attributes')->count())->toBe(1)
        ->and(DB::table('emails')->whereNotIn('person_id', DB::table('persons')->select('id'))->count())->toBe(0)
        ->and(DB::table('emails')->whereNotIn('lead_id', DB::table('leads')->select('id'))->count())->toBe(0)
        ->and(DB::table('ma_campaigns')->whereNotIn('audience_id', DB::table('ma_audiences')->select('id'))->count())->toBe(0)
        ->and(DB::table('ma_attributions')->whereNotIn('lead_id', DB::table('leads')->select('id'))->count())->toBe(0)
        ->and(DB::table('ma_attributions')->whereNotIn('campaign_id', DB::table('ma_campaigns')->select('id'))->count())->toBe(0)
        ->and(DB::table('call_logs')->count())->toBe(2)
        ->and(DB::table('call_logs')->whereNotIn('contact_id', DB::table('persons')->select('id'))->count())->toBe(0)
        ->and(DB::table('ai_knowledge_chunks')->whereNotIn('document_id', DB::table('ai_knowledge_documents')->select('id'))->count())->toBe(0)
        ->and(DB::table('unified_inbox_connections')->pluck('channel')->sort()->values()->all())->toBe(['instagram', 'messenger', 'whatsapp'])
        ->and(DB::table('unified_inbox_conversations')->count())->toBe(13)
        ->and(DB::table('unified_inbox_messages')->count())->toBe(34)
        ->and(DB::table('unified_inbox_conversations')->whereNull('person_id')->count())->toBe(0)
        ->and(DB::table('unified_inbox_conversations')->whereNull('lead_id')->count())->toBe(0)
        ->and(DB::table('unified_inbox_conversations')->whereNotIn('person_id', DB::table('persons')->select('id'))->count())->toBe(0)
        ->and(DB::table('unified_inbox_conversations')->whereNotIn('lead_id', DB::table('leads')->select('id'))->count())->toBe(0)
        ->and(DB::table('unified_inbox_messages')->whereNotIn('conversation_id', DB::table('unified_inbox_conversations')->select('id'))->count())->toBe(0);
});
