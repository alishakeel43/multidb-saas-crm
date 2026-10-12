<x-admin::layouts>
    <x-slot:title>
        @lang('telephony::app.call-logs.index.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">
                    @lang('telephony::app.call-logs.index.title')
                </div>
            </div>
        </div>

        {!! view_render_event('admin.telephony.call_logs.index.datagrid.before') !!}

        <x-admin::datagrid :src="route('admin.telephony.call_logs.index')" />

        {!! view_render_event('admin.telephony.call_logs.index.datagrid.after') !!}
    </div>
</x-admin::layouts>
