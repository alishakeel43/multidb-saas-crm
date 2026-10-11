<?php

return [
    /*
     * Quotes becomes a parent once Approvals is registered.
     * Mirror Contacts: keep an explicit child that opens the Quotes list (Create button lives there).
     */
    [
        'key' => 'quotes.quotes',
        'name' => 'quote_approval::app.menu.quotes',
        'route' => 'admin.quotes.index',
        'sort' => 1,
        'icon-class' => '',
    ],
    [
        'key' => 'quotes.approvals',
        'name' => 'quote_approval::app.menu.approvals',
        'route' => 'admin.quotes.approvals.index',
        'sort' => 2,
        'icon-class' => '',
    ],
];
