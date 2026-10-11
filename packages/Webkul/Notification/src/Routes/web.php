<?php

use Illuminate\Support\Facades\Route;
use Webkul\Notification\Http\Controllers\NotificationController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::controller(NotificationController::class)->prefix('notifications')->name('notifications.')->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('feed', 'feed')->name('feed');
            Route::post('{id}/read', 'markRead')->name('read');
            Route::post('read-all', 'markAllRead')->name('read-all');
        });
    });
