<?php

namespace Webkul\Task\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\Task\Models\Task;

class TaskDataGrid extends DataGrid
{
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('tasks')
            ->select(
                'tasks.id',
                'tasks.title',
                'tasks.status',
                'tasks.priority',
                'tasks.due_date',
                'tasks.due_time',
                'tasks.entity_type',
                'users.name as assignee_name',
            )
            ->leftJoin('users', 'tasks.assigned_to', '=', 'users.id');

        if ($userIds = bouncer()->getAuthorizedUserIds()) {
            $queryBuilder->where(function ($query) use ($userIds) {
                $query->whereIn('tasks.assigned_to', $userIds)
                    ->orWhereIn('tasks.created_by', $userIds);
            });
        }

        $this->addFilter('id', 'tasks.id');
        $this->addFilter('title', 'tasks.title');
        $this->addFilter('status', 'tasks.status');
        $this->addFilter('priority', 'tasks.priority');
        $this->addFilter('due_date', 'tasks.due_date');
        $this->addFilter('assignee_name', 'users.name');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('task::app.datagrid.id'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'title',
            'label' => trans('task::app.datagrid.title'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('task::app.datagrid.status'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $key = 'task::app.status.'.$row->status;
                $label = trans($key);

                return e($label === $key ? str_replace('_', ' ', (string) $row->status) : $label);
            },
        ]);

        $this->addColumn([
            'index' => 'priority',
            'label' => trans('task::app.datagrid.priority'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => collect(Task::PRIORITIES)->map(fn ($priority) => [
                'label' => trans('task::app.priority.'.$priority),
                'value' => $priority,
            ])->all(),
            'closure' => fn ($row) => trans('task::app.priority.'.$row->priority),
        ]);

        $this->addColumn([
            'index' => 'due_date',
            'label' => trans('task::app.datagrid.due'),
            'type' => 'date',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                if (! $row->due_date) {
                    return '—';
                }

                return e($row->due_date.($row->due_time ? ' '.substr((string) $row->due_time, 0, 5) : ''));
            },
        ]);

        $this->addColumn([
            'index' => 'assignee_name',
            'label' => trans('task::app.datagrid.assignee'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => e($row->assignee_name ?: '—'),
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('tasks.view')) {
            $this->addAction([
                'icon' => 'icon-view',
                'index' => 'view',
                'title' => trans('task::app.datagrid.view'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.tasks.view', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('tasks.edit')) {
            $this->addAction([
                'icon' => 'icon-edit',
                'index' => 'edit',
                'title' => trans('task::app.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.tasks.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('tasks.delete')) {
            $this->addAction([
                'icon' => 'icon-delete',
                'index' => 'delete',
                'title' => trans('task::app.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.tasks.delete', $row->id),
            ]);
        }
    }

    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('tasks.edit')) {
            $this->addMassAction([
                'title' => trans('task::app.datagrid.mass-update'),
                'url' => route('admin.tasks.mass_update'),
                'method' => 'POST',
                'options' => [
                    ['label' => trans('task::app.status.pending'), 'value' => Task::STATUS_PENDING],
                    ['label' => trans('task::app.status.in_progress'), 'value' => Task::STATUS_IN_PROGRESS],
                    ['label' => trans('task::app.status.completed'), 'value' => Task::STATUS_COMPLETED],
                    ['label' => trans('task::app.status.cancelled'), 'value' => Task::STATUS_CANCELLED],
                ],
            ]);
        }

        if (bouncer()->hasPermission('tasks.delete')) {
            $this->addMassAction([
                'icon' => 'icon-delete',
                'title' => trans('task::app.datagrid.mass-delete'),
                'method' => 'POST',
                'url' => route('admin.tasks.mass_delete'),
            ]);
        }
    }
}
