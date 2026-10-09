<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Admin\Http\Controllers\Settings\AttributeController;
use Webkul\Attribute\Repositories\AttributeValueRepository;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('sqlite');

    Schema::create('attributes', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code');
        $table->string('type');
        $table->string('entity_type');
    });

    Schema::create('attribute_values', function (Blueprint $table) {
        $table->increments('id');
        $table->string('entity_type');
        $table->unsignedInteger('entity_id');
        $table->unsignedInteger('attribute_id');
        $table->json('json_value')->nullable();
    });

    DB::table('attributes')->insert([
        'id' => 1,
        'code' => 'emails',
        'type' => 'email',
        'entity_type' => 'persons',
    ]);

    DB::table('attribute_values')->insert([
        'entity_type' => 'persons',
        'entity_id' => 10,
        'attribute_id' => 1,
        'json_value' => json_encode([['value' => 'taken@example.test', 'label' => 'work']]),
    ]);
});

afterEach(function () {
    DB::purge('sqlite');
});

it('checks uniqueness across existing entities when creating a new entity', function () {
    $attribute = DB::table('attributes')->where('id', 1)->first();
    $repository = app(AttributeValueRepository::class);

    expect($repository->isValueUnique(null, 'persons', $attribute, 'taken@example.test'))->toBeFalse()
        ->and($repository->isValueUnique(null, 'persons', $attribute, 'new@example.test'))->toBeTrue();
});

it('allows an existing entity to retain its own unique value', function () {
    $attribute = DB::table('attributes')->where('id', 1)->first();
    $repository = app(AttributeValueRepository::class);

    expect($repository->isValueUnique(10, 'persons', $attribute, 'taken@example.test'))->toBeTrue();
});

it('returns a correct uniqueness response from the attribute validation endpoint', function () {
    app()->instance('request', Request::create('/admin/settings/attributes/check-unique-validation', 'GET', [
        'entity_id' => null,
        'entity_type' => 'persons',
        'attribute_code' => 'emails',
        'attribute_value' => 'taken@example.test',
    ]));

    $response = app(AttributeController::class)->checkUniqueValidation();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true)['validated'])->toBeFalse();
});

it('returns a validation response when the requested attribute is unknown', function () {
    app()->instance('request', Request::create('/admin/settings/attributes/check-unique-validation', 'GET', [
        'entity_type' => 'persons',
        'attribute_code' => 'missing_attribute',
        'attribute_value' => 'value',
    ]));

    $response = app(AttributeController::class)->checkUniqueValidation();

    expect($response->getStatusCode())->toBe(422);
});
