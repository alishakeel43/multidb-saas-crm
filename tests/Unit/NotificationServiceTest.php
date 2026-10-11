<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Notification\Services\NotificationService;

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
    DB::reconnect('sqlite');

    Schema::create('user_notifications', function (Blueprint $table) {
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
});

it('stores and marks notifications as read', function () {
    $service = app(NotificationService::class);

    $note = $service->notify(5, 'assignment', 'Lead assigned', 'Body', '/admin/leads/1', ['lead_id' => 1], 'lead', 1);

    expect($service->unreadCount(5))->toBe(1)
        ->and($service->recent(5))->toHaveCount(1)
        ->and($note->type)->toBe('assignment');

    $service->markRead(5, $note->id);

    expect($service->unreadCount(5))->toBe(0);
});
