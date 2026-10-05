<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Session;

/**
 * Gatekeepers for routes.
 * - requireLogin: used on "/" - guests go to login
 * - redirectIfAuthenticated: used on "/login" - authenticated users go to home
 */
final class AuthMiddleware
{
    public static function requireLogin(string $basePath): void
    {
        if (!Session::get('user_id')) {
            // Not logged in -> login page
            header('Location: ' . rtrim($basePath, '/') . '/login', true, 302);
            exit;
        }
    }

    public static function redirectIfAuthenticated(string $basePath): void
    {
        if (Session::get('user_id')) {
            header('Location: ' . rtrim($basePath, '/') . '/', true, 302);
            exit;
        }
    }
}
