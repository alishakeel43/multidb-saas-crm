<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\Booking\Mail\BookingReminderMail;
use Webkul\Booking\Models\Booking;
use Webkul\Booking\Models\BookingService;
use Webkul\Booking\Services\BookingReminderService;
use Webkul\Booking\States\Confirmed;
use Webkul\Booking\States\Pending;

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
        'mail.default' => 'array',
    ]);

    DB::purge('sqlite');
    DB::reconnect('sqlite');
    Mail::fake();

    Schema::create('booking_services', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->unsignedInteger('duration_minutes')->default(30);
        $table->unsignedInteger('buffer_minutes')->default(0);
        $table->boolean('is_active')->default(true);
        $table->text('description')->nullable();
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
        $table->timestamp('reminder_sent_at')->nullable();
        $table->timestamps();
    });
});

it('sends a reminder for bookings starting within the window', function () {
    $service = BookingService::query()->create([
        'name' => 'Discovery call',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
        'is_active' => true,
    ]);

    $starts = Carbon::now()->addMinutes(30);

    $booking = Booking::query()->create([
        'booking_service_id' => $service->id,
        'user_id' => null,
        'guest_name' => 'Ada',
        'guest_email' => 'ada@example.test',
        'starts_at' => $starts,
        'ends_at' => (clone $starts)->addMinutes(30),
        'status' => Pending::class,
    ]);

    $sent = app(BookingReminderService::class)->sendDueReminders(Carbon::now());

    expect($sent)->toBe(1)
        ->and($booking->fresh()->reminder_sent_at)->not->toBeNull();

    Mail::assertSent(BookingReminderMail::class, function (BookingReminderMail $mail) {
        return $mail->hasTo('ada@example.test');
    });
});

it('skips bookings outside the reminder window', function () {
    $service = BookingService::query()->create([
        'name' => 'Later call',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
        'is_active' => true,
    ]);

    $starts = Carbon::now()->addHours(5);

    Booking::query()->create([
        'booking_service_id' => $service->id,
        'guest_email' => 'later@example.test',
        'starts_at' => $starts,
        'ends_at' => (clone $starts)->addMinutes(30),
        'status' => Confirmed::class,
    ]);

    $sent = app(BookingReminderService::class)->sendDueReminders(Carbon::now());

    expect($sent)->toBe(0);
    Mail::assertNothingSent();
});
