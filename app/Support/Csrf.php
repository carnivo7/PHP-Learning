<?php

declare(strict_types=1);

namespace App\Support;


/**
 * Synchronizer CSRF tokens (session-bound).
 *
 * Lifecycle:
 * - token()     → read or create current token
 * - rotate()    → destroy current, mint a new one (call AFTER a successful validate)
 * - validate()  → hash_equals only (does not rotate)
 * - validateAndConsume() → validate then rotate (one-time use for that POST)
 *
 * AJAX: on JSON errors, return the new token so the client can update <input name="_token">.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    /** Return the current token; create one if missing. */
    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || $token === '') {
            return self::rotate();
        }
        return $token;
    }

    /**
     * Mint a new token and store it. Previous token becomes invalid.
     * Call after a state-changing request was authenticated as CSRF-valid,
     * and when rendering a fresh login page if you want per-load tokens.
     */
    public static function rotate(): string
    {
        $token = bin2hex(random_bytes(32)); // 256-bit
        Session::put(self::KEY, $token);
        return $token;
    }

    /** Hidden field for classic HTML forms. */
    public static function field(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $t . '" autocomplete="off">';
    }

    /** Constant-time compare. Does not rotate. */
    public static function validate(?string $submitted): bool
    {
        $expected = Session::get(self::KEY);
        if (!is_string($expected) || !is_string($submitted) || $submitted === '') {
            return false;
        }

        // hash_equals mitigates timing leaks on the token itself
        return hash_equals($expected, $submitted);
    }

    /**
     * Validate then rotate (consume). Returns false if invalid (token unchanged).
     * Prefer this on POST login / logout / create-task.
     */
    public static function validateAndConsume(?string $submitted): bool
    {
        if (!self::validate($submitted)) {
            return false;
        }

        self::rotate();
        return true;
    }
}
