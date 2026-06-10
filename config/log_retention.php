<?php

return [
    'limits' => [
        'max_runtime_seconds' => env('LOG_RETENTION_MAX_RUNTIME_SECONDS', 900),
        'sleep_ms_between_batches' => env('LOG_RETENTION_SLEEP_MS', 25),
        'max_batches_per_section' => env('LOG_RETENTION_MAX_BATCHES_PER_SECTION', 0),
    ],

    'whatsapp' => [
        'payload_null_after_days' => env('WHATSAPP_PAYLOAD_RETENTION_DAYS', 7),
        'delete_after_days' => env('WHATSAPP_LOG_RETENTION_DAYS', 90),
        'batch_size' => env('WHATSAPP_LOG_RETENTION_BATCH_SIZE', 1000),
        'max_batches' => env('WHATSAPP_LOG_RETENTION_MAX_BATCHES', 0),
    ],

    'notification' => [
        'response_null_after_days' => env('NOTIFICATION_RESPONSE_RETENTION_DAYS', 7),
        'delete_after_days' => env('NOTIFICATION_LOG_RETENTION_DAYS', 180),
        'batch_size' => env('NOTIFICATION_LOG_RETENTION_BATCH_SIZE', 1000),
        'max_batches' => env('NOTIFICATION_LOG_RETENTION_MAX_BATCHES', 0),
    ],

    'scheduler' => [
        'run_at' => env('LOG_RETENTION_RUN_AT', '02:25'),
        'without_overlapping_minutes' => env('LOG_RETENTION_WITHOUT_OVERLAPPING_MINUTES', 30),
        'on_one_server' => env('LOG_RETENTION_ON_ONE_SERVER', true),
    ],

    'healthcheck' => [
        'max_age_hours' => env('LOG_RETENTION_HEALTHCHECK_MAX_AGE_HOURS', 36),
    ],
];
