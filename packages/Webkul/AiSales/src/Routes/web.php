<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Webkul\AiSales\Http\Controllers\AiSalesController;
use Webkul\AiSales\Http\Middleware\AuthenticateAiSales;

Route::middleware(['web', AuthenticateAiSales::class, 'throttle:60,1'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->prefix('api/ai-sales')
    ->name('ai_sales.')
    ->group(function () {
        Route::get('health', [AiSalesController::class, 'health'])->name('health');
        Route::post('leads', [AiSalesController::class, 'store'])->name('leads.store');
        Route::delete('leads/{id}', [AiSalesController::class, 'destroy'])->whereNumber('id')->name('leads.destroy');
    });
