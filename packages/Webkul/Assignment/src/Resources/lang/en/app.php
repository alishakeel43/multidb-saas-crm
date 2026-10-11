<?php

return [
    'menu' => [
        'assignment' => 'Lead Assignment',
        'assignment-info' => 'Round-robin, workload, and rule-based lead allocation',
    ],
    'acl' => [
        'assignment' => 'Lead Assignment',
    ],
    'index' => [
        'title' => 'Lead Assignment Rules',
        'create-btn' => 'Add Rule',
        'name' => 'Name',
        'strategy' => 'Strategy',
        'users' => 'Assignees',
        'priority' => 'Priority',
        'only-when-empty' => 'Only when owner is empty',
        'active' => 'Active',
        'strategies' => [
            'round_robin' => 'Round robin',
            'least_workload' => 'Least open leads',
            'first_available' => 'First in list',
        ],
    ],
    'messages' => [
        'created' => 'Assignment rule created successfully.',
        'updated' => 'Assignment rule updated successfully.',
        'deleted' => 'Assignment rule deleted successfully.',
    ],
];
