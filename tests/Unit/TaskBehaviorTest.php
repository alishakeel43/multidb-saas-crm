<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Task\Http\Requests\TaskRequest;
use Webkul\Task\Http\Requests\UpdateStatusRequest;
use Webkul\Task\Models\Task;
use Webkul\Task\Repositories\TaskRepository;

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
    date_default_timezone_set('UTC');
    Carbon::setTestNow(Carbon::parse('2026-10-10 12:00:00', 'UTC'));
    Illuminate\Support\Carbon::setTestNow(Carbon::parse('2026-10-10 12:00:00', 'UTC'));

    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
        $table->boolean('status')->default(true);
    });
    DB::table('users')->insert([
        ['id' => 1, 'name' => 'Active user', 'email' => 'active@example.test', 'status' => true],
        ['id' => 2, 'name' => 'Inactive user', 'email' => 'inactive@example.test', 'status' => false],
    ]);

    Schema::create('persons', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id')->nullable();
        $table->string('name');
    });
    DB::table('persons')->insert(['id' => 1, 'user_id' => 1, 'name' => 'CRM contact']);

    $tasksMigration = require base_path('packages/Webkul/Task/src/Database/Migrations/2026_10_07_140000_create_tasks_table.php');
    $tasksMigration->up();
    $fieldsMigration = require base_path('packages/Webkul/Task/src/Database/Migrations/2026_10_09_100000_add_schedule_and_participants_to_tasks.php');
    $fieldsMigration->up();
    $stagesMigration = require base_path('packages/Webkul/Task/src/Database/Migrations/2026_10_07_150000_create_task_stages_table.php');
    $stagesMigration->up();
    DB::table('task_stages')->insert([
        'code' => 'review',
        'name' => 'In Review',
        'color' => '#2846A0',
        'sort_order' => 2,
        'is_default' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
    Illuminate\Support\Carbon::setTestNow();
    date_default_timezone_set('UTC');
    DB::purge('sqlite');
});

function insertTaskForBehavior(array $attributes): int
{
    return (int) DB::table('tasks')->insertGetId([
        'title' => 'Test task',
        'status' => Task::STATUS_PENDING,
        'priority' => Task::PRIORITY_MEDIUM,
        'created_at' => now(),
        'updated_at' => now(),
        ...$attributes,
    ]);
}

it('filters due-today and overdue tasks without including completed or cancelled tasks', function () {
    $dueToday = insertTaskForBehavior(['due_date' => '2026-10-10', 'due_time' => '11:00:00']);
    $dueLater = insertTaskForBehavior(['due_date' => '2026-10-10', 'due_time' => '13:00:00']);
    $overdue = insertTaskForBehavior(['due_date' => '2026-10-09', 'due_time' => '23:59:00']);
    $completed = insertTaskForBehavior([
        'due_date' => '2026-10-09',
        'status' => Task::STATUS_COMPLETED,
        'completed_at' => '2026-10-09 14:00:00',
    ]);
    $cancelled = insertTaskForBehavior([
        'due_date' => '2026-10-09',
        'status' => Task::STATUS_CANCELLED,
    ]);
    $dueTodayIds = [$dueToday, $dueLater];
    $overdueIds = [$dueToday, $overdue];
    $incompleteIds = [$dueToday, $dueLater, $overdue];
    sort($dueTodayIds);
    sort($overdueIds);
    sort($incompleteIds);

    expect(Task::query()->dueToday()->pluck('id')->sort()->values()->all())->toBe($dueTodayIds)
        ->and(Task::query()->overdue()->pluck('id')->sort()->values()->all())->toBe($overdueIds)
        ->and(Task::query()->incomplete()->pluck('id')->sort()->values()->all())->toBe($incompleteIds)
        ->and(Task::query()->findOrFail($dueToday)->is_overdue)->toBeTrue()
        ->and(Task::query()->findOrFail($dueLater)->is_overdue)->toBeFalse()
        ->and(Task::query()->findOrFail($completed)->is_overdue)->toBeFalse()
        ->and(Task::query()->findOrFail($cancelled)->is_overdue)->toBeFalse();
});

