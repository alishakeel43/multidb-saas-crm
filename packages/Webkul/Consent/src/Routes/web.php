<?php

use Illuminate\Support\Facades\Route;
use Webkul\Consent\Http\Controllers\ConsentController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::controller(ConsentController::class)
            ->prefix('settings/consent')
            ->name('settings.consent.')
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('', 'store')->name('store');
            });
    });
