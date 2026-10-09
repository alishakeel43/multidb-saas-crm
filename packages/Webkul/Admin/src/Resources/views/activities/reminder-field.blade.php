<label class="block text-sm">
    @lang('admin::app.reminders.minutes')
    @if ($validated ?? false)
        <x-admin::form.control-group.control type="number" name="reminder_minutes" rules="integer|min_value:0|max_value:10080"
            :value="old('reminder_minutes', $activity->reminder_minutes ?? config('activity-reminders.minutes'))"
            :label="trans('admin::app.reminders.minutes')" />
        <x-admin::form.control-group.error control-name="reminder_minutes" />
    @else
        <input type="number" name="reminder_minutes" min="0" max="10080"
            value="{{ old('reminder_minutes', $activity->reminder_minutes ?? config('activity-reminders.minutes')) }}"
            class="block w-full rounded border p-2 dark:bg-gray-900">
    @endif
</label>
