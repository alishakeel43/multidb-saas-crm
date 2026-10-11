<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Webkul\UnifiedInbox\Http\Controllers\ChannelController;
use Webkul\UnifiedInbox\Http\Controllers\InboxController;
use Webkul\UnifiedInbox\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Public Meta Webhook Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web'])
    ->withoutMiddleware([
        ValidateCsrfToken::class,
    ])
    ->prefix('unified-inbox/meta')
    ->name('unified_inbox.webhook.')
    ->group(function () {
        Route::get('webhook', [WebhookController::class, 'verify'])->name('verify');
        Route::post('webhook', [WebhookController::class, 'handle'])->name('handle');
    });

/*
|--------------------------------------------------------------------------
| Admin Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.unified_inbox.')
    ->group(function () {
        // Main Unified Inbox view
        Route::get('unified-inbox', [InboxController::class, 'index'])->name('index');

        // Conversation actions
        Route::prefix('unified-inbox/conversations/{id}')->name('conversations.')->group(function () {
            Route::get('messages', [InboxController::class, 'messages'])->name('messages');
            Route::post('send', [InboxController::class, 'send'])->name('send');
            Route::post('status', [InboxController::class, 'updateStatus'])->name('status');
            Route::post('assign', [InboxController::class, 'assign'])->name('assign');
            Route::post('link-lead', [InboxController::class, 'linkLead'])->name('link_lead');
            Route::post('link-person', [InboxController::class, 'linkPerson'])->name('link_person');
        });

        // Channel connections & Meta Embedded Signup
        Route::prefix('settings/channels')->name('channels.')->group(function () {
            Route::get('', [ChannelController::class, 'index'])->name('index');
            Route::get('callback', [ChannelController::class, 'callback'])->name('meta.callback');
            Route::post('manual', [ChannelController::class, 'manualConnect'])->name('manual');
            Route::post('{id}/disconnect', [ChannelController::class, 'disconnect'])->name('disconnect');
        });
    });
