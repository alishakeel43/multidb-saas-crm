<?php

return [
    'admin_password' => env('TENANT_ADMIN_PASSWORD'),
    'trial_days' => (int) env('TENANT_TRIAL_DAYS', 14),
    'enforce_subscription' => (bool) env('SAAS_ENFORCE_SUBSCRIPTION', false),
];
