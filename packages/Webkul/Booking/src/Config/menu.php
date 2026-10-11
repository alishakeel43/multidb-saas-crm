<?php

return [
    [
        'key' => 'bookings',
        'name' => 'booking::app.menu.bookings',
        'route' => 'admin.bookings.index',
        'sort' => 5,
        'icon-class' => 'icon-calendar',
    ],
    [
        'key' => 'settings.other_settings.booking',
        'name' => 'booking::app.menu.settings',
        'info' => 'booking::app.menu.settings-info',
        'route' => 'admin.settings.booking.index',
        'sort' => 11,
        'icon-class' => 'icon-calendar',
    ],
];
