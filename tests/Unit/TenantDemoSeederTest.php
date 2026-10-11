<?php

use Database\Seeders\TenantDemoSeeder;
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
        'attributes',
        'email_templates',
        'workflows',
    ] as $table) {
        Schema::create($table, fn ($blueprint) => $blueprint->increments('id'));
        DB::table($table)->insert(['id' => 1]);
    }

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

it('seeds linked demo CRM records idempotently', function () {
    $seeder = app(TenantDemoSeeder::class);

    $seeder->run();
    $seeder->run();

    expect(DB::table('organizations')->count())->toBe(3)
        ->and(DB::table('persons')->count())->toBe(6)
        ->and(DB::table('leads')->count())->toBe(6)
        ->and(DB::table('activities')->count())->toBe(1)
        ->and(DB::table('lead_activities')->count())->toBe(6)
        ->and(DB::table('person_activities')->count())->toBe(6)
        ->and(DB::table('activity_participants')->count())->toBe(6)
        ->and(DB::table('organizations')->whereNotIn('user_id', DB::table('users')->select('id'))->count())->toBe(0)
        ->and(DB::table('persons')->whereNotIn('user_id', DB::table('users')->select('id'))->count())->toBe(0)
        ->and(DB::table('persons')->whereNotIn('organization_id', DB::table('organizations')->select('id'))->count())->toBe(0)
        ->and(DB::table('leads')->whereNotIn('user_id', DB::table('users')->select('id'))->count())->toBe(0)
        ->and(DB::table('leads')->whereNotIn('person_id', DB::table('persons')->select('id'))->count())->toBe(0)
        ->and(DB::table('leads')->whereNotIn('lead_source_id', DB::table('lead_sources')->select('id'))->count())->toBe(0)
        ->and(DB::table('leads')->whereNotIn('lead_type_id', DB::table('lead_types')->select('id'))->count())->toBe(0)
        ->and(DB::table('leads')->whereNotIn('lead_pipeline_id', DB::table('lead_pipelines')->select('id'))->count())->toBe(0)
        ->and(DB::table('leads')->whereNotIn('lead_pipeline_stage_id', DB::table('lead_pipeline_stages')->select('id'))->count())->toBe(0)
        ->and(DB::table('activities')->whereNotIn('user_id', DB::table('users')->select('id'))->count())->toBe(0)
        ->and(DB::table('lead_activities')->whereNotIn('lead_id', DB::table('leads')->select('id'))->count())->toBe(0)
        ->and(DB::table('person_activities')->whereNotIn('person_id', DB::table('persons')->select('id'))->count())->toBe(0)
        ->and(DB::table('activity_participants')->whereNotIn('person_id', DB::table('persons')->select('id'))->count())->toBe(0)
        ->and(DB::table('persons')->whereNotIn('id', DB::table('leads')->select('person_id'))->count())->toBe(0)
        ->and(DB::table('organizations')->whereNotIn('id', DB::table('persons')->select('organization_id'))->count())->toBe(0)
        ->and(DB::table('leads')->distinct()->count('person_id'))->toBe(6)
        ->and(DB::table('persons')->distinct()->count('organization_id'))->toBe(3);
});

it('requires an existing tenant user before creating demo records', function () {
    DB::table('users')->delete();

    expect(fn () => app(TenantDemoSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'Provision a tenant administrator')
        ->and(DB::table('organizations')->count())->toBe(0);
});
