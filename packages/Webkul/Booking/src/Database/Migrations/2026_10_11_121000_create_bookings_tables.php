<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_services', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->unsignedInteger('buffer_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'name'], 'booking_services_active_name_idx');
        });

        Schema::create('booking_availabilities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('weekday'); // 0=Sunday .. 6=Saturday
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // isSlotAvailable(): staff windows for a weekday
            $table->index(
                ['user_id', 'weekday', 'is_active'],
                'booking_availabilities_user_weekday_active_idx'
            );
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

            $table->foreign('booking_service_id')
                ->references('id')
                ->on('booking_services')
                ->cascadeOnDelete();

            // overlap checks + staff calendar: user + time range
            $table->index(
                ['user_id', 'starts_at', 'ends_at'],
                'bookings_user_starts_ends_idx'
            );

            // upcoming / status filters (SalesReportService, listings)
            $table->index(['status', 'starts_at'], 'bookings_status_starts_idx');

            // relation lookups from CRM records
            $table->index('person_id', 'bookings_person_id_idx');
            $table->index('lead_id', 'bookings_lead_id_idx');
            $table->unique('activity_id', 'bookings_activity_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('booking_availabilities');
        Schema::dropIfExists('booking_services');
    }
};
