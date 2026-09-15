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
     | Google Maps — powers the "drop a pin" location picker on the report forms.
     | With no key set the picker hides itself and the plain location text field
     | keeps working, so the app stays usable without a billing account.
     |
     | `center` / `zoom` decide where the map opens before anything is picked;
     | default is the campus center for University Recovery System.
     */
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
        'center' => [
            'lat' => (float) env('CAMPUS_CENTER_LAT', -6.8161),
            'lng' => (float) env('CAMPUS_CENTER_LNG', 39.2894),
        ],
        'zoom' => (int) env('CAMPUS_MAP_ZOOM', 17),
    ],

];
