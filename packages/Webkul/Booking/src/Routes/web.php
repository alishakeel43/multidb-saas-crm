<?php

use Illuminate\Support\Facades\Route;
use Webkul\Booking\Http\Controllers\BookingController;
use Webkul\Booking\Http\Controllers\BookingSettingsController;
use Webkul\Booking\Http\Controllers\PublicBookingController;

Route::middleware(['web'])->group(function () {
    Route::get('/book', [PublicBookingController::class, 'show'])->name('bookings.public.show');
    Route::post('/book', [PublicBookingController::class, 'store'])->name('bookings.public.store');
});

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::controller(BookingController::class)->prefix('bookings')->name('bookings.')->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('', 'store')->name('store');
            Route::post('{id}/confirm', 'confirm')->name('confirm');
            Route::post('{id}/cancel', 'cancel')->name('cancel');
            Route::post('{id}/reschedule', 'reschedule')->name('reschedule');
        });

        Route::controller(BookingSettingsController::class)
            ->prefix('settings/booking')
            ->name('settings.booking.')
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('services', 'storeService')->name('services.store');
                Route::post('availability', 'storeAvailability')->name('availability.store');
                Route::post('availability/defaults', 'seedDefaultAvailability')->name('availability.defaults');
            });
    });
