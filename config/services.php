<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Not wired up to any code yet — reserved for the Microsoft Graph "auto-create
    // a real Teams meeting" follow-up. See the "Microsoft Teams Integration" section
    // of the README for what these values are and how to obtain them.
    'microsoft_teams' => [
        'tenant_id' => env('MS_TEAMS_TENANT_ID'),
        'client_id' => env('MS_TEAMS_CLIENT_ID'),
        'client_secret' => env('MS_TEAMS_CLIENT_SECRET'),
        'organizer_upn' => env('MS_TEAMS_ORGANIZER_UPN'),
    ],

];
