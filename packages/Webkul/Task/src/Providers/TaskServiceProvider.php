<?php

namespace Webkul\Task\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Task\Contracts\Task as TaskContract;
use Webkul\Task\Contracts\TaskStage as TaskStageContract;
use Webkul\Task\Models\Task;
use Webkul\Task\Models\TaskStage;

class TaskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bindIf(TaskContract::class, Task::class);
        $this->app->bindIf(TaskStageContract::class, TaskStage::class);

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'task');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'task');

        Event::listen('admin.dashboard.index.content.right.before', function ($view) {
            if (is_object($view) && method_exists($view, 'addTemplate')) {
                $view->addTemplate('task::dashboard');
            }
        });

        Event::listen('admin.leads.view.actions.after', function ($view) {
            if (is_object($view) && method_exists($view, 'addTemplate') && $view->getParam('lead')) {
                $view->addTemplate('task::components.entity-action');
            }
        });

        Event::listen('admin.leads.view.activities.before', function ($view) {
            if (is_object($view) && method_exists($view, 'addTemplate') && $view->getParam('lead')) {
                $view->addTemplate('task::components.related');
            }
        });

        Event::listen('admin.contact.persons.view.actions.after', function ($view) {
            if (is_object($view) && method_exists($view, 'addTemplate') && $view->getParam('person')) {
                $view->addTemplate('task::components.entity-action');
            }
        });

        Event::listen('admin.contact.persons.view.right.before', function ($view) {
            if (is_object($view) && method_exists($view, 'addTemplate') && $view->getParam('person')) {
                $view->addTemplate('task::components.related');
            }
        });
    }
}
