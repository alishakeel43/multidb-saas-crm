<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Task\Models\Task;
use Webkul\Task\Services\TaskWorkflow;

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

    Schema::create('task_stages', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code', 50)->unique();
        $table->string('name', 100);
        $table->string('color', 20)->default('#2563EB');
        $table->unsignedInteger('sort_order')->default(1);
        $table->boolean('is_default')->default(false);
        $table->timestamps();
    });

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
});

it('stores tasks in the current database without a tenant id column', function () {
    $taskColumns = collect(DB::select('pragma table_info(tasks)'))->pluck('name');
    $stageColumns = collect(DB::select('pragma table_info(task_stages)'))->pluck('name');

    expect($taskColumns)->not->toContain('tenant_id')
        ->and($stageColumns)->not->toContain('tenant_id');
});

it('creates a task on the default stage and records completion metadata', function () {
    $workflow = app(TaskWorkflow::class);

    $task = $workflow->create([
        'title' => 'Call the buyer',
        'priority' => Task::PRIORITY_HIGH,
        'due_date' => Carbon::tomorrow()->toDateString(),
    ]);

    expect($task->status)->toBe(Task::STATUS_PENDING)
        ->and($task->completed_at)->toBeNull();

    $done = $workflow->changeStatus($task, Task::STATUS_COMPLETED);

    expect($done->status)->toBe(Task::STATUS_COMPLETED)
        ->and($done->completed_at)->not->toBeNull();

    $reopened = $workflow->changeStatus($done, Task::STATUS_PENDING);

    expect($reopened->completed_at)->toBeNull()
        ->and($reopened->completed_by)->toBeNull();
});

it('treats past due dates as overdue and ignores finished tasks', function () {
    $workflow = app(TaskWorkflow::class);

    $overdue = $workflow->create([
        'title' => 'Yesterday',
        'due_date' => Carbon::yesterday()->toDateString(),
    ]);

    $finished = $workflow->create([
        'title' => 'Already done',
        'status' => Task::STATUS_COMPLETED,
        'due_date' => Carbon::yesterday()->toDateString(),
    ]);

    $laterToday = $workflow->create([
        'title' => 'Later today',
        'due_date' => Carbon::today()->toDateString(),
        'due_time' => Carbon::now()->addHour()->format('H:i'),
    ]);

    expect($overdue->is_overdue)->toBeTrue()
        ->and($finished->is_overdue)->toBeFalse()
        ->and($laterToday->is_overdue)->toBeFalse()
        ->and(Task::query()->overdue()->pluck('id')->all())->toBe([$overdue->id]);
});

it('saves a task when a related record was not chosen', function () {
    $task = app(TaskWorkflow::class)->create([
        'title' => 'Unlinked task',
        'entity_type' => 'leads',
        'entity_id' => null,
        'due_time' => '15:30:00',
    ]);

    expect($task->entity_type)->toBeNull()
        ->and($task->entity_id)->toBeNull()
        ->and($task->due_time)->not->toBeNull()
        ->and(Task::query()->where('title', 'Unlinked task')->exists())->toBeTrue();
});

it('moves tasks off a removed stage', function () {
    $workflow = app(TaskWorkflow::class);

    $task = $workflow->create([
        'title' => 'Move me',
        'status' => Task::STATUS_PENDING,
    ]);

    $moved = $workflow->moveStage(Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS);

    expect($moved)->toBe(1)
        ->and($task->fresh()->status)->toBe(Task::STATUS_IN_PROGRESS);
});
