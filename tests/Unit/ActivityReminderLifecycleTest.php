<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Multitenancy\Tasks\SwitchTenantStorageTask;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Tests\TestCase;
use Webkul\Activity\Contracts\SmsSender;
use Webkul\Activity\Jobs\SendActivityReminder;
use Webkul\Activity\Models\Activity;
use Webkul\Activity\Models\ActivityReminder;
use Webkul\Activity\Notifications\ActivityDue;
use Webkul\Activity\Services\ReminderService;
use Webkul\User\Models\User;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'tenant',
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [
            SwitchTenantStorageTask::class,
            SwitchTenantDatabaseTask::class,
        ],
        'cache.default' => 'array',
        'activity-reminders.minutes' => 15,
        'activity-reminders.email' => false,
        'activity-reminders.sms.enabled' => false,
        'activity-reminders.sms.driver' => 'null',
        'activity-reminders.sms.recipients' => [],
    ]);
    app()->forgetInstance(TasksCollection::class);
    app()->forgetInstance(ReminderService::class);
    DB::purge('landlord');
    DB::purge('tenant');
    Notification::fake();
    Queue::fake();

    $migration = require database_path('migrations/landlord/2026_10_09_000000_create_saas_landlord_tables.php');
    $migration->up();

    $this->plan = Plan::query()->create([
        'slug' => 'starter',
        'name' => 'Starter',
        'is_active' => true,
    ]);
    $this->tenantDatabasePaths = [];
});

afterEach(function () {
    Tenant::forgetCurrent();
    DB::purge('landlord');
    DB::purge('tenant');
    Carbon::setTestNow();

    foreach ($this->tenantDatabasePaths as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

function createActivityReminderSchema(): void
{
    Schema::connection('tenant')->create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
        $table->string('password')->nullable();
        $table->boolean('status')->default(true);
        $table->timestamps();
    });

    Schema::connection('tenant')->create('activities', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->string('type')->nullable();
        $table->string('location')->nullable();
        $table->text('comment')->nullable();
        $table->text('additional')->nullable();
        $table->dateTime('schedule_from')->nullable();
        $table->dateTime('schedule_to')->nullable();
        $table->boolean('is_done')->default(false);
        $table->unsignedInteger('user_id')->nullable();
        $table->timestamps();
    });

    $migration = require base_path('packages/Webkul/Activity/src/Database/Migrations/2026_10_05_100000_create_activity_reminders.php');
    $migration->up();
}

