<?php

return [
    'admin_password' => env('TENANT_ADMIN_PASSWORD'),
    'trial_days' => (int) env('TENANT_TRIAL_DAYS', 14),
];
