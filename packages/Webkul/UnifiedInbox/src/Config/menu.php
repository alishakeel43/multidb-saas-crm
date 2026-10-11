<?php

return [
    [
        'key' => 'unified_inbox',
        'name' => 'unified_inbox::app.menu.inbox',
        'route' => 'admin.unified_inbox.index',
        'sort' => 3,
        'icon-class' => 'icon-mail',
    ],
    [
        'key' => 'settings.other_settings.unified_inbox_channels',
        'name' => 'unified_inbox::app.menu.channels',
        'info' => 'unified_inbox::app.menu.channels-info',
        'route' => 'admin.unified_inbox.channels.index',
        'sort' => 14,
        'icon-class' => 'icon-setting',
    ],
];