function createReminderActivity(array $overrides = []): int
{
    return (int) DB::connection('tenant')->table('activities')->insertGetId([
        'title' => 'Review proposal',
        'type' => 'task',
        'schedule_from' => '2026-10-10 10:00:00',
        'is_done' => false,
        'user_id' => 1,
        'reminder_minutes' => null,
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);
}

function createReminderTenant(string $name, string $slug, string $database): Tenant
{
    return Tenant::query()->create([
        'name' => $name,
        'slug' => $slug,
        'database' => $database,
        'plan_id' => test()->plan->id,
        'status' => 'active',
    ]);
}

it('schedules configured reminder channels at the activity-specific offset', function () {
    createActivityReminderSchema();
    DB::connection('tenant')->table('users')->insert([
        'id' => 1,
        'name' => 'Tenant User',
        'email' => 'user@example.test',
        'status' => true,
    ]);
    $activityId = createReminderActivity([
        'schedule_from' => '2026-10-10 10:00:00',
        'reminder_minutes' => 30,
    ]);
    config([
        'activity-reminders.email' => true,
        'activity-reminders.sms.enabled' => true,
        'activity-reminders.sms.driver' => 'twilio',
    ]);
    $activity = Activity::query()->findOrFail($activityId);

    app(ReminderService::class)->schedule($activity);

    $reminders = ActivityReminder::query()->orderBy('channel')->get();

    expect($reminders->pluck('channel')->all())->toBe(['email', 'in_app', 'sms'])
        ->and($reminders->pluck('remind_at')->map(fn ($date) => $date->toDateTimeString())->unique()->all())
        ->toBe(['2026-10-10 09:30:00'])
        ->and($reminders->pluck('status')->unique()->all())->toBe(['pending'])
        ->and($reminders->pluck('user_id')->unique()->all())->toBe([1])
        ->and($reminders->pluck('idempotency_key')->unique()->count())->toBe(3);
});

it('replaces pending and failed reminders while preserving sent delivery history', function () {
    createActivityReminderSchema();
    DB::connection('tenant')->table('users')->insert([
        'id' => 1,
        'name' => 'Tenant User',
        'email' => 'user@example.test',
        'status' => true,
    ]);
    $activityId = createReminderActivity();
    foreach (['pending', 'failed', 'sent'] as $index => $status) {
        ActivityReminder::query()->create([
            'activity_id' => $activityId,
            'user_id' => 1,
            'remind_at' => now(),
            'channel' => 'in_app',
            'status' => $status,
            'idempotency_key' => sprintf('00000000-0000-4000-8000-%012d', $index + 1),
        ]);
    }

    app(ReminderService::class)->schedule(Activity::query()->findOrFail($activityId));

    expect(ActivityReminder::query()->where('status', 'cancelled')->count())->toBe(2)
        ->and(ActivityReminder::query()->where('status', 'sent')->count())->toBe(1)
        ->and(ActivityReminder::query()->where('status', 'pending')->count())->toBe(1);
});

it('reschedules reminders after activity changes and cancels pending reminders on deletion', function () {
    createActivityReminderSchema();
    DB::connection('tenant')->table('users')->insert([
        'id' => 1,
        'name' => 'Tenant User',
        'email' => 'user@example.test',
        'status' => true,
    ]);

    $activity = Activity::query()->create([
        'title' => 'Review proposal',
        'type' => 'meeting',
        'schedule_from' => '2026-10-10 10:00:00',
        'is_done' => false,
        'user_id' => 1,
        'reminder_minutes' => 15,
    ]);
    $activity->schedule_from = '2026-10-10 11:00:00';
    $activity->save();

    expect(ActivityReminder::query()->where('status', 'cancelled')->count())->toBe(1)
        ->and(ActivityReminder::query()->where('status', 'pending')->count())->toBe(1)
        ->and(Carbon::parse(ActivityReminder::query()->where('status', 'pending')->value('remind_at'))->toDateTimeString())
        ->toBe('2026-10-10 10:45:00');

    $activity->delete();

    expect(ActivityReminder::query()->where('status', 'cancelled')->count())->toBe(2)
        ->and(ActivityReminder::query()->where('status', 'pending')->count())->toBe(0);
});

it('does not create reminders for completed, unscheduled, or unsupported activities', function (array $overrides) {
    createActivityReminderSchema();
    DB::connection('tenant')->table('users')->insert([
        'id' => 1,
        'name' => 'Tenant User',
        'email' => 'user@example.test',
        'status' => true,
    ]);
    $activityId = createReminderActivity($overrides);

    app(ReminderService::class)->schedule(Activity::query()->findOrFail($activityId));

    expect(ActivityReminder::query()->count())->toBe(0);
})->with([
    'completed activity' => [['is_done' => true]],
    'no scheduled time' => [['schedule_from' => null]],
    'unsupported activity type' => [['type' => 'lunch']],
]);

it('queues only due reminders belonging to active tenant databases', function () {
    $databaseA = tempnam(sys_get_temp_dir(), 'krayin-reminder-a-');
    $databaseB = tempnam(sys_get_temp_dir(), 'krayin-reminder-b-');
    $this->tenantDatabasePaths = [$databaseA, $databaseB];
    $tenantA = createReminderTenant('Reminder A', 'reminder-a', $databaseA);
    $tenantB = createReminderTenant('Reminder B', 'reminder-b', $databaseB);
    $inactive = createReminderTenant('Reminder inactive', 'reminder-inactive', tempnam(sys_get_temp_dir(), 'krayin-reminder-inactive-'));
    $this->tenantDatabasePaths[] = $inactive->database;
    $inactive->update(['status' => 'suspended']);

    foreach ([$tenantA, $tenantB, $inactive] as $tenant) {
        $tenant->makeCurrent();
        Schema::connection('tenant')->create('users', function (Blueprint $table) {
            $table->increments('id');
        });
        Schema::connection('tenant')->create('activities', function (Blueprint $table) {
            $table->increments('id');
        });
        Schema::connection('tenant')->create('activity_reminders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('activity_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->dateTime('remind_at');
            $table->string('channel');
            $table->string('status');
            $table->string('idempotency_key');
            $table->timestamps();
        });
        DB::connection('tenant')->table('activity_reminders')->insert([
            ['id' => 1, 'activity_id' => 1, 'user_id' => 1, 'remind_at' => '2026-10-09 12:00:00', 'channel' => 'in_app', 'status' => 'pending', 'idempotency_key' => "{$tenant->id}-due", 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'activity_id' => 1, 'user_id' => 1, 'remind_at' => '2026-10-11 12:00:00', 'channel' => 'in_app', 'status' => 'pending', 'idempotency_key' => "{$tenant->id}-future", 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
    Tenant::forgetCurrent();
    Carbon::setTestNow('2026-10-10 12:00:00');

    $this->artisan('activities:dispatch-reminders')->assertSuccessful();

    Queue::assertPushed(SendActivityReminder::class, 2);
    Queue::assertPushed(SendActivityReminder::class, fn (SendActivityReminder $job): bool => $job->tenantId === $tenantA->id && $job->reminderId === 1);
    Queue::assertPushed(SendActivityReminder::class, fn (SendActivityReminder $job): bool => $job->tenantId === $tenantB->id && $job->reminderId === 1);
});

it('sends an in-app reminder only in the tenant database identified by the job', function () {
    $database = tempnam(sys_get_temp_dir(), 'krayin-reminder-job-');
    $this->tenantDatabasePaths = [$database];
    $tenant = createReminderTenant('Reminder Job', 'reminder-job', $database);
    $tenant->makeCurrent();
    createActivityReminderSchema();
    DB::connection('tenant')->table('users')->insert([
        'id' => 1,
        'name' => 'Reminder Recipient',
        'email' => 'recipient@example.test',
        'password' => 'hash',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $activityId = createReminderActivity([
        'schedule_from' => '2026-10-10 10:00:00',
        'reminder_minutes' => 15,
    ]);
    $reminder = ActivityReminder::query()->create([
        'activity_id' => $activityId,
        'user_id' => 1,
        'remind_at' => '2026-10-10 09:45:00',
        'channel' => 'in_app',
        'status' => 'pending',
        'idempotency_key' => '00000000-0000-4000-8000-000000000101',
    ]);
    Tenant::forgetCurrent();
    Carbon::setTestNow('2026-10-10 09:50:00');

    (new SendActivityReminder($reminder->id, $tenant->id))->handle(app(SmsSender::class));

    $tenant->makeCurrent();
    expect(ActivityReminder::query()->findOrFail($reminder->id)->status)->toBe('sent');
    Notification::assertSentTo(
        User::query()->findOrFail(1),
        ActivityDue::class,
    );
});

it('marks uncertain SMS delivery failed and does not retry the external send automatically', function () {
    $database = tempnam(sys_get_temp_dir(), 'krayin-reminder-sms-');
    $this->tenantDatabasePaths = [$database];
    $tenant = createReminderTenant('Reminder SMS', 'reminder-sms', $database);
    $tenant->makeCurrent();
    createActivityReminderSchema();
    DB::connection('tenant')->table('users')->insert([
        'id' => 1,
        'name' => 'SMS Recipient',
        'email' => 'sms@example.test',
        'password' => 'hash',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $activityId = createReminderActivity([
        'schedule_from' => '2026-10-10 10:00:00',
        'reminder_minutes' => 15,
    ]);
    $reminder = ActivityReminder::query()->create([
        'activity_id' => $activityId,
        'user_id' => 1,
        'remind_at' => '2026-10-10 09:45:00',
        'channel' => 'sms',
        'status' => 'pending',
        'idempotency_key' => '00000000-0000-4000-8000-000000000102',
    ]);
    Tenant::forgetCurrent();
    Carbon::setTestNow('2026-10-10 09:50:00');
    config([
        'activity-reminders.sms.enabled' => true,
        'activity-reminders.sms.recipients' => [1 => '+15551234567'],
    ]);

    $sms = new class implements SmsSender
    {
        public int $calls = 0;

        public function send(string $to, string $message, string $idempotencyKey): void
        {
            $this->calls++;

            throw new RuntimeException('Provider timed out after accepting the request.');
        }
    };

    $job = new SendActivityReminder($reminder->id, $tenant->id);
    $job->handle($sms);
    $job->handle($sms);

    $tenant->makeCurrent();
    expect($sms->calls)->toBe(1)
        ->and(ActivityReminder::query()->findOrFail($reminder->id)->status)->toBe('failed')
        ->and(ActivityReminder::query()->findOrFail($reminder->id)->error)
        ->toBe('Delivery failed or its outcome is unknown.');
});
