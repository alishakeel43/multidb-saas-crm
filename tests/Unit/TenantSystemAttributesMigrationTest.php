<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

it('restores only missing lead and person system attributes', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    Schema::create('attributes', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code');
        $table->string('name');
        $table->string('type');
        $table->string('lookup_type')->nullable();
        $table->string('entity_type');
        $table->integer('sort_order')->nullable();
        $table->string('validation')->nullable();
        $table->boolean('is_required')->default(false);
        $table->boolean('is_unique')->default(false);
        $table->boolean('quick_add')->default(false);
        $table->boolean('is_user_defined')->default(true);
        $table->timestamps();
        $table->unique(['code', 'entity_type']);
    });

    DB::table('attributes')->insert([
        'code' => 'description',
        'name' => 'Tenant Description',
        'type' => 'textarea',
        'entity_type' => 'leads',
        'is_user_defined' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require base_path('packages/Webkul/Lead/src/Database/Migrations/2026_10_09_120000_restore_missing_system_attributes.php');
    $migration->up();
    $migration->up();

    $contactMigration = require base_path('packages/Webkul/Lead/src/Database/Migrations/2026_10_09_130000_restore_missing_person_contact_attributes.php');
    $contactMigration->up();
    $contactMigration->up();

    expect(DB::table('attributes')->count())->toBe(10)
        ->and(DB::table('attributes')->where('code', 'description')->where('entity_type', 'leads')->value('name'))->toBe('Tenant Description')
        ->and(DB::table('attributes')->where('code', 'title')->where('entity_type', 'leads')->value('is_required'))->toBe(1)
        ->and(DB::table('attributes')->where('code', 'lead_value')->where('entity_type', 'leads')->value('type'))->toBe('text')
        ->and(DB::table('attributes')->where('code', 'organization_id')->where('entity_type', 'persons')->value('lookup_type'))->toBe('organizations')
        ->and(DB::table('attributes')->where('code', 'emails')->where('entity_type', 'persons')->value('is_unique'))->toBe(1)
        ->and(DB::table('attributes')->where('code', 'contact_numbers')->where('entity_type', 'persons')->value('quick_add'))->toBe(1);
});
