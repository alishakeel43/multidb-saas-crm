{!! view_render_event('admin.tasks.index.table.before') !!}

<x-admin::datagrid :src="route('admin.tasks.index')">
    <!-- DataGrid Shimmer -->
    <x-admin::shimmer.datagrid />

    <x-slot:toolbar-right-after>
        @include('task::index.view-switcher')

        <x-admin::datagrid.column-settings />
    </x-slot>
</x-admin::datagrid>

{!! view_render_event('admin.tasks.index.table.after') !!}
