<?php

return [
    [
        'key' => 'quotes.quotes',
        'name' => 'quote_approval::app.menu.quotes',
        'route' => 'admin.quotes.index',
        'sort' => 1,
    ],
    [
        'key' => 'quotes.approvals',
        'name' => 'quote_approval::app.acl.approvals',
        'route' => 'admin.quotes.approvals.index',
        'sort' => 2,
    ],
];
