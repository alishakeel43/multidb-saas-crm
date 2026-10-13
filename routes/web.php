<?php

use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TenantPublicFileController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('stripe.webhook');

Route::get('/storage/{path}', TenantPublicFileController::class)
    ->where('path', '.*')
    ->name('tenant.public-storage');
