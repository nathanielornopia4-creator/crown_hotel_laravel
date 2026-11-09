<?php

use Illuminate\Support\Str;

return [

    // Session Driver
    'driver' => env('SESSION_DRIVER', 'file'),

    // Session Lifetime
    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    // Expire Session When Browser Closes
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    // Encrypt Session Data
    'encrypt' => env('SESSION_ENCRYPT', false),

    // Session File Storage Path
    'files' => storage_path('framework/sessions'),

    // Database Connection
    'connection' => env('SESSION_CONNECTION'),

    // Database Session Table
    'table' => env('SESSION_TABLE', 'sessions'),

    // Session Store
    'store' => env('SESSION_STORE'),

    // Session Lottery
    'lottery' => [2, 100],

    // Session Cookie Name
    'cookie' => env('SESSION_COOKIE', Str::slug((string) env('APP_NAME', 'laravel')) . '-session'),

    // Session Cookie Path
    'path' => env('SESSION_PATH', '/'),

    // Session Cookie Domain
    'domain' => env('SESSION_DOMAIN'),

    // Secure Cookie
    'secure' => env('SESSION_SECURE_COOKIE'),

    // HTTP Only Cookie
    'http_only' => true,

    // Same-Site Cookie Policy
    'same_site' => 'lax',

    // Partitioned Cookie
    'partitioned' => false,

];