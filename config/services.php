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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

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

    'forjawaly' => [
        'key' => env('FORJAWALY_API_KEY'),
        'secret' => env('FORJAWALY_API_SECRET'),
        'sender' => env('FORJAWALY_SENDER'),
        'base_url' => env('FORJAWALY_URL', 'https://api-sms.4jawaly.com/api/v1/'),
    ],

    // SMS Service Providers
    'unifonic' => [
        'app_sid' => env('UNIFONIC_APP_SID'),
        'sender_id' => env('UNIFONIC_SENDER_ID', 'Abeer'),
    ],

    'mobily' => [
        'username' => env('MOBILY_USERNAME'),
        'password' => env('MOBILY_PASSWORD'),
        'sender' => env('MOBILY_SENDER', 'Abeer'),
    ],

    'fourjawaly' => [
        'username' => env('4JAWALY_USERNAME'),
        'password' => env('4JAWALY_PASSWORD'),
        'sender' => env('4JAWALY_SENDER', 'Abeer'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Al Rajhi Bank Payment Gateway (single / exclusive payment gateway)
    |--------------------------------------------------------------------------
    |
    | Hosted Payment Page integration. The Transportal ID / password identify
    | the merchant terminal; the AES key + IV encrypt the "trandata" payload
    | exchanged with the bank. Currency 682 = SAR.
    |
    */
    'alrajhi' => [
        'base_url' => env('ALRAJHI_BASE_URL', 'https://securepayments.alrajhibank.com.sa'),
        'transportal_id' => env('ALRAJHI_TRANSPORTAL_ID'),
        'password' => env('ALRAJHI_PASSWORD'),
        'encryption_key' => env('ALRAJHI_ENCRYPTION_KEY'),
        'iv' => env('ALRAJHI_IV', 'PGKEYENCDECIVSPC'),
        'currency_code' => env('ALRAJHI_CURRENCY_CODE', '682'),
        // redirect_text: reply "REDIRECT=<url>" to the bank's server-to-server callback (default)
        // http_redirect: reply with an HTTP 302 (merchant profiles configured for browser POST)
        'callback_mode' => env('ALRAJHI_CALLBACK_MODE', 'redirect_text'),
    ],
];
