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
     * Agoda Affiliate Lite API: live prices and guest ratings (App\Support\Agoda\AgodaClient).
     * AGODA_API_KEY may be "siteid:key" as shown in the Agoda partner portal.
     */
    'agoda' => [
        'url' => env('AGODA_API_URL', 'https://affiliateapi7643.agoda.com/affiliateservice/lt_v1'),
        'site_id' => env('AGODA_SITE_ID'),
        'api_key' => env('AGODA_API_KEY'),
        'currency' => env('AGODA_CURRENCY', 'EUR'),
        'language' => env('AGODA_LANGUAGE', 'en-us'),
        'timeout' => (int) env('AGODA_TIMEOUT', 8),
    ],

];
