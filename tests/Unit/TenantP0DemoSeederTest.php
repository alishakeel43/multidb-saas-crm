<?php

use Database\Seeders\TenantDemoSeeder;
use Database\Seeders\TenantP0DemoSeeder;
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
        'app.admin_path' => 'admin',
    ]);

    DB::purge('tenant');
    DB::setDefaultConnection('tenant');

    foreach ([
        'countries',
        'country_states',
        'email_templates',
        'workflows',
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
        $table->integer('score')->default(0);
        $table->boolean('status')->nullable();
        $table->timestamp('closed_at')->nullable();
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

    Schema::create('assignment_rules', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('entity_type')->default('leads');
        $table->string('strategy');
        $table->boolean('is_active')->default(true);
        $table->boolean('only_when_empty')->default(true);
        $table->unsignedInteger('priority')->default(0);
        $table->json('conditions')->nullable();
        $table->timestamps();
    });

    Schema::create('assignment_rule_users', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('assignment_rule_id');
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('assignment_cursors', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('assignment_rule_id');
        $table->unsignedInteger('last_user_id')->nullable();
        $table->timestamps();
    });

    Schema::create('person_consents', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('person_id');
        $table->string('channel');
        $table->boolean('is_opted_in')->default(false);
        $table->string('source')->nullable();
        $table->unsignedInteger('changed_by')->nullable();
        $table->timestamp('captured_at')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('booking_services', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->unsignedInteger('duration_minutes')->default(30);
        $table->unsignedInteger('buffer_minutes')->default(0);
        $table->boolean('is_active')->default(true);
        $table->text('description')->nullable();
        $table->timestamps();
    });

    Schema::create('booking_availabilities', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->unsignedTinyInteger('weekday');
        $table->time('starts_at');
        $table->time('ends_at');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('bookings', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('booking_service_id');
        $table->unsignedInteger('user_id')->nullable();
        $table->unsignedInteger('person_id')->nullable();
        $table->unsignedInteger('lead_id')->nullable();
        $table->unsignedInteger('activity_id')->nullable();
        $table->string('guest_name')->nullable();
        $table->string('guest_email')->nullable();
        $table->string('guest_phone')->nullable();
        $table->dateTime('starts_at');
        $table->dateTime('ends_at');
        $table->string('status');
        $table->text('notes')->nullable();
        $table->timestamp('reminder_sent_at')->nullable();
        $table->timestamps();
    });

    Schema::create('quotes', function ($table) {
        $table->increments('id');
        $table->string('subject');
        $table->string('description')->nullable();
        $table->decimal('discount_percent', 12, 4)->nullable();
        $table->decimal('discount_amount', 12, 4)->nullable();
        $table->decimal('tax_amount', 12, 4)->nullable();
        $table->decimal('adjustment_amount', 12, 4)->nullable();
        $table->decimal('sub_total', 12, 4)->nullable();
        $table->decimal('grand_total', 12, 4)->nullable();
        $table->datetime('expired_at')->nullable();
        $table->unsignedInteger('person_id');
        $table->unsignedInteger('user_id');
        $table->timestamps();
    });

    Schema::create('lead_quotes', function ($table) {
        $table->unsignedInteger('lead_id');
        $table->unsignedInteger('quote_id');
    });

    Schema::create('quote_approvals', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('quote_id')->unique();
        $table->string('status')->default('draft');
        $table->unsignedInteger('requested_by')->nullable();
        $table->unsignedInteger('reviewed_by')->nullable();
        $table->timestamp('requested_at')->nullable();
        $table->timestamp('reviewed_at')->nullable();
        $table->text('notes')->nullable();
        $table->string('delivery_status', 32)->default('none');
        $table->string('order_reference')->nullable();
        $table->timestamp('converted_at')->nullable();
        $table->timestamps();
    });

    Schema::create('lead_scoring_rules', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('field');
        $table->string('operator', 32);
        $table->string('value')->nullable();
        $table->integer('points')->default(0);
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('priority')->default(0);
        $table->timestamps();
    });

    Schema::create('user_notifications', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->string('type', 64);
        $table->string('title');
        $table->text('body')->nullable();
        $table->string('link')->nullable();
        $table->string('entity_type', 64)->nullable();
        $table->unsignedInteger('entity_id')->nullable();
        $table->json('meta')->nullable();
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });

    DB::table('lead_pipelines')->insert(['id' => 1, 'is_default' => true]);
    DB::table('lead_pipeline_stages')->insert(['id' => 1, 'lead_pipeline_id' => 1, 'code' => 'new']);
    DB::table('lead_types')->insert(['id' => 1]);
    DB::table('lead_sources')->insert([
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
        ['id' => 4],
        ['id' => 5],
    ]);
});

afterEach(function () {
    DB::purge('tenant');
});

it('seeds P0 package demo data idempotently via TenantDemoSeeder', function () {
    $seeder = app(TenantDemoSeeder::class);

    $seeder->run();
    $seeder->run();

    expect(DB::table('assignment_rules')->count())->toBe(1)
        ->and(DB::table('assignment_rule_users')->count())->toBe(1)
        ->and(DB::table('assignment_cursors')->count())->toBe(1)
        ->and(DB::table('person_consents')->count())->toBe(5)
        ->and(DB::table('booking_services')->count())->toBe(1)
        ->and(DB::table('booking_availabilities')->count())->toBe(5)
        ->and(DB::table('bookings')->count())->toBe(3)
        ->and(DB::table('quotes')->count())->toBe(3)
        ->and(DB::table('quote_approvals')->count())->toBe(3)
        ->and(DB::table('quote_approvals')->where('delivery_status', 'converted')->count())->toBe(1)
        ->and(DB::table('lead_scoring_rules')->count())->toBe(3)
        ->and(DB::table('user_notifications')->count())->toBe(3)
        ->and(DB::table('user_notifications')->whereNull('read_at')->count())->toBe(2)
        ->and(DB::table('leads')->where('status', 0)->whereNotNull('closed_at')->count())->toBe(1)
        ->and(DB::table('leads')->where('status', 0)->whereNull('closed_at')->count())->toBe(1)
        ->and(DB::table('leads')->where('score', '>', 0)->count())->toBeGreaterThan(0)
        ->and(DB::table('activities')->where('title', 'like', 'DEMO - %')->count())->toBeGreaterThanOrEqual(3);
});

it('requires core demo CRM records before seeding P0 data alone', function () {
    expect(fn () => app(TenantP0DemoSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'Core demo CRM records are missing');
});
