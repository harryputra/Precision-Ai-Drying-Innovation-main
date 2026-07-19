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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'openweather' => [
        'api_key' => env('OPENWEATHER_API_KEY'),
        'lat'     => env('OPENWEATHER_LAT', -7.0271),
        'lon'     => env('OPENWEATHER_LON', 107.5892),
        'city'    => env('OPENWEATHER_CITY', 'Margahurip,Banjaran,ID'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
    ],

    // API keys untuk proteksi endpoint publik
    'webhooks' => [
        // Dipakai n8n untuk endpoint /api/ai/context, /api/ai/decide, /api/ai/chat/reply
        'ai_key'  => env('AI_WEBHOOK_KEY'),
        // Dipakai ESP32 untuk endpoint /api/iot/*
        'iot_key' => env('IOT_DEVICE_KEY'),
    ],

];
