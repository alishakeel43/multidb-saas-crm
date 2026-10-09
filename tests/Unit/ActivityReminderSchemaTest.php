<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

it('creates activity reminder schema without tenant_id', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
    });
    Schema::create('activities', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->string('type')->nullable();
        $table->boolean('is_done')->default(false);
        $table->unsignedInteger('user_id')->nullable();
        $table->dateTime('schedule_from')->nullable();
    });

    $migration = require base_path('packages/Webkul/Activity/src/Database/Migrations/2026_10_05_100000_create_activity_reminders.php');
    $migration->up();

    expect(Schema::hasTable('activity_reminders'))->toBeTrue()
        ->and(Schema::hasColumn('activities', 'reminder_minutes'))->toBeTrue()
        ->and(Schema::hasColumn('activity_reminders', 'tenant_id'))->toBeFalse()
        ->and(Schema::hasTable('notifications'))->toBeTrue()
        ->and(Schema::hasColumn('notifications', 'tenant_id'))->toBeFalse();
});
