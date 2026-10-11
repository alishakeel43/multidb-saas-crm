<?php

use Illuminate\Support\Facades\Route;
use Webkul\LeadScoring\Http\Controllers\LeadScoringRuleController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::controller(LeadScoringRuleController::class)
            ->prefix('settings/lead-scoring')
            ->name('settings.lead-scoring.')
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('', 'store')->name('store');
                Route::delete('{id}', 'destroy')->name('destroy');
            });
    });
