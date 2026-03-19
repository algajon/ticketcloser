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

    'vapi' => [
        'key' => env('VAPI_API_KEY'),
        'base_url' => env('VAPI_BASE_URL', 'https://api.vapi.ai'),
        'webhook_url' => env('VAPI_WEBHOOK_URL', rtrim(env('APP_URL'), '/') . '/api/webhooks/vapi'),
        'secret' => env('VAPI_WEBHOOK_SECRET'),
    ],

    'server_api_token' => env('SERVER_API_TOKEN'),

    // ── Stripe ──────────────────────────────────────────────────────
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'prices' => [
            'startup' => env('STRIPE_PRICE_STARTUP'),
            'pro' => env('STRIPE_PRICE_PRO'),
            'enterprise' => env('STRIPE_PRICE_ENTERPRISE'),
        ],
    ],

    // ── Google (Calendar OAuth) ──────────────────────────────────────
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // ── OpenAI-compatible LLM (Prompt Writer) ───────────────────────
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

];
