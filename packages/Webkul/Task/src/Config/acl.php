<?php

return [
    [
        'key' => 'tasks',
        'name' => 'admin::app.acl.tasks',
        'route' => 'admin.tasks.index',
        'sort' => 5.5,
    ], [
        'key' => 'tasks.create',
        'name' => 'admin::app.acl.create',
        'route' => ['admin.tasks.create', 'admin.tasks.store', 'admin.tasks.search_entities', 'admin.tasks.stages', 'admin.tasks.stages.store'],
        'sort' => 1,
    ], [
        'key' => 'tasks.edit',
        'name' => 'admin::app.acl.edit',
        'route' => ['admin.tasks.edit', 'admin.tasks.update', 'admin.tasks.status', 'admin.tasks.mass_update', 'admin.tasks.search_entities', 'admin.tasks.stages.update', 'admin.tasks.stages.reassign'],
        'sort' => 2,
    ], [
        'key' => 'tasks.view',
        'name' => 'admin::app.acl.view',
        'route' => ['admin.tasks.view', 'admin.tasks.entity', 'admin.tasks.dashboard_stats', 'admin.tasks.kanban_data', 'admin.tasks.stages'],
        'sort' => 3,
    ], [
        'key' => 'tasks.delete',
        'name' => 'admin::app.acl.delete',
        'route' => ['admin.tasks.delete', 'admin.tasks.mass_delete', 'admin.tasks.stages.delete'],
        'sort' => 4,
    ],
];
