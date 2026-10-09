<?php

namespace Webkul\Task\Providers;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Task\Console\Commands\CheckTaskReminders;
use Webkul\Task\Contracts\Task as TaskContract;
use Webkul\Task\Contracts\TaskStage as TaskStageContract;
use Webkul\Task\Models\Task;
use Webkul\Task\Models\TaskStage;

class TaskServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bindIf(TaskContract::class, Task::class);
        $this->app->bindIf(TaskStageContract::class, TaskStage::class);

        // Numeric config entries must be appended to preserve index arrays
        foreach (['acl' => 'acl', 'menu' => 'menu.admin'] as $file => $key) {
            if (! $this->app->configurationIsCached()) {
                config([$key => array_merge(config($key, []), require __DIR__.'/../Config/'.$file.'.php')]);
            }
        }

        $this->app->register(ModuleServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'task');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'task');

        Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'task');

        $this->commands([
            CheckTaskReminders::class,
        ]);

        // Register Breadcrumbs
        $this->registerBreadcrumbs();

        // Register View Render Event Listeners for UI Integrations
        $this->registerViewRenderEvents();

        // Concord proxy registration fallback
        $this->app->booted(function () {
            $concord = app('concord');

            if (! $concord->model(TaskContract::class)) {
                $concord->registerModel(TaskContract::class, Task::class);
            }

            if (! $concord->model(TaskStageContract::class)) {
                $concord->registerModel(TaskStageContract::class, TaskStage::class);
            }
        });
    }

    /**
     * Register dynamic breadcrumbs for tasks pages.
     */
    protected function registerBreadcrumbs(): void
    {
        if (! class_exists(Breadcrumbs::class)) {
            return;
        }

        if (! Breadcrumbs::exists('tasks')) {
            Breadcrumbs::for('tasks', function (BreadcrumbTrail $trail) {
                $trail->parent('dashboard');
                $trail->push(trans('admin::app.tasks.index.title'), route('admin.tasks.index'));
            });
        }

        if (! Breadcrumbs::exists('tasks.create')) {
            Breadcrumbs::for('tasks.create', function (BreadcrumbTrail $trail) {
                $trail->parent('tasks');
                $trail->push(trans('admin::app.tasks.create.title'), route('admin.tasks.create'));
            });
        }

        if (! Breadcrumbs::exists('tasks.edit')) {
            Breadcrumbs::for('tasks.edit', function (BreadcrumbTrail $trail, $task) {
                $trail->parent('tasks');
                $trail->push(trans('admin::app.tasks.edit.title'), route('admin.tasks.edit', is_object($task) ? $task->id : $task));
            });
        }

        if (! Breadcrumbs::exists('tasks.view')) {
            Breadcrumbs::for('tasks.view', function (BreadcrumbTrail $trail, $task) {
                $trail->parent('tasks');
                $title = is_object($task) ? $task->title : trans('admin::app.tasks.view.title');
                $id = is_object($task) ? $task->id : $task;
                $trail->push($title, route('admin.tasks.view', $id));
            });
        }
    }

    /**
     * Inject task integrations into CRM Dashboard and Entity Views via ViewRenderEventManager.
     */
    protected function registerViewRenderEvents(): void
    {
        // Inject Dashboard widget
        Event::listen('admin.dashboard.index.content.right.widgets', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('admin::dashboard.index.tasks');
        });

        // Inject Quick Task action button in Lead view
        Event::listen('admin.leads.view.actions.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('task::components.entity-action');
        });

        // Inject Quick Task action button in Person view
        Event::listen('admin.contact.persons.view.actions.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('task::components.entity-action');
        });

        // Inject Quick Task action button in Organization view
        Event::listen('admin.contact.organizations.view.actions.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('task::components.entity-action');
        });
    }
}
