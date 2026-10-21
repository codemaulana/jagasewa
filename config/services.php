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

    /*
    |--------------------------------------------------------------------------
    | Error Monitoring Configuration (T-36)
    |--------------------------------------------------------------------------
    |
    | Supports Sentry DSN (via official Sentry Store REST API) and Webhook
    | alerts (Discord/Slack/Telegram) without extra heavy packages.
    |
    */
    'monitoring' => [
        'sentry_dsn' => env('SENTRY_DSN'),
        'webhook_url' => env('MONITOR_WEBHOOK_URL'),
        'enabled' => env('MONITOR_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mayar Payment Gateway Configuration (T-42)
    |--------------------------------------------------------------------------
    */
    'mayar' => [
        'api_url' => env('MAYAR_API_URL', 'https://api.mayar.id/hl/v1'),
        'api_key' => env('MAYAR_API_KEY'),
        'webhook_secret' => env('MAYAR_WEBHOOK_SECRET', 'test-mayar-webhook-secret'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Xendit Payment Gateway Configuration (Fallback/Alternative)
    |--------------------------------------------------------------------------
    */
    'xendit' => [
        'callback_token' => env('XENDIT_CALLBACK_TOKEN', 'test-xendit-callback-token'),
        'secret_key' => env('XENDIT_SECRET_KEY'),
    ],

];
