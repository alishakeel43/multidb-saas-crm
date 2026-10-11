<?php

return [
    [
        'key' => 'bookings',
        'name' => 'booking::app.acl.bookings',
        'route' => 'admin.bookings.index',
        'sort' => 5,
    ],
    [
        'key' => 'settings.other_settings.booking',
        'name' => 'booking::app.acl.settings',
        'route' => 'admin.settings.booking.index',
        'sort' => 11,
    ],
];
