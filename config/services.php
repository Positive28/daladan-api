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

    'eskiz' => [
        'email' => env('ESKIZ_EMAIL'),
        'password' => env('ESKIZ_PASSWORD'),
        'sms_from' => env('ESKIZ_SMS_FROM', '4546'),
        'base_url' => rtrim(env('ESKIZ_BASE_URL', 'https://notify.eskiz.uz/api'), '/'),
        'request_timeout_seconds' => max(5, min(120, (int) env('ESKIZ_REQUEST_TIMEOUT', 25))),
        'connect_timeout_seconds' => max(2, min(60, (int) env('ESKIZ_CONNECT_TIMEOUT', 10))),
        'verify_ssl' => filter_var(env('ESKIZ_HTTP_VERIFY_SSL', 'true'), FILTER_VALIDATE_BOOL),
        // Eskiz trial: faqat quyidagi uchala matn (belgilar darajasida mos).
        'trial_sms_bodies' => [
            'uz' => 'Bu Eskiz dan test',
            'ru' => 'Это тест от Eskiz',
            'en' => 'This is test from Eskiz',
        ],
        'otp_trial_variant' => strtolower((string) env('ESKIZ_OTP_TRIAL_VARIANT', 'uz')),
        // .envda ko'rsatilmasa: localda true (trial), boshqa muhitda false.
        'otp_use_test_template' => (($r = env('ESKIZ_OTP_USE_TEST_TEMPLATE')) !== null && $r !== '')
            ? filter_var($r, FILTER_VALIDATE_BOOL)
            : env('APP_ENV') === 'local',
        // Bo'sh bo'lsa trial_sms_bodies dan tanlanadi; maxsus matn kerak bo'lsa .envda yozing.
        'otp_test_template_body' => env('ESKIZ_OTP_TEST_TEMPLATE_TEXT'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
