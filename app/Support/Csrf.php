<?php

declare(strict_types=1);

namespace App\Support;

/**
 * CSRF tokens — required on every state-changing POST (login, logout, create task).
*/
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
        }
        return $token;
    }

    public static function field(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $t . '">';
    }

    public static function validate(?string $submitted): bool
    {
        $expected = Session::get(self::KEY);
        if (!is_string($expected) || !is_string($submitted) || $submitted === '') {
            return false;
        }
        return hash_equals($expected, $submitted);
    }
}
