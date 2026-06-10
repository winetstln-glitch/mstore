<?php

return [
    'whatsapp' => [
        'payload_null_after_days' => env('WHATSAPP_PAYLOAD_RETENTION_DAYS', 7),
        'delete_after_days' => env('WHATSAPP_LOG_RETENTION_DAYS', 90),
        'batch_size' => env('WHATSAPP_LOG_RETENTION_BATCH_SIZE', 1000),
    ],

    'notification' => [
        'response_null_after_days' => env('NOTIFICATION_RESPONSE_RETENTION_DAYS', 7),
        'delete_after_days' => env('NOTIFICATION_LOG_RETENTION_DAYS', 180),
        'batch_size' => env('NOTIFICATION_LOG_RETENTION_BATCH_SIZE', 1000),
    ],

    'scheduler' => [
        'run_at' => env('LOG_RETENTION_RUN_AT', '02:25'),
        'without_overlapping_minutes' => env('LOG_RETENTION_WITHOUT_OVERLAPPING_MINUTES', 30),
        'on_one_server' => env('LOG_RETENTION_ON_ONE_SERVER', true),
    ],
];

