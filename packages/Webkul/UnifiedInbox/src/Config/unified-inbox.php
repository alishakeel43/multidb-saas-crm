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

    // AI reply suggestions (OpenAI-compatible /chat/completions).
    // provider: grok (xAI) | custom (local Ollama/LM Studio/etc.) | openai
    // Defaults per provider live in Services/AI/AiProviderFactory::DEFAULTS.
    // If no ENV key is set (and not custom+base_url), Krayin's Magic AI
    // settings (Configuration → General → Magic AI) are used as a fallback.
    'ai' => [
        'provider' => env('UNIFIED_INBOX_AI_PROVIDER', 'grok'),
        'api_key' => env('UNIFIED_INBOX_AI_API_KEY'),
        'model' => env('UNIFIED_INBOX_AI_MODEL'),
        'base_url' => env('UNIFIED_INBOX_AI_BASE_URL'),
        'timeout' => (int) env('UNIFIED_INBOX_AI_TIMEOUT', 20),
    ],
];
