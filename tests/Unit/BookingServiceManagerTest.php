<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Activity\Contracts\Activity as ActivityContract;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Booking\Models\Booking;
use Webkul\Booking\Models\BookingAvailability;
use Webkul\Booking\Models\BookingService;
use Webkul\Booking\Repositories\BookingRepository;
use Webkul\Booking\Repositories\BookingServiceRepository;
use Webkul\Booking\Services\BookingServiceManager;
use Webkul\Booking\States\Cancelled;
use Webkul\Booking\States\Confirmed;

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

    Schema::create('booking_services', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->unsignedInteger('duration_minutes')->default(30);
        $table->unsignedInteger('buffer_minutes')->default(0);
        $table->boolean('is_active')->default(true);
        $table->text('description')->nullable();
        $table->timestamps();
    });

    Schema::create('booking_availabilities', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->unsignedTinyInteger('weekday');
        $table->time('starts_at');
        $table->time('ends_at');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('bookings', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('booking_service_id');
        $table->unsignedInteger('user_id')->nullable();
        $table->unsignedInteger('person_id')->nullable();
        $table->unsignedInteger('lead_id')->nullable();
        $table->unsignedInteger('activity_id')->nullable();
        $table->string('guest_name')->nullable();
        $table->string('guest_email')->nullable();
        $table->string('guest_phone')->nullable();
        $table->dateTime('starts_at');
        $table->dateTime('ends_at');
        $table->string('status');
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    $activity = new class implements ActivityContract
    {
        public int $id = 1;

        public function leads()
        {
            return new class
            {
                public function attach($id): void {}
            };
        }

        public function persons()
        {
            return new class
            {
                public function attach($id): void {}
            };
        }
    };

    $activityRepo = Mockery::mock(ActivityRepository::class);
    $activityRepo->shouldReceive('create')->andReturn($activity);

    app()->instance(ActivityRepository::class, $activityRepo);
});

function bookingManager(): BookingServiceManager
{
    return new BookingServiceManager(
        app(ActivityRepository::class),
        app(BookingRepository::class),
        app(BookingServiceRepository::class),
    );
}

it('uses soft Mon–Fri defaults when staff has no custom availability', function () {
    $manager = bookingManager();
    $mondayTen = Carbon::parse('next monday')->setTime(10, 0);

    expect($manager->isSlotAvailable(1, $mondayTen, (clone $mondayTen)->addMinutes(30)))->toBeTrue();

    $saturdayTen = Carbon::parse('next saturday')->setTime(10, 0);

    expect($manager->isSlotAvailable(1, $saturdayTen, (clone $saturdayTen)->addMinutes(30)))->toBeFalse();
});

it('requires staff and books inside default hours', function () {
    $service = BookingService::query()->create([
        'name' => 'Demo call',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
        'is_active' => true,
    ]);

    $manager = bookingManager();

    expect(fn () => $manager->book([
        'booking_service_id' => $service->id,
        'starts_at' => Carbon::parse('next monday')->setTime(10, 0)->toDateTimeString(),
        'auto_confirm' => true,
    ]))->toThrow(InvalidArgumentException::class);

    $booking = $manager->book([
        'booking_service_id' => $service->id,
        'user_id' => 7,
        'starts_at' => Carbon::parse('next monday')->setTime(10, 0)->toDateTimeString(),
        'guest_name' => 'Alex',
        'auto_confirm' => true,
    ]);

    expect($booking->user_id)->toBe(7)
        ->and($booking->status->equals(Confirmed::class))->toBeTrue()
        ->and($booking->guest_name)->toBe('Alex');
});

it('blocks overlapping appointments for the same staff member', function () {
    $service = BookingService::query()->create([
        'name' => 'Demo call',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
        'is_active' => true,
    ]);

    $start = Carbon::parse('next monday')->setTime(10, 0);
    $manager = bookingManager();

    $manager->book([
        'booking_service_id' => $service->id,
        'user_id' => 7,
        'starts_at' => $start->toDateTimeString(),
        'auto_confirm' => true,
    ]);

    expect(fn () => $manager->book([
        'booking_service_id' => $service->id,
        'user_id' => 7,
        'starts_at' => $start->copy()->addMinutes(15)->toDateTimeString(),
        'auto_confirm' => true,
    ]))->toThrow(InvalidArgumentException::class);

    $existing = Booking::query()->latest('id')->first();
    $existing->status = Cancelled::class;
    $existing->save();

    $again = $manager->book([
        'booking_service_id' => $service->id,
        'user_id' => 7,
        'starts_at' => $start->toDateTimeString(),
        'auto_confirm' => false,
    ]);

    expect($again->id)->toBeGreaterThan(0);
});

it('seeds default weekday availability rows', function () {
    $created = bookingManager()->seedDefaultWeek(3);

    expect($created)->toBe(5)
        ->and(BookingAvailability::query()->where('user_id', 3)->count())->toBe(5)
        ->and(bookingManager()->seedDefaultWeek(3))->toBe(0);
});
