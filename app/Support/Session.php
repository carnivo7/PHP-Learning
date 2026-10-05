<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Secure session bootstrap
 * Cookies: HttpOnly, Secure, SameSite=Lax, Secure when HTTPS
 */

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/daily-notes', // limit cookie to /daily-notes/*
            'secure' => $secure,     // true once you've set up HTTPS
            'httponly' => true,     // true for security
            'samesite' => 'lax',    // csrf mitigation for cross-site POSTs
        ]);

        session_name('dayfold_sess');
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Call after successful login to stop session fixation attacks. */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }
}
