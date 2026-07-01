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

    // API externa fake que alimenta o módulo de pedidos.
    'fakestore' => [
        'base_url' => env('FAKESTORE_BASE_URL', 'https://fakestoreapi.com'),
        'timeout' => (int) env('FAKESTORE_TIMEOUT', 15),
        'retries' => (int) env('FAKESTORE_RETRIES', 3),
        'retry_backoff' => (int) env('FAKESTORE_RETRY_BACKOFF', 200), // ms
        'rate_limit' => (int) env('FAKESTORE_RATE_LIMIT', 30), // req/s
        'page_size' => (int) env('FAKESTORE_PAGE_SIZE', 50),
    ],

    // Webhook do N8N para notificações orientadas a eventos.
    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
    ],

];
