<?php

namespace Webkul\Task\DataGrids;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Traits\ProvideDropdownOptions;
use Webkul\DataGrid\DataGrid;
use Webkul\Task\Models\Task;
use Webkul\User\Repositories\UserRepository;

class TaskDataGrid extends DataGrid
{
    use ProvideDropdownOptions;

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('tasks')
            ->distinct()
            ->select(
                'tasks.*',
                'assigned_user.name as assigned_name',
                'creator.name as creator_name',
                'leads.title as lead_title',
                'persons.name as person_name',
                'organizations.name as organization_name',
                'quotes.subject as quote_subject'
            )
            ->leftJoin('users as assigned_user', 'tasks.assigned_to', '=', 'assigned_user.id')
            ->leftJoin('users as creator', 'tasks.created_by', '=', 'creator.id')
            ->leftJoin('leads', function ($join) {
                $join->on('tasks.entity_id', '=', 'leads.id')
                    ->where('tasks.entity_type', '=', 'leads');
            })
            ->leftJoin('persons', function ($join) {
                $join->on('tasks.entity_id', '=', 'persons.id')
                    ->where('tasks.entity_type', '=', 'persons');
            })
            ->leftJoin('organizations', function ($join) {
                $join->on('tasks.entity_id', '=', 'organizations.id')
                    ->where('tasks.entity_type', '=', 'organizations');
            })
            ->leftJoin('quotes', function ($join) {
                $join->on('tasks.entity_id', '=', 'quotes.id')
                    ->where('tasks.entity_type', '=', 'quotes');
            });

        // Apply view permissions
        if ($userIds = bouncer()->getAuthorizedUserIds()) {
            $queryBuilder->where(function ($query) use ($userIds) {
                $query->whereIn('tasks.assigned_to', $userIds)
                    ->orWhereIn('tasks.created_by', $userIds);
            });
        }

        // Preset quick views
        $view = request()->get('view');
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->toTimeString();

