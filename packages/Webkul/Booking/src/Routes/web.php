<?php

use Illuminate\Support\Facades\Route;
use Webkul\Booking\Http\Controllers\BookingController;
use Webkul\Booking\Http\Controllers\BookingSettingsController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::controller(BookingController::class)->prefix('bookings')->name('bookings.')->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('', 'store')->name('store');
            Route::post('{id}/confirm', 'confirm')->name('confirm');
            Route::post('{id}/cancel', 'cancel')->name('cancel');
        });

        Route::controller(BookingSettingsController::class)
            ->prefix('settings/booking')
            ->name('settings.booking.')
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('services', 'storeService')->name('services.store');
                Route::post('availability', 'storeAvailability')->name('availability.store');
            });
    });
