<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'genieacs' => [
        'url' => env('GENIEACS_URL', 'http://127.0.0.1:7557'),
    ],

    'whatsapp' => [
        'url' => env('WHATSAPP_API_URL'),
        'key' => env('WHATSAPP_API_KEY'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    ],

    'mixradius' => [
        'base_url' => env('MIXRADIUS_BASE_URL', ''),
        'api_token' => env('MIXRADIUS_API_TOKEN'),
        'api_secret' => env('MIXRADIUS_API_SECRET'),
        'auth_endpoint' => env('MIXRADIUS_AUTH_ENDPOINT'),
        'user_info_endpoint' => env('MIXRADIUS_USER_INFO_ENDPOINT'),
        'billing_endpoint' => env('MIXRADIUS_BILLING_ENDPOINT', '/api/invoices'),
        'invoice_html_url' => env('MIXRADIUS_INVOICE_HTML_URL'),
    ],

];
