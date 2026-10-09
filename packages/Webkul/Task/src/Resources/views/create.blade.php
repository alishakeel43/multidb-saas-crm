<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.tasks.create.title')
    </x-slot>

    <x-admin::form :action="route('admin.tasks.store')">
        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs name="tasks.create" />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.tasks.create.title')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <a
                        href="{{ route('admin.tasks.index') }}"
                        class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                    >
                        @lang('admin::app.tasks.create.cancel')
                    </a>

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('admin::app.tasks.create.save-btn')
                    </button>
                </div>
            </div>

            <!-- Form Content -->
            <div class="flex gap-4 max-xl:flex-wrap">
                <!-- Left Panel (Core Details) -->
                <div class="flex flex-1 flex-col gap-4 max-xl:flex-auto">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.tasks.view.info')
                        </div>

                        <!-- Title -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.tasks.fields.title')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="title"
                                :value="old('title')"
                                rules="required"
                                :label="trans('admin::app.tasks.fields.title')"
                                :placeholder="trans('admin::app.tasks.fields.title-placeholder')"
                            />

                            <x-admin::form.control-group.error control-name="title" />
                        </x-admin::form.control-group>

                        <!-- Description -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.tasks.fields.description')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                name="description"
                                :value="old('description')"
                                :label="trans('admin::app.tasks.fields.description')"
                                :placeholder="trans('admin::app.tasks.fields.description-placeholder')"
                                rows="5"
                            />

                            <x-admin::form.control-group.error control-name="description" />
                        </x-admin::form.control-group>
                    </div>

                    <!-- Related CRM Record -->
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.tasks.view.relationships')
                        </div>

                        <x-task::entity-selector
                            :entity-type="old('entity_type', $entityType)"
                            :entity-id="old('entity_id', $entityId)"
                            :entity-title="$entityTitle"
                        />
                    </div>
                </div>

                <!-- Right Panel (Scheduling, Assignment & Workflow) -->
                <div class="flex w-[380px] max-w-full flex-col gap-4 max-xl:w-full">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            Workflow & Assignment
                        </div>

                        <!-- Status -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.tasks.fields.status')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="status"
                                :value="old('status', \Webkul\Task\Models\Task::STATUS_PENDING)"
                                :label="trans('admin::app.tasks.fields.status')"
                            >
                                @if (! empty($stages))
                                    @foreach ($stages as $stageOption)
                                        <option value="{{ $stageOption['code'] }}" {{ old('status', request('status', 'pending')) === $stageOption['code'] ? 'selected' : '' }}>
                                            {{ $stageOption['name'] }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="pending" {{ old('status', request('status', 'pending')) === 'pending' ? 'selected' : '' }}>@lang('admin::app.tasks.status.pending')</option>
                                    <option value="in_progress" {{ old('status', request('status', 'pending')) === 'in_progress' ? 'selected' : '' }}>@lang('admin::app.tasks.status.in_progress')</option>
                                    <option value="completed" {{ old('status', request('status', 'pending')) === 'completed' ? 'selected' : '' }}>@lang('admin::app.tasks.status.completed')</option>
                                    <option value="cancelled" {{ old('status', request('status', 'pending')) === 'cancelled' ? 'selected' : '' }}>@lang('admin::app.tasks.status.cancelled')</option>
                                @endif
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="status" />
                        </x-admin::form.control-group>

                        <!-- Priority -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.tasks.fields.priority')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="priority"
                                :value="old('priority', \Webkul\Task\Models\Task::PRIORITY_MEDIUM)"
                                :label="trans('admin::app.tasks.fields.priority')"
                            >
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>@lang('admin::app.tasks.priority.low')</option>
                                <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>@lang('admin::app.tasks.priority.medium')</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>@lang('admin::app.tasks.priority.high')</option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>@lang('admin::app.tasks.priority.urgent')</option>
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="priority" />
                        </x-admin::form.control-group>

                        <!-- Assigned User -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.tasks.fields.assigned_to')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="assigned_to"
                                :value="old('assigned_to', auth()->id())"
                                :label="trans('admin::app.tasks.fields.assigned_to')"
                            >
                                <option value="">@lang('admin::app.tasks.fields.select-user')</option>
                                @foreach ($users as $user)
                                    <option
                                        value="{{ $user->id }}"
                                        {{ (int) old('assigned_to', auth()->id()) === (int) $user->id ? 'selected' : '' }}
                                    >
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="assigned_to" />
                        </x-admin::form.control-group>

                        <!-- Due Date -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.tasks.fields.due_date')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="date"
                                name="due_date"
                                :value="old('due_date')"
                                :label="trans('admin::app.tasks.fields.due_date')"
                            />

                            <x-admin::form.control-group.error control-name="due_date" />
                        </x-admin::form.control-group>

                        <!-- Due Time -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.tasks.fields.due_time')
                            </x-admin::form.control-group.label>

                            <x-task::time-picker
                                name="due_time"
                                :value="old('due_time')"
                            />

                            <x-admin::form.control-group.error control-name="due_time" />
                        </x-admin::form.control-group>
                    </div>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
