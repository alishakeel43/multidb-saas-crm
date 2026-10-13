<?php

use Illuminate\Support\Facades\Route;
use Webkul\MarketingAutomation\Http\Controllers\MarketingAutomationController;
use Webkul\MarketingAutomation\Http\Controllers\TrackingController;

Route::middleware(['web', 'throttle:120,1'])->group(function () {
    Route::get('marketing-automation/open/{token}', [TrackingController::class, 'open'])
        ->where('token', '[A-Za-z0-9]{40}')
        ->name('marketing_automation.track.open');

    Route::get('marketing-automation/click/{token}', [TrackingController::class, 'click'])
        ->where('token', '[A-Za-z0-9]{40}')
        ->name('marketing_automation.track.click');

    Route::get('marketing-automation/unsubscribe/{token}', [TrackingController::class, 'unsubscribeForm'])
        ->where('token', '[A-Za-z0-9]{40}')
        ->name('marketing_automation.track.unsubscribe');

    Route::post('marketing-automation/unsubscribe/{token}', [TrackingController::class, 'unsubscribe'])
        ->where('token', '[A-Za-z0-9]{40}')
        ->name('marketing_automation.track.unsubscribe.store');
});

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::controller(MarketingAutomationController::class)
            ->prefix('marketing-automation')
            ->name('marketing_automation.')
            ->group(function () {
                Route::get('', 'index')->name('index');

                Route::get('workflows', 'workflows')->name('workflows');
                Route::post('workflows', 'storeWorkflow')->name('workflows.store');
                Route::delete('workflows/{id}', 'destroyWorkflow')->name('workflows.destroy');

                Route::get('campaigns', 'campaigns')->name('campaigns');
                Route::post('campaigns', 'storeCampaign')->name('campaigns.store');
                Route::get('campaigns/{id}', 'showCampaign')->name('campaigns.show');
                Route::put('campaigns/{id}', 'updateCampaign')->name('campaigns.update');
                Route::delete('campaigns/{id}', 'destroyCampaign')->name('campaigns.destroy');

                Route::get('audiences', 'audiences')->name('audiences');
                Route::post('audiences', 'storeAudience')->name('audiences.store');
                Route::delete('audiences/{id}', 'destroyAudience')->name('audiences.destroy');

                Route::get('sequences', 'sequences')->name('sequences');
                Route::post('sequences', 'storeSequence')->name('sequences.store');
                Route::get('sequences/{id}', 'showSequence')->name('sequences.show');
                Route::post('sequences/{id}/steps', 'storeStep')->name('sequences.steps.store');
                Route::delete('sequences/{id}/steps/{stepId}', 'destroyStep')->name('sequences.steps.destroy');
                Route::post('sequences/{id}/enroll', 'enroll')->name('sequences.enroll');
                Route::post('sequences/{id}/status', 'updateSequenceStatus')->name('sequences.status');
                Route::post('sequences/{id}/enrollments/{enrollmentId}/status', 'updateEnrollmentStatus')->name('sequences.enrollments.status');

                Route::get('attributions', 'attributions')->name('attributions');

                Route::get('engagement', 'engagement')->name('engagement');
                Route::post('engagement', 'storeEngagementRule')->name('engagement.store');
                Route::delete('engagement/{id}', 'destroyEngagementRule')->name('engagement.destroy');

                Route::get('deliverability', 'deliverability')->name('deliverability');
                Route::post('deliverability/{id}/bounce', 'bounce')->name('deliverability.bounce');
            });
    });
