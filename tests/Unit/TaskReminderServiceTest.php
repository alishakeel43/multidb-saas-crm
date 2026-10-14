<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Notification\Models\UserNotification;
use Webkul\Task\Models\Task;
use Webkul\Task\Services\TaskReminderService;

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

    Schema::create('tasks', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('status', 50)->default('pending');
        $table->string('priority', 30)->default('medium');
        $table->date('due_date')->nullable();
        $table->time('due_time')->nullable();
        $table->unsignedInteger('assigned_to')->nullable();
        $table->unsignedInteger('created_by')->nullable();
        $table->unsignedInteger('completed_by')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->string('entity_type', 60)->nullable();
        $table->unsignedInteger('entity_id')->nullable();
        $table->timestamp('reminder_sent_at')->nullable();
        $table->timestamps();
    });

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

it('notifies the assignee once for a task that is due today', function () {
    Task::query()->create([
        'title' => 'Send the proposal',
        'status' => Task::STATUS_PENDING,
        'priority' => Task::PRIORITY_MEDIUM,
        'due_date' => Carbon::today()->toDateString(),
        'assigned_to' => 7,
    ]);

    $service = app(TaskReminderService::class);

    expect($service->dispatchDue(Carbon::now()))->toBe(1)
        ->and($service->dispatchDue(Carbon::now()))->toBe(0)
        ->and(UserNotification::query()->where('user_id', 7)->where('type', 'task_due')->count())->toBe(1);
});

it('skips tasks that are not due yet', function () {
    Task::query()->create([
        'title' => 'Next week',
        'status' => Task::STATUS_PENDING,
        'due_date' => Carbon::tomorrow()->toDateString(),
        'assigned_to' => 3,
    ]);

    expect(app(TaskReminderService::class)->dispatchDue(Carbon::now()))->toBe(0)
        ->and(UserNotification::query()->count())->toBe(0);
});
