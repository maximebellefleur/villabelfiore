<?php

return [
    'name'     => env('APP_NAME', 'Rooted'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost'),
    'key'      => env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'Europe/Rome'),
    'locale'   => env('APP_LOCALE', 'en'),
    'currency' => env('APP_CURRENCY', 'EUR'),

    'session' => [
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),
        'name'     => env('SESSION_NAME', 'rooted_session'),
    ],

    'upload' => [
        'max_size'      => 10 * 1024 * 1024, // 10 MB
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'text/plain', 'text/csv',
        ],
    ],

    'pagination' => [
        'per_page' => 20,
    ],
];
