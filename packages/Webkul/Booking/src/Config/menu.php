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
        'key' => 'settings.booking',
        'name' => 'booking::app.menu.settings',
        'info' => 'booking::app.menu.settings-info',
        'route' => 'admin.settings.booking.index',
        'sort' => 6,
        'icon-class' => 'icon-calendar',
    ],
];
