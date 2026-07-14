<?php

return [
    'default' => env('NETWORK_PROVIDER', 'dummy'),

    'providers' => [
        'dummy' => [
            // No extra config needed
        ],
        'mikrotik' => [
            // Use existing MikrotikService config for now
        ],
        'freeradius' => [
            'base_url' => env('FREERADIUS_BASE_URL', ''),
            'api_token' => env('FREERADIUS_API_TOKEN', ''),
            'api_secret' => env('FREERADIUS_API_SECRET', ''),
            'auth_endpoint' => env('FREERADIUS_AUTH_ENDPOINT', '/api/users/auth'),
            'user_info_endpoint' => env('FREERADIUS_USER_INFO_ENDPOINT', '/api/users/info'),
            'billing_endpoint' => env('FREERADIUS_BILLING_ENDPOINT', '/api/invoices'),
        ],
        'genieacs' => [
            'base_url' => env('GENIEACS_BASE_URL', 'http://localhost:7557'),
        ],
        'huaweiolt' => [
            'host' => env('HUAWEIOLT_HOST', ''),
            'port' => env('HUAWEIOLT_PORT', 22),
            'username' => env('HUAWEIOLT_USERNAME', ''),
            'password' => env('HUAWEIOLT_PASSWORD', ''),
        ],
        'zteolt' => [
            'host' => env('ZTEOLT_HOST', ''),
            'port' => env('ZTEOLT_PORT', 22),
            'username' => env('ZTEOLT_USERNAME', ''),
            'password' => env('ZTEOLT_PASSWORD', ''),
        ],
        'fiberhomeolt' => [
            'host' => env('FIBERHOMEOLT_HOST', ''),
            'port' => env('FIBERHOMEOLT_PORT', 22),
            'username' => env('FIBERHOMEOLT_USERNAME', ''),
            'password' => env('FIBERHOMEOLT_PASSWORD', ''),
        ],
        'cdataolt' => [
            'host' => env('CDATAOLT_HOST', ''),
            'port' => env('CDATAOLT_PORT', 22),
            'username' => env('CDATAOLT_USERNAME', ''),
            'password' => env('CDATAOLT_PASSWORD', ''),
        ],
        'hsgqolt' => [
            'host' => env('HSGQOLT_HOST', ''),
            'port' => env('HSGQOLT_PORT', 22),
            'username' => env('HSGQOLT_USERNAME', ''),
            'password' => env('HSGQOLT_PASSWORD', ''),
        ],
    ],
];
