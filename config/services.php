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

    'digiflazz' => [
        'username' => env('DIGIFLAZZ_USERNAME'),
        'api_key' => env('DIGIFLAZZ_API_KEY'),
        'webhook_secret' => env('DIGIFLAZZ_WEBHOOK_SECRET'),
        'base_url' => env('DIGIFLAZZ_BASE_URL', 'https://api.digiflazz.com/v1'),
    ],

    'tripay' => [
        'api_key' => env('TRIPAY_API_KEY'),
        'private_key' => env('TRIPAY_PRIVATE_KEY'),
        'merchant_code' => env('TRIPAY_MERCHANT_CODE'),
        'mode' => env('TRIPAY_MODE', 'sandbox'),
    ],

    'user_id_check' => [
        'endpoint' => env('USER_ID_CHECK_ENDPOINT', 'https://order-sg.codashop.com/initPayment.action'),
        'timeout' => env('USER_ID_CHECK_TIMEOUT', 5),
        'cache_seconds' => env('USER_ID_CHECK_CACHE_SECONDS', 60),
        // Optional override per slug. If empty, fallback to values stored in table games.
        'games' => [],
    ],

    'product_grouping' => [
        'fallback_label' => env('PRODUCT_GROUPING_FALLBACK_LABEL', 'Produk Lainnya'),
        'default_rules' => [
            'Diamond' => ['diamond'],
            'Event Top Up' => ['event'],
        ],
        'rules_by_slug' => [
            'genshin' => [
                'Blessing' => ['blessing', 'welkin'],
                'Genesis Crystal' => ['genesis', 'crystal'],
            ],
            'hsr' => [
                'Express Supply Pass' => ['express', 'supply', 'pass'],
                'Oneiric Shard' => ['oneiric', 'shard'],
            ],
            'mobile-legends' => [
                'WDP' => ['wdp', 'weekly', 'weekly diamond pass'],
                'Diamond' => ['diamond'],
            ],
        ],
    ],

];
