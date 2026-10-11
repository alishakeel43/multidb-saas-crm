<?php

return [
    'channels' => [
        'whatsapp' => ['label' => 'WhatsApp'],
        'messenger' => ['label' => 'Messenger'],
        'instagram' => ['label' => 'Instagram'],
    ],

    'meta' => [
        'app_id' => env('UNIFIED_INBOX_META_APP_ID'),
        'app_secret' => env('UNIFIED_INBOX_META_APP_SECRET'),
        'version' => env('UNIFIED_INBOX_META_VERSION') ?: 'v21.0',
        'verify_token' => env('UNIFIED_INBOX_META_VERIFY_TOKEN'),
        // Facebook Login for Business configuration IDs (Embedded Signup).
        'whatsapp_config_id' => env('UNIFIED_INBOX_WHATSAPP_CONFIG_ID'),
        'messaging_config_id' => env('UNIFIED_INBOX_MESSAGING_CONFIG_ID'),
    ],

    // Explicit central webhook override (defaults to root app.url)
    'webhook_url' => env('UNIFIED_INBOX_WEBHOOK_URL'),
];
