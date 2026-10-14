@php
    $entityType = null;
    $entityId = null;

    if (! empty($lead)) {
        $entityType = 'leads';
        $entityId = $lead->id;
    } elseif (! empty($person)) {
        $entityType = 'persons';
        $entityId = $person->id;
    }
@endphp

@if ($entityType && $entityId && bouncer()->hasPermission('tasks.create'))
    <a
        href="{{ route('admin.tasks.create', ['entity_type' => $entityType, 'entity_id' => $entityId]) }}"
        class="transparent-button"
    >
        @lang('task::app.related.add')
    </a>
@endif