        if ($view === 'my_tasks') {
            $queryBuilder->where('tasks.assigned_to', auth()->id());
        } elseif ($view === 'today') {
            $queryBuilder->whereNotIn('tasks.status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])
                ->whereDate('tasks.due_date', $today);
        } elseif ($view === 'upcoming') {
            $queryBuilder->whereNotIn('tasks.status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])
                ->whereDate('tasks.due_date', '>', $today);
        } elseif ($view === 'overdue') {
            $queryBuilder->whereNotIn('tasks.status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])
                ->whereNotNull('tasks.due_date')
                ->where(function ($q) use ($today, $currentTime) {
                    $q->where('tasks.due_date', '<', $today)
                        ->orWhere(function ($sub) use ($today, $currentTime) {
                            $sub->where('tasks.due_date', '=', $today)
                                ->whereNotNull('tasks.due_time')
                                ->where('tasks.due_time', '<', $currentTime);
                        });
                });
        } elseif ($view === 'completed') {
            $queryBuilder->where('tasks.status', Task::STATUS_COMPLETED);
        } elseif ($view === 'unassigned') {
            $queryBuilder->whereNull('tasks.assigned_to');
        }

        $this->addFilter('id', 'tasks.id');
        $this->addFilter('title', 'tasks.title');
        $this->addFilter('status', 'tasks.status');
        $this->addFilter('priority', 'tasks.priority');
        $this->addFilter('due_date', 'tasks.due_date');
        $this->addFilter('assigned_to', 'tasks.assigned_to');
        $this->addFilter('created_by', 'tasks.created_by');
        $this->addFilter('created_at', 'tasks.created_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.tasks.datagrid.id'),
            'type' => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'title',
            'label' => trans('admin::app.tasks.datagrid.title'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $isCompleted = $row->status === Task::STATUS_COMPLETED;
                $viewUrl = route('admin.tasks.view', $row->id);
                $titleHtml = htmlspecialchars($row->title, ENT_QUOTES, 'UTF-8');
                $class = $isCompleted ? 'line-through text-gray-500 dark:text-gray-400' : 'font-medium text-gray-800 dark:text-gray-100 hover:text-brandColor';

                return '<a href="'.$viewUrl.'" class="'.$class.'">'.$titleHtml.'</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('admin::app.tasks.datagrid.status'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'dropdown_options' => [
                ['value' => Task::STATUS_PENDING, 'label' => trans('admin::app.tasks.status.pending')],
                ['value' => Task::STATUS_IN_PROGRESS, 'label' => trans('admin::app.tasks.status.in_progress')],
                ['value' => Task::STATUS_COMPLETED, 'label' => trans('admin::app.tasks.status.completed')],
                ['value' => Task::STATUS_CANCELLED, 'label' => trans('admin::app.tasks.status.cancelled')],
            ],
            'closure' => function ($row) {
                return match ($row->status) {
                    Task::STATUS_PENDING => '<span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">'.trans('admin::app.tasks.status.pending').'</span>',
                    Task::STATUS_IN_PROGRESS => '<span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">'.trans('admin::app.tasks.status.in_progress').'</span>',
                    Task::STATUS_COMPLETED => '<span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/50 dark:text-green-300">'.trans('admin::app.tasks.status.completed').'</span>',
                    Task::STATUS_CANCELLED => '<span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">'.trans('admin::app.tasks.status.cancelled').'</span>',
                    default => htmlspecialchars($row->status, ENT_QUOTES, 'UTF-8'),
                };
            },
        ]);

        $this->addColumn([
            'index' => 'priority',
            'label' => trans('admin::app.tasks.datagrid.priority'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'dropdown_options' => [
                ['value' => Task::PRIORITY_LOW, 'label' => trans('admin::app.tasks.priority.low')],
                ['value' => Task::PRIORITY_MEDIUM, 'label' => trans('admin::app.tasks.priority.medium')],
                ['value' => Task::PRIORITY_HIGH, 'label' => trans('admin::app.tasks.priority.high')],
                ['value' => Task::PRIORITY_URGENT, 'label' => trans('admin::app.tasks.priority.urgent')],
            ],
            'closure' => function ($row) {
                return match ($row->priority) {
                    Task::PRIORITY_URGENT => '<span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">'.trans('admin::app.tasks.priority.urgent').'</span>',
                    Task::PRIORITY_HIGH => '<span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300">'.trans('admin::app.tasks.priority.high').'</span>',
                    Task::PRIORITY_MEDIUM => '<span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">'.trans('admin::app.tasks.priority.medium').'</span>',
                    Task::PRIORITY_LOW => '<span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-normal bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">'.trans('admin::app.tasks.priority.low').'</span>',
                    default => htmlspecialchars($row->priority, ENT_QUOTES, 'UTF-8'),
                };
            },
        ]);

        $this->addColumn([
            'index' => 'entity_title',
            'label' => trans('admin::app.tasks.datagrid.related_entity'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                if (! $row->entity_type || ! $row->entity_id) {
                    return '<span class="text-gray-400">—</span>';
                }

                $title = match ($row->entity_type) {
                    'leads' => $row->lead_title ?? ('#'.$row->entity_id),
                    'persons' => $row->person_name ?? ('#'.$row->entity_id),
                    'organizations' => $row->organization_name ?? ('#'.$row->entity_id),
                    'quotes' => $row->quote_subject ?? ('#'.$row->entity_id),
                    default => '#'.$row->entity_id,
                };

                $url = match ($row->entity_type) {
                    'leads' => route('admin.leads.view', $row->entity_id),
                    'persons' => route('admin.contacts.persons.view', $row->entity_id),
                    'organizations' => route('admin.contacts.organizations.edit', $row->entity_id),
                    'quotes' => route('admin.quotes.edit', $row->entity_id),
                    default => null,
                };

                $badgeLabel = match ($row->entity_type) {
                    'leads' => 'Lead',
                    'persons' => 'Person',
                    'organizations' => 'Org',
                    'quotes' => 'Quote',
                    default => ucfirst($row->entity_type),
                };

                $titleEscaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                $badgeHtml = '<span class="mr-1 inline-block rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-400">'.$badgeLabel.'</span>';

                if ($url) {
                    return $badgeHtml.'<a href="'.$url.'" class="text-brandColor hover:underline">'.$titleEscaped.'</a>';
                }

                return $badgeHtml.$titleEscaped;
            },
        ]);

        $this->addColumn([
            'index' => 'due_date',
            'label' => trans('admin::app.tasks.datagrid.due_date'),
            'type' => 'date',
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'date_range',
            'sortable' => true,
            'closure' => function ($row) {
                if (! $row->due_date) {
                    return '<span class="text-gray-400">—</span>';
                }

                $dueDate = Carbon::parse($row->due_date);
                $formattedDate = core()->formatDate($dueDate);
                $dueTimeStr = $row->due_time ? ' '.substr($row->due_time, 0, 5) : '';

                // Overdue check
                $now = Carbon::now();
                $isOverdue = false;

                if (! in_array($row->status, [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED], true)) {
                    if ($row->due_time) {
                        $dueDateTime = Carbon::parse($dueDate->format('Y-m-d').' '.$row->due_time);
                        $isOverdue = $now->greaterThan($dueDateTime);
                    } else {
                        $isOverdue = $now->startOfDay()->greaterThan($dueDate->startOfDay());
                    }
                }

                if ($isOverdue) {
                    return '<span class="inline-flex items-center gap-1 font-semibold text-red-600 dark:text-red-400"><i class="icon-warning text-sm"></i> '.$formattedDate.$dueTimeStr.' ('.trans('admin::app.tasks.overdue').')</span>';
                }

                return '<span class="text-gray-700 dark:text-gray-300">'.$formattedDate.$dueTimeStr.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'assigned_to',
            'label' => trans('admin::app.tasks.datagrid.assigned_to'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => UserRepository::class,
                'column' => [
                    'label' => 'name',
                    'value' => 'id',
                ],
            ],
            'sortable' => true,
            'closure' => function ($row) {
                if (! $row->assigned_name) {
                    return '<span class="italic text-gray-400">'.trans('admin::app.tasks.unassigned').'</span>';
                }

                return '<span class="inline-flex items-center gap-1.5"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-brandColor/20 text-xs font-bold text-brandColor">'.strtoupper(substr($row->assigned_name, 0, 1)).'</span>'.htmlspecialchars($row->assigned_name, ENT_QUOTES, 'UTF-8').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.tasks.datagrid.created_at'),
            'type' => 'date',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => core()->formatDate($row->created_at),
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'index' => 'view',
            'icon' => 'icon-eye',
            'title' => trans('admin::app.tasks.datagrid.view'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.tasks.view', $row->id),
        ]);

        if (bouncer()->hasPermission('tasks.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('admin::app.tasks.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.tasks.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('tasks.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('admin::app.tasks.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.tasks.delete', $row->id),
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('tasks.delete')) {
            $this->addMassAction([
                'icon' => 'icon-delete',
                'title' => trans('admin::app.tasks.datagrid.mass-delete'),
                'method' => 'POST',
                'url' => route('admin.tasks.mass_delete'),
            ]);
        }

        if (bouncer()->hasPermission('tasks.edit')) {
            $this->addMassAction([
                'title' => trans('admin::app.tasks.datagrid.mass-update-status'),
                'url' => route('admin.tasks.mass_update'),
                'method' => 'POST',
                'options' => [
                    ['label' => trans('admin::app.tasks.status.pending'), 'value' => Task::STATUS_PENDING],
                    ['label' => trans('admin::app.tasks.status.in_progress'), 'value' => Task::STATUS_IN_PROGRESS],
                    ['label' => trans('admin::app.tasks.status.completed'), 'value' => Task::STATUS_COMPLETED],
                    ['label' => trans('admin::app.tasks.status.cancelled'), 'value' => Task::STATUS_CANCELLED],
                ],
            ]);
        }
    }
}
