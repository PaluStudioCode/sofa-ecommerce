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

    'midtrans' => [
        'driver' => env('MIDTRANS_DRIVER', env('APP_ENV') === 'testing' ? 'fake' : 'http'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'callback_url' => env('MIDTRANS_CALLBACK_URL'),
        'snap_base_url' => env('MIDTRANS_IS_PRODUCTION', false)
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com',
        'api_base_url' => env('MIDTRANS_IS_PRODUCTION', false)
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com',
    ],

    'google_maps' => [
        'driver' => env('GOOGLE_MAPS_DRIVER', env('APP_ENV') === 'testing' ? 'fake' : 'http'),
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'browser_key' => env('VITE_GOOGLE_MAPS_API_KEY'),
        'map_id' => env('GOOGLE_MAPS_MAP_ID'),
        'api_base_url' => env('GOOGLE_MAPS_API_BASE_URL', 'https://maps.googleapis.com/maps/api'),
    ],

    'fonnte' => [
        'driver' => env('FONNTE_DRIVER', env('APP_ENV') === 'testing' ? 'fake' : 'http'),
        'token' => env('FONNTE_TOKEN'),
        'base_url' => env('FONNTE_BASE_URL', 'https://api.fonnte.com'),
    ],

    'ngrok' => [
        'url' => env('NGROK_URL'),
        'authtoken' => env('NGROK_AUTHTOKEN'),
    ],

];
