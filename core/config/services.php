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
        'token' => env('POSTMARK_TOKEN'),
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

    'bictorys' => [
        'webhook_secret' => env('BICTORYS_WEBHOOK_SECRET'),
        'webhook_require_signature' => env('BICTORYS_WEBHOOK_REQUIRE_SIGNATURE', false),
        'webhook_require_https' => env('BICTORYS_WEBHOOK_REQUIRE_HTTPS', false),
        'webhook_queue_enabled' => env('BICTORYS_WEBHOOK_QUEUE_ENABLED', false),
        'webhook_process_inline' => env('BICTORYS_WEBHOOK_PROCESS_INLINE', true),
    ],

];
