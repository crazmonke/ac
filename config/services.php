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

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
    ],

    'apartment_sync' => [
        'source_url' => env('APARTMENT_SYNC_SOURCE_URL', 'https://apis.data.go.kr/1613000/AptListService3'),
        'service_key' => env('APARTMENT_SYNC_SERVICE_KEY'),
    ],

    'adsense' => [
        'client_id' => env('ADSENSE_CLIENT_ID', 'ca-pub-7984252442494010'),
        'home_hero_slot' => env('ADSENSE_HOME_HERO_SLOT', '1649371006'),
        'home_feed_slot' => env('ADSENSE_HOME_FEED_SLOT', '6299062458'),
        'home_feed_layout_key' => env('ADSENSE_HOME_FEED_LAYOUT_KEY', '-ex+6g-2p-8d+pe'),
        'home_feed_interval' => max(0, (int) env('ADSENSE_HOME_FEED_INTERVAL', 5)),
    ],

];
