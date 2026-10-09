<?php

return [
    'minutes' => (int) env('ACTIVITY_REMINDER_MINUTES', 30),
    'email' => (bool) env('ACTIVITY_REMINDER_EMAIL', true),
    'sms' => [
        'enabled' => (bool) env('ACTIVITY_REMINDER_SMS', false),
        'driver' => env('ACTIVITY_SMS_DRIVER', 'null'),
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        // Current-tenant user ID => E.164 number. Server-managed, never a request value.
        'recipients' => json_decode(env('ACTIVITY_SMS_RECIPIENTS', '{}'), true) ?: [],
    ],
];
