<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Webkul\Telephony\Http\Controllers\CallLogController;
use Webkul\Telephony\Http\Controllers\SettingsController;
use Webkul\Telephony\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Public Twilio Webhook Routes
|--------------------------------------------------------------------------
| These routes must be reachable from the root domain without a tenant
| subdomain.  CSRF verification is disabled because Twilio signs requests
| with an HMAC-SHA1 signature that is validated inside WebhookController.
*/
Route::middleware(['web'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->prefix('telephony')
    ->name('telephony.')
    ->group(function () {
        Route::post('webhook', [WebhookController::class, 'handle'])->name('webhook.handle');
        Route::get('twiml', [WebhookController::class, 'handleTwiml'])->name('twiml');
        Route::post('recording', [WebhookController::class, 'recording'])->name('recording');
    });

/*
|--------------------------------------------------------------------------
| Admin Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.telephony.')
    ->group(function () {
        // Call Logs
        Route::controller(CallLogController::class)
            ->prefix('telephony/call-logs')
            ->name('call_logs.')
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('', 'store')->name('store');
                Route::delete('{id}', 'destroy')->name('delete');
                Route::get('{id}/recording', 'recordingProxy')->name('recording');
            });

        // Settings
        Route::controller(SettingsController::class)
            ->prefix('telephony/settings')
            ->name('settings.')
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('', 'update')->name('update');
            });
    });
