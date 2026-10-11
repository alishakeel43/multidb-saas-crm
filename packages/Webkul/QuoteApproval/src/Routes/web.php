<?php

use Illuminate\Support\Facades\Route;
use Webkul\QuoteApproval\Http\Controllers\QuoteApprovalController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::controller(QuoteApprovalController::class)
            ->prefix('quotes/approvals')
            ->name('quotes.approvals.')
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('{quoteId}/submit', 'submit')->name('submit');
                Route::post('{quoteId}/approve', 'approve')->name('approve');
                Route::post('{quoteId}/reject', 'reject')->name('reject');
            });
    });
