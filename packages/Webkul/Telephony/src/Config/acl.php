<?php

return [
    [
        'key' => 'telephony',
        'name' => 'telephony::app.acl.telephony',
        'route' => 'admin.telephony.call_logs.index',
        'sort' => 11,
    ],
    /**
     * Intermediate parents are required so Acl::prepareAclItems() can undot
     * nested keys without inventing key-less branches that crash Roles create/edit.
     */
    [
        'key' => 'telephony.call_logs',
        'name' => 'telephony::app.acl.call-logs',
        'route' => 'admin.telephony.call_logs.index',
        'sort' => 1,
    ],
    [
        'key' => 'telephony.call_logs.view',
        'name' => 'telephony::app.acl.call-logs-view',
        'route' => 'admin.telephony.call_logs.index',
        'sort' => 1,
    ],
    [
        'key' => 'telephony.call_logs.delete',
        'name' => 'telephony::app.acl.call-logs-delete',
        'route' => 'admin.telephony.call_logs.destroy',
        'sort' => 2,
    ],
    [
        'key' => 'telephony.settings',
        'name' => 'telephony::app.acl.settings',
        'route' => 'admin.telephony.settings.index',
        'sort' => 2,
    ],
    [
        'key' => 'telephony.settings.edit',
        'name' => 'telephony::app.acl.settings-edit',
        'route' => 'admin.telephony.settings.index',
        'sort' => 1,
    ],
];
