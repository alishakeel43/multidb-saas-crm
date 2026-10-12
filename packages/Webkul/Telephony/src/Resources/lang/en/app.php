<?php

return [
    'menu' => [
        'telephony' => 'Telephony',
        'settings' => 'Telephony Settings',
    ],

    'acl' => [
        'telephony' => 'Telephony',
        'call-logs-view' => 'View Call Logs',
        'call-logs-delete' => 'Delete Call Logs',
        'settings-edit' => 'Edit Telephony Settings',
    ],

    'configuration' => [
        'telephony' => [
            'title' => 'Telephony',
            'info' => 'Configure VoIP / Twilio settings.',

            'credentials' => [
                'title' => 'Twilio Credentials',
                'info' => 'Enter your Twilio account credentials.',
                'account-sid' => 'Account SID',
                'auth-token' => 'Auth Token',
                'from-number' => 'Default From Number',
                'status-callback-base-url' => 'Status Callback Base URL',
                'webhook-signing-enabled' => 'Verify Webhook Signatures',
            ],
        ],
    ],

    'call-logs' => [
        'index' => [
            'title' => 'Call Logs',
        ],
    ],
];
