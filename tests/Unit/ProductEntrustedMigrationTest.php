<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

it('renames the lead_value attribute label to Product Entrusted', function () {
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
        $table->string('type')->nullable();
        $table->string('validation')->nullable();
        $table->string('entity_type')->nullable();
        $table->timestamps();
    });

    DB::table('attributes')->insert([
        'code' => 'lead_value',
        'name' => 'Lead Value',
        'type' => 'price',
        'validation' => 'decimal',
        'entity_type' => 'leads',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $rename = require base_path('packages/Webkul/Lead/src/Database/Migrations/2026_10_06_160000_rename_lead_value_attribute_to_product_entrusted.php');
    $rename->up();

    $attribute = DB::table('attributes')->where('code', 'lead_value')->first();

    expect($attribute->name)->toBe('Product Entrusted');
});

it('converts entrusted values to text without losing meaningful values and supports rollback', function () {
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
        $table->string('type')->nullable();
        $table->string('validation')->nullable();
        $table->boolean('quick_add')->default(false);
        $table->string('entity_type')->nullable();
        $table->timestamps();
    });

    Schema::create('leads', function (Blueprint $table) {
        $table->increments('id');
        $table->decimal('lead_value', 12, 4)->nullable();
    });

    DB::table('attributes')->insert([
        'code' => 'lead_value',
        'name' => 'Product Entrusted',
        'type' => 'price',
        'validation' => 'decimal',
        'quick_add' => false,
        'entity_type' => 'leads',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('leads')->insert([
        ['id' => 1, 'lead_value' => '125.5000'],
        ['id' => 2, 'lead_value' => '0.0000'],
        ['id' => 3, 'lead_value' => null],
    ]);

    $migration = require base_path('packages/Webkul/Lead/src/Database/Migrations/2026_10_06_161000_store_product_entrusted_as_text.php');
    $migration->up();

    expect(Schema::getColumnType('leads', 'lead_value'))->toBe('varchar')
        ->and(DB::table('leads')->orderBy('id')->pluck('lead_value')->all())->toBe(['125.5', '0', null])
        ->and(DB::table('attributes')->value('type'))->toBe('text')
        ->and(DB::table('attributes')->value('validation'))->toBeNull()
        ->and(DB::table('attributes')->value('quick_add'))->toBe(1);

    $migration->down();

    expect(Schema::getColumnType('leads', 'lead_value'))->toBe('numeric')
        ->and(DB::table('leads')->where('id', 1)->value('lead_value'))->toBe(125.5)
        ->and(DB::table('attributes')->value('type'))->toBe('price')
        ->and(DB::table('attributes')->value('validation'))->toBe('decimal')
        ->and(DB::table('attributes')->value('quick_add'))->toBe(0);
});
