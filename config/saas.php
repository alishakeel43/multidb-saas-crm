<?php

return [
    'trial_days' => (int) env('TENANT_TRIAL_DAYS', 14),

    'enforce_subscription' => (bool) env('SAAS_ENFORCE_SUBSCRIPTION', false),

    'default_storage_quota_mb' => (int) env('SAAS_DEFAULT_STORAGE_QUOTA_MB', 1024),

    'default_currency' => env('SAAS_DEFAULT_CURRENCY', env('APP_CURRENCY', 'USD')),

    'tax_percent' => (float) env('SAAS_TAX_PERCENT', 0),

    'invoice_number_prefix' => env('SAAS_INVOICE_PREFIX', 'INV'),

    'tenant_backups' => [
        'path' => env('SAAS_TENANT_BACKUP_PATH', storage_path('backups/tenants')),
        'retention_days' => (int) env('SAAS_TENANT_BACKUP_RETENTION_DAYS', 14),
    ],

    'subscription_blocked_statuses' => [
        'expired',
        'canceled',
    ],

    'tenant_blocked_statuses' => [
        'suspended',
        'canceled',
    ],
];
