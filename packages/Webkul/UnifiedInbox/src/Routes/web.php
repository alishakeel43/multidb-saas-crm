<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Webkul\UnifiedInbox\Http\Controllers\ChannelController;
use Webkul\UnifiedInbox\Http\Controllers\InboxController;
use Webkul\UnifiedInbox\Http\Controllers\MetaOAuthController;
use Webkul\UnifiedInbox\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Public Meta Webhook + OAuth Routes
|--------------------------------------------------------------------------
*/
// One common Meta webhook for WhatsApp, Messenger and Instagram (register this URL once in Meta).
// One common OAuth redirect for every tenant/channel (register this URL once in Meta Valid OAuth Redirect URIs).
Route::middleware(['web'])
    ->withoutMiddleware([
        ValidateCsrfToken::class,
    ])
    ->prefix('api/meta')
    ->name('unified_inbox.meta_webhook.')
    ->group(function () {
        Route::get('webhook', [WebhookController::class, 'verify'])->name('verify');
        Route::post('webhook', [WebhookController::class, 'handle'])->name('handle');
        Route::get('oauth/callback', [MetaOAuthController::class, 'callback'])->name('oauth.callback');
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
        Route::get('unified-inbox/compose/templates', [InboxController::class, 'composeTemplates'])->name('compose.templates');
        Route::get('unified-inbox/compose/contacts', [InboxController::class, 'composeContacts'])->name('compose.contacts');
        Route::post('unified-inbox/compose', [InboxController::class, 'compose'])->name('compose.store');
        Route::get('unified-inbox/{id}', [InboxController::class, 'show'])->whereNumber('id')->name('show');

        // Saved quick replies (composer '/' shortcut)
        Route::get('unified-inbox/quick-replies', [InboxController::class, 'quickRepliesIndex'])->name('quick_replies.index');
        Route::post('unified-inbox/quick-replies', [InboxController::class, 'quickRepliesStore'])->name('quick_replies.store');
        Route::put('unified-inbox/quick-replies/{replyId}', [InboxController::class, 'quickRepliesUpdate'])->whereNumber('replyId')->name('quick_replies.update');
        Route::delete('unified-inbox/quick-replies/{replyId}', [InboxController::class, 'quickRepliesDestroy'])->whereNumber('replyId')->name('quick_replies.destroy');

        Route::get('unified-inbox/crm-search', [InboxController::class, 'crmSearch'])->name('crm_search');

        // Conversation actions
        Route::prefix('unified-inbox/conversations/{id}')->name('conversations.')->group(function () {
            Route::get('messages', [InboxController::class, 'messages'])->name('messages');
            Route::post('send', [InboxController::class, 'send'])->name('send');
            Route::post('status', [InboxController::class, 'updateStatus'])->name('status');
            Route::post('assign', [InboxController::class, 'assign'])->name('assign');
            Route::post('link-lead', [InboxController::class, 'linkLead'])->name('link_lead');
            Route::post('link-person', [InboxController::class, 'linkPerson'])->name('link_person');
            Route::post('link-record', [InboxController::class, 'linkRecord'])->name('link_record');
            Route::post('organization', [InboxController::class, 'createOrganization'])->name('create_organization');
            Route::post('notes', [InboxController::class, 'storeNote'])->name('notes');
            Route::post('flags', [InboxController::class, 'updateFlags'])->name('flags');
            Route::post('labels', [InboxController::class, 'updateLabels'])->name('labels');
            Route::post('attachments', [InboxController::class, 'sendAttachment'])->name('attachments.store');
            Route::get('messages/{messageId}/attachments/{index}', [InboxController::class, 'attachment'])->whereNumber(['messageId', 'index'])->name('attachments.show');
            Route::get('ai-suggestions', [InboxController::class, 'aiSuggestions'])->middleware('throttle:20,1')->name('ai_suggestions');
        });

        // Channel connections & Meta Embedded Signup
        Route::prefix('settings/channels')->name('channels.')->group(function () {
            Route::get('', [ChannelController::class, 'index'])->name('index');
            Route::get('callback', [ChannelController::class, 'callback'])->name('meta.callback');
            Route::post('accept-token', [ChannelController::class, 'acceptUserToken'])->name('accept_token');
            Route::post('complete-selection', [ChannelController::class, 'completeSelection'])->name('complete_selection');
            Route::post('cancel-selection', [ChannelController::class, 'cancelSelection'])->name('cancel_selection');
            Route::post('manual', [ChannelController::class, 'manualConnect'])->name('manual');
            Route::post('{id}/disconnect', [ChannelController::class, 'disconnect'])->name('disconnect');
        });
    });
