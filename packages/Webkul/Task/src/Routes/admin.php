<?php

use Illuminate\Support\Facades\Route;
use Webkul\Task\Http\Controllers\TaskController;

Route::group(['middleware' => ['web', 'admin_locale', 'user'], 'prefix' => config('app.admin_path')], function () {
    Route::controller(TaskController::class)->prefix('tasks')->group(function () {
        Route::get('', 'index')->name('admin.tasks.index');

        Route::get('create', 'create')->name('admin.tasks.create');

        Route::post('create', 'store')->name('admin.tasks.store');

        Route::get('search-entities', 'searchEntities')->name('admin.tasks.search_entities');

        Route::get('view/{id}', 'view')->name('admin.tasks.view');

        Route::get('edit/{id}', 'edit')->name('admin.tasks.edit');

        Route::put('edit/{id}', 'update')->name('admin.tasks.update');

        Route::put('status/{id}', 'updateStatus')->name('admin.tasks.status');

        Route::delete('{id}', 'destroy')->name('admin.tasks.delete');

        Route::post('mass-update', 'massUpdate')->name('admin.tasks.mass_update');

        Route::post('mass-destroy', 'massDestroy')->name('admin.tasks.mass_delete');

        Route::get('entity/{entity_type}/{entity_id}', 'entityTasks')->name('admin.tasks.entity');

        Route::get('dashboard-stats', 'dashboardStats')->name('admin.tasks.dashboard_stats');

        Route::get('kanban-data', 'kanbanData')->name('admin.tasks.kanban_data');

        Route::get('get', 'get')->name('admin.tasks.get');

        Route::put('stage/edit/{id}', 'updateStage')->name('admin.tasks.stage.update');

        Route::put('quick-edit/{id}', 'quickUpdate')->name('admin.tasks.quick_update');

        Route::get('kanban/look-up', 'kanbanLookup')->name('admin.tasks.kanban.look_up');

        Route::get('stages', 'getStages')->name('admin.tasks.stages');

        Route::post('stages', 'storeStage')->name('admin.tasks.stages.store');

        Route::put('stages/{code}', 'updateStageDefinition')->name('admin.tasks.stages.update');

        Route::put('stages/{code}/reassign', 'reassignTasks')->name('admin.tasks.stages.reassign');

        Route::delete('stages/{code}', 'destroyStage')->name('admin.tasks.stages.delete');
    });
});
