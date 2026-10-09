<?php

use Illuminate\Support\Facades\Route;
use Webkul\Activity\Http\Controllers\ReminderNotificationController;

Route::middleware(['web', 'admin_locale', 'user'])->prefix(trim(config('app.admin_path'), '/').'/reminder-notifications')
    ->name('admin.reminder_notifications.')->controller(ReminderNotificationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{id}/read', 'read')->whereUuid('id')->name('read');
    });
