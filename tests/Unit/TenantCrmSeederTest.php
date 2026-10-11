<?php

use Database\Seeders\TenantCrmSeeder;
use Illuminate\Database\Schema\Blueprint;
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
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('tenant');
    DB::purge('landlord');
    DB::setDefaultConnection('tenant');

    Schema::connection('tenant')->create('attributes', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('code');
        $table->string('name');
        $table->string('type');
        $table->string('lookup_type')->nullable();
        $table->string('entity_type');
        $table->integer('sort_order')->nullable();
        $table->string('validation')->nullable();
        $table->boolean('is_required')->default(0);
        $table->boolean('is_unique')->default(0);
        $table->boolean('quick_add')->default(0);
        $table->boolean('is_user_defined')->default(1);
        $table->unique(['code', 'entity_type']);
        $table->timestamps();
    });

    foreach ([
        'countries',
        'country_states',
        'email_templates',
        'lead_pipelines',
        'lead_pipeline_stages',
        'lead_types',
        'lead_sources',
        'workflows',
        'roles',
    ] as $table) {
        Schema::connection('tenant')->create($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->id();

            if ($table === 'roles') {
                $blueprint->string('name');
                $blueprint->text('description')->nullable();
                $blueprint->string('permission_type');
                $blueprint->text('permissions')->nullable();
            }

            $blueprint->timestamps();
        });
    }
});

afterEach(function () {
    DB::purge('tenant');
    DB::purge('landlord');
});

it('reseeds attributes when migrations only inserted partial person columns', function () {
    // Mimic Installer migrations that insert a few person attributes before CRM seeding.
    DB::connection('tenant')->table('attributes')->insert([
        [
            'code' => 'job_title',
            'name' => 'Job Title',
            'type' => 'text',
            'entity_type' => 'persons',
            'lookup_type' => null,
            'validation' => null,
            'sort_order' => 4,
            'is_required' => 0,
            'is_unique' => 0,
            'quick_add' => 0,
            'is_user_defined' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'code' => 'user_id',
            'name' => 'Sales Owner',
            'type' => 'lookup',
            'entity_type' => 'persons',
            'lookup_type' => 'users',
            'validation' => null,
            'sort_order' => 5,
            'is_required' => 0,
            'is_unique' => 0,
            'quick_add' => 0,
            'is_user_defined' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Mark other baselines non-empty so only attributes reseed runs.
    foreach (['countries', 'country_states', 'email_templates', 'lead_pipelines', 'lead_pipeline_stages', 'lead_types', 'lead_sources', 'workflows'] as $table) {
        DB::connection('tenant')->table($table)->insert([
            'id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->seed(TenantCrmSeeder::class);

    expect(
        DB::connection('tenant')->table('attributes')
            ->where('entity_type', 'persons')
            ->where('code', 'organization_id')
            ->exists()
    )->toBeTrue()
        ->and(
            DB::connection('tenant')->table('attributes')
                ->where('entity_type', 'persons')
                ->where('code', 'name')
                ->exists()
        )->toBeTrue()
        ->and(
            DB::connection('tenant')->table('attributes')
                ->where('entity_type', 'leads')
                ->where('code', 'title')
                ->exists()
        )->toBeTrue();
});