it('validates task assignments against active users and tenant task stages', function () {
    Route::post('/_tests/task-request', fn (TaskRequest $request) => response()->json($request->validated()));

    $validResponse = $this->postJson('/_tests/task-request', [
        'title' => 'Follow-up',
        'status' => 'review',
        'priority' => Task::PRIORITY_HIGH,
        'assigned_to' => 1,
    ]);
    $validResponse->assertOk()->assertJsonPath('status', 'review')->assertJsonPath('assigned_to', 1);

    $this->postJson('/_tests/task-request', [
        'title' => 'Invalid assignment',
        'assigned_to' => 2,
    ])->assertUnprocessable()->assertJsonValidationErrors('assigned_to');

    $this->postJson('/_tests/task-request', [
        'title' => 'Invalid stage',
        'status' => 'not-a-tenant-stage',
    ])->assertUnprocessable()->assertJsonValidationErrors('status');
});

it('validates scheduling fields and participant records', function () {
    Route::post('/_tests/task-fields-request', fn (TaskRequest $request) => response()->json($request->validated()));

    $this->postJson('/_tests/task-fields-request', [
        'title' => 'Scheduled task',
        'schedule_from' => '2026-10-11T09:30',
        'schedule_to' => '2026-10-11T10:00',
        'location' => 'Conference room',
        'reminder_minutes' => 15,
        'participants' => [
            'users' => [1],
            'persons' => [1],
        ],
    ])->assertOk()
        ->assertJsonPath('location', 'Conference room')
        ->assertJsonPath('reminder_minutes', 15)
        ->assertJsonPath('participants.users.0', 1)
        ->assertJsonPath('participants.persons.0', 1);

    $this->postJson('/_tests/task-fields-request', [
        'title' => 'Invalid schedule',
        'schedule_from' => '2026-10-11T10:00',
        'schedule_to' => '2026-10-11T09:00',
        'reminder_minutes' => 7,
        'participants' => ['users' => [2], 'persons' => [999]],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors([
            'schedule_to',
            'reminder_minutes',
            'participants.users.0',
            'participants.persons.0',
        ]);
});

it('accepts configured tenant stages for kanban status changes and rejects unknown stages', function () {
    Route::post('/_tests/task-status-request', fn (UpdateStatusRequest $request) => response()->json($request->validated()));

    $this->postJson('/_tests/task-status-request', ['status' => 'review'])
        ->assertOk()
        ->assertJsonPath('status', 'review');

    $this->postJson('/_tests/task-status-request', ['status' => 'unrecognized'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('stores task relationships in the tenant task schema without a tenant_id column', function () {
    $taskId = insertTaskForBehavior([
        'assigned_to' => 1,
        'created_by' => 1,
        'entity_type' => 'leads',
        'entity_id' => 42,
        'due_date' => '2026-10-11',
    ]);
    $task = Task::query()->findOrFail($taskId);

    expect(Schema::hasColumn('tasks', 'tenant_id'))->toBeFalse()
        ->and($task->assigned_to)->toBe(1)
        ->and($task->entity_type)->toBe('leads')
        ->and($task->entity_id)->toBe(42)
        ->and($task->due_datetime->toDateTimeString())->toBe('2026-10-11 23:59:59');
});

it('persists task schedule data and user and contact participants', function () {
    $task = app(TaskRepository::class)->create([
        'title' => 'Scheduled task',
        'status' => Task::STATUS_PENDING,
        'priority' => Task::PRIORITY_MEDIUM,
        'schedule_from' => '2026-10-11 09:30:00',
        'schedule_to' => '2026-10-11 10:00:00',
        'location' => 'Conference room',
        'reminder_minutes' => 15,
        'participants' => ['users' => [1], 'persons' => [1]],
    ]);
    $taskId = (int) $task->id;

    expect(Schema::hasColumn('tasks', 'schedule_from'))->toBeTrue()
        ->and(Task::query()->findOrFail($taskId)->schedule_from->toDateTimeString())->toBe('2026-10-11 09:30:00')
        ->and(Task::query()->findOrFail($taskId)->schedule_to->toDateTimeString())->toBe('2026-10-11 10:00:00')
        ->and(Task::query()->findOrFail($taskId)->location)->toBe('Conference room')
        ->and(Task::query()->findOrFail($taskId)->reminder_minutes)->toBe(15)
        ->and(DB::table('task_participants')->where('task_id', $taskId)->count())->toBe(2);

    app(TaskRepository::class)->update([
        'participants' => ['users' => [], 'persons' => [1]],
    ], $taskId);

    expect(DB::table('task_participants')->where('task_id', $taskId)->count())->toBe(1)
        ->and(DB::table('task_participants')->where('task_id', $taskId)->whereNotNull('person_id')->exists())->toBeTrue();
});
