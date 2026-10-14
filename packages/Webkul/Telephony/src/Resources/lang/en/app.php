<?php

return [
    'menu' => [
        'telephony' => 'Telephony',
        'settings' => 'Telephony Settings',
    ],

    'acl' => [
        'telephony' => 'Telephony',
        'call-logs' => 'Call Logs',
        'call-logs-view' => 'View Call Logs',
        'call-logs-delete' => 'Delete Call Logs',
        'settings' => 'Telephony Settings',
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

        'call-initiated' => 'Call initiated successfully.',
        'deleted' => 'Call log deleted successfully.',

        'errors' => [
            'no-from-number' => 'No outbound phone number configured. Please set a "From Number" in Telephony Settings.',
            'initiation-failed' => 'Unable to initiate call. Please check your Twilio credentials and try again.',
            'no-recording' => 'No recording available for this call.',
        ],
    ],

    'settings' => [
        'title' => 'Telephony Settings',
        'save' => 'Save Settings',
        'saved' => 'Telephony settings saved successfully.',
        'auth-token-placeholder' => 'Leave blank to keep current token',
        'auth-token-note' => 'The Auth Token is stored securely and will not be displayed again.',
        'callback-url-note' => 'Override the base URL used for Twilio status callbacks. Leave blank to use the app URL.',
    ],

    'datagrid' => [
        'id' => 'ID',
        'direction' => 'Direction',
        'status' => 'Status',
        'from-number' => 'From',
        'to-number' => 'To',
        'contact' => 'Contact',
        'duration' => 'Duration',
        'started-at' => 'Started At',
        'agent' => 'Agent',
        'delete' => 'Delete',
        'mass-delete' => 'Mass Delete',
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
    ],
];
