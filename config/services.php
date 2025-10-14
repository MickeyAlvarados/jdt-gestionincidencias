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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DeepSeek AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the DeepSeek AI service used by the intelligent
    | support agent. This service provides AI-powered problem resolution
    | and automated technical support.
    |
    */

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'api_url' => env('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'max_tokens' => env('DEEPSEEK_MAX_TOKENS', 1000),
        'temperature' => env('DEEPSEEK_TEMPERATURE', 0.7),
        'timeout' => env('DEEPSEEK_TIMEOUT', 30), // segundos
    ],

];
