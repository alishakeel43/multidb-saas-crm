<?php

namespace Webkul\Telephony\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CallLogDataGrid extends DataGrid
{
    /**
     * Build the base query for call logs.
     *
     * The result set is scoped to the records visible to the current user via
     * bouncer()'s authorized-user scope — the same pattern used by
     * ActivityDataGrid.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('call_logs')
            ->select(
                'call_logs.*',
                'users.name as agent_name',
                'persons.name as contact_name',
            )
            ->leftJoin('users', 'call_logs.user_id', '=', 'users.id')
            ->leftJoin('persons', 'call_logs.contact_id', '=', 'persons.id')
            ->where(function ($query) {
                if ($userIds = bouncer()->getAuthorizedUserIds()) {
                    $query->whereIn('call_logs.user_id', $userIds);
                }
            });

        $this->addFilter('id', 'call_logs.id');
        $this->addFilter('direction', 'call_logs.direction');
        $this->addFilter('status', 'call_logs.status');
        $this->addFilter('from_number', 'call_logs.from_number');
        $this->addFilter('to_number', 'call_logs.to_number');
        $this->addFilter('started_at', 'call_logs.started_at');
        $this->addFilter('agent_name', 'users.name');

        return $queryBuilder;
    }

    /**
     * Define the visible columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('telephony::app.datagrid.id'),
            'type' => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'direction',
            'label' => trans('telephony::app.datagrid.direction'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => [
                ['label' => trans('telephony::app.datagrid.inbound'), 'value' => 'inbound'],
                ['label' => trans('telephony::app.datagrid.outbound'), 'value' => 'outbound'],
            ],
            'closure' => fn ($row) => trans('telephony::app.datagrid.'.$row->direction),
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('telephony::app.datagrid.status'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'from_number',
            'label' => trans('telephony::app.datagrid.from-number'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'to_number',
            'label' => trans('telephony::app.datagrid.to-number'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'contact_name',
            'label' => trans('telephony::app.datagrid.contact'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                if (! $row->contact_id || ! $row->contact_name) {
                    return "<span class='text-gray-500'>—</span>";
                }

                $route = urldecode(route('admin.contacts.persons.view', $row->contact_id));

                return "<a class='text-brandColor hover:underline' href='{$route}'>{$row->contact_name}</a>";
            },
        ]);

        $this->addColumn([
            'index' => 'duration_seconds',
            'label' => trans('telephony::app.datagrid.duration'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->duration_seconds === null) {
                    return '—';
                }

                $minutes = intdiv((int) $row->duration_seconds, 60);
                $seconds = (int) $row->duration_seconds % 60;

                return sprintf('%d:%02d', $minutes, $seconds);
            },
        ]);

        $this->addColumn([
            'index' => 'started_at',
            'label' => trans('telephony::app.datagrid.started-at'),
            'type' => 'date',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'filterable_type' => 'date_range',
            'closure' => fn ($row) => $row->started_at ? core()->formatDate($row->started_at) : '—',
        ]);

        $this->addColumn([
            'index' => 'agent_name',
            'label' => trans('telephony::app.datagrid.agent'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);
    }

    /**
     * Define row-level actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('telephony.call_logs.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('telephony::app.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.telephony.call_logs.delete', $row->id),
            ]);
        }
    }

    /**
     * Define mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('telephony.call_logs.delete')) {
            $this->addMassAction([
                'icon' => 'icon-delete',
                'title' => trans('telephony::app.datagrid.mass-delete'),
                'method' => 'POST',
                'url' => route('admin.telephony.call_logs.index'),
            ]);
        }
    }
}
