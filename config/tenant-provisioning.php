<?php

return [
    'admin_password' => env('TENANT_ADMIN_PASSWORD'),
    'trial_days' => (int) env('TENANT_TRIAL_DAYS', 14),
    'enforce_subscription' => (bool) env('SAAS_ENFORCE_SUBSCRIPTION', false),
    'database_host_allowlist' => array_filter(array_map('trim', explode(',', env('TENANT_DATABASE_ALLOWED_HOSTS', env('DB_HOST', '127.0.0.1'))))),
    'database_port_allowlist' => array_filter(array_map('trim', explode(',', env('TENANT_DATABASE_ALLOWED_PORTS', env('DB_PORT', '8889'))))),
    'protected_databases' => array_filter(array_map('trim', explode(',', env('TENANT_PROTECTED_DATABASES', 'zaristech_saas,tenant_bootstrap')))),
];
