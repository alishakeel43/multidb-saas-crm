<?php

return [
    [
        'key' => 'telephony',
        'name' => 'telephony::app.configuration.telephony.title',
        'info' => 'telephony::app.configuration.telephony.info',
        'sort' => 10,
    ],
    [
        'key' => 'telephony.credentials',
        'name' => 'telephony::app.configuration.telephony.credentials.title',
        'info' => 'telephony::app.configuration.telephony.credentials.info',
        'icon' => 'icon-phone',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'account_sid',
                'title' => 'telephony::app.configuration.telephony.credentials.account-sid',
                'type' => 'text',
                'validation' => 'required',
            ],
            [
                'name' => 'auth_token',
                'title' => 'telephony::app.configuration.telephony.credentials.auth-token',
                'type' => 'password',
                'validation' => 'required',
            ],
            [
                'name' => 'from_number',
                'title' => 'telephony::app.configuration.telephony.credentials.from-number',
                'type' => 'text',
            ],
            [
                'name' => 'status_callback_base_url',
                'title' => 'telephony::app.configuration.telephony.credentials.status-callback-base-url',
                'type' => 'text',
            ],
            [
                'name' => 'webhook_signing_enabled',
                'title' => 'telephony::app.configuration.telephony.credentials.webhook-signing-enabled',
                'type' => 'boolean',
                'default' => true,
            ],
        ],
    ],
];
