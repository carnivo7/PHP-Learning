<?php

declare(strict_types=1);

/**
 * Route table - single place that maps URL path to controllers.
 *
 * Keys are paths AFTER the /daily-notes prefix.
 * 'middleware' => ['auth] means "must be logged in".
 * 'middleware' => ['guest] means "only for logged out users".
 */

return [
    // App home - required login; guest get redirected to /login
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => [\App\Controllers\HomeController::class, 'index'],
        'middleware' => ['auth'],
    ],

    // Login page - Dayfold UI (guest only)
    [
        'method' => 'GET',
        'path' => '/login',
        'handler' => [\App\Controllers\AuthController::class, 'showLogin'],
        'middleware' => ['guest'],
    ],

    [
        'method' => 'POST',
        'path' => '/login',
        'handler' => [\App\Controllers\AuthController::class, 'handleLogin'],
        'middleware' => ['guest'],
    ],

    [
        'method' => 'GET',
        'path' => '/logout',
        'handler' => [\App\Controllers\AuthController::class, 'logout'],
        'middleware' => ['auth'],
    ],

    [
        'method' => 'GET',
        'path' => '/register',
        'handler' => [\App\Controllers\AuthController::class, 'showRegister'],
        'middleware' => ['guest'],
    ]
];
