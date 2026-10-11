<?php

use Illuminate\Support\Facades\Route;
use Webkul\Assignment\Http\Controllers\AssignmentRuleController;
use Webkul\Assignment\Http\Controllers\LeadDuplicateController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::controller(AssignmentRuleController::class)
            ->prefix('settings/assignment')
            ->name('settings.assignment.')
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('', 'store')->name('store');
                Route::put('{id}', 'update')->name('update');
                Route::delete('{id}', 'destroy')->name('destroy');
            });

        Route::get('leads/duplicates/check', [LeadDuplicateController::class, 'check'])
            ->name('leads.duplicates.check');
    });
