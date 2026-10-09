@php
    $actionEntityType = null;
    $actionEntityId = null;

    if (isset($lead) && $lead) {
        $actionEntityType = 'leads';
        $actionEntityId = $lead->id;
    } elseif (isset($person) && $person) {
        $actionEntityType = 'persons';
        $actionEntityId = $person->id;
    } elseif (isset($organization) && $organization) {
        $actionEntityType = 'organizations';
        $actionEntityId = $organization->id;
    }
@endphp

@if ($actionEntityType && $actionEntityId && bouncer()->hasPermission('tasks.create'))
    <a
        href="{{ route('admin.tasks.create', ['entity_type' => $actionEntityType, 'entity_id' => $actionEntityId]) }}"
        class="secondary-button"
        title="Add Task"
    >
        <i class="icon-tick text-base"></i>
        <span>@lang('admin::app.tasks.actions.quick-add')</span>
    </a>
@endif
