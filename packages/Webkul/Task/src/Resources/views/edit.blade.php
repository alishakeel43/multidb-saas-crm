<x-admin::layouts>
    <x-slot:title>@lang('task::app.edit.title')</x-slot>

    <div class="flex flex-col gap-4">
        <div class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xl font-bold dark:text-white">@lang('task::app.edit.title')</div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <x-admin::form method="PUT" :action="route('admin.tasks.update', $task->id)">
                @include('task::form')
                <div class="mt-4">
                    <button type="submit" class="primary-button">@lang('task::app.form.save')</button>
                </div>
            </x-admin::form>
        </div>
    </div>
</x-admin::layouts>
