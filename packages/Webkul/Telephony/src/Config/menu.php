<?php

return [
    [
        'key' => 'telephony',
        'name' => 'telephony::app.menu.telephony',
        'route' => 'admin.telephony.call_logs.index',
        'sort' => 11,
        'icon-class' => 'icon-activity',
    ],
    [
        'key' => 'telephony.settings',
        'name' => 'telephony::app.menu.settings',
        'route' => 'admin.telephony.settings.index',
        'sort' => 1,
        'icon-class' => 'icon-setting',
    ],
];
