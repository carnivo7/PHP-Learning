<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Csrf;
use App\Support\Session;
use App\Support\View;
use App\Support\Input;

/**
 * Login / logout.
 * Real password check (password_verify + DB) comes in the next step.
 * For now: demo login so you can test redirect + session safely.
 */
final class AuthController
{
    public function __construct(private array $config)
    {}

    public function showLogin(): void
    {
        // New token on each full page render (refresh / first visit)
        Csrf::rotate();

        View::render('auth.login', [
            'basePath' => rtrim($this->config['base_path'], '/'),
            'error' => Session::get('flash_error'),
            'oldEmail' => Session::get('old_email', ''),
            'csrfField' => Csrf::field(),
        ]);
        Session::forget('flash_error');
        Session::forget('old_email');
    }

    public function handleLogin(): void
    {
        $base = rtrim($this->config['base_path'], '/');
        $wantsJson  = $this->wantsJson();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')  {
            $this->fail($wantsJson, 405, 'Method Not Allowed.', $base);
        }

        $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($len > 8192) {
            $this->fail($wantsJson, 413, 'Request Entity Too Large.', $base);
        }

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if ($contentType !== ''
            && !str_contains($contentType, 'application/x-www-form-urlencoded')
            && !str_contains($contentType, 'multipart/form-data')) {
            $this->fail($wantsJson, 415, 'Unsupported media type.', $base);
        }

        $token = $_POST['_token'] ?? null;
        // 1) CSRF - reject forged cross-site posts
        if (is_array($token) || !Csrf::validate(is_string($token) ? $token : null)) {
            // Invalid CSRF: still rotate so a stolen old token is useless,
            // and hand the client a usable fresh token for the next try.
            Csrf::rotate();
            $this->fail($wantsJson, 419, 'Invalid session token. Please try again.', $base);
        }

        // Sanitize whole POST bag (or just the fields you need)
        $input = Input::fromRequest('post');
        $email = is_string($input['email'] ?? null) ? strtolower(trim($input['email'])) : '';

        // Passwords: do NOT strip/normalize heavily — validate length only.
        // Prefer reading password from raw POST, then password_verify.
        // Never log passwords. Never htmlspecialchars passwords into HTML.
        $passwordRaw = $_POST['password'] ?? '';
        if (is_array($passwordRaw)) {
            $this->fail($wantsJson, 400, 'Invalid request.', $base);
        }

        $password = (string) $passwordRaw; // never trim passwords

        if ($email === '' || $password === '' || mb_strlen($password) > 128
            || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail($wantsJson, 422, 'Those credentials do not match our records.', $base);
        }

        // DEMO ONLY — replace with PDO + password_verify
        $ok = ($email === 'demo@dayfold.test' && $password === 'ChangeMe123!');

        if (!$ok) {
            // Token already consumed above; fail() will expose the new Csrf::token()
            $this->fail($wantsJson, 401, 'Those credentials do not match our records.', $base);
        }

        // Full session id rotation after privilege change
        Session::regenerate();
        // Optional: mint CSRF again after regenerate for any next POST on home
        Csrf::rotate();

        Session::put('user_id', 1);
        Session::put('user_email', $email);

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            echo json_encode([
                'ok' => true,
                'redirect' => $base . '/',
                // not required after redirect, but harmless
                'csrf' => Csrf::token(),
            ], JSON_THROW_ON_ERROR);
            exit;
        }

        header('Location: ' . $base . '/', true, 302);
        exit;
    }

    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return str_contains($accept, 'application/json') || strcasecmp($xhr, 'XMLHttpRequest') === 0;
    }

    public function logout(): void
    {
        $base = rtrim($this->config['base_path'], '/');

        $token = $_POST['_token'] ?? null;
        if (is_array($token) || !Csrf::validate(is_string($token) ? $token : null)) {
            http_response_code(419);
            header('Location: ' . $base . '/', true, 302);
            exit;
        }

        Session::destroy();
        header('Location: ' . $base . '/login', true, 302);
        exit;
    }

    private function fail(bool $wantsJson, int $status, string $message, string $base): never
    {
        if ($wantsJson) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            // Always send a fresh CSRF so the next AJAX attempt works
            echo json_encode([
                'ok' => false,
                'message' => $message,
                'csrf' => Csrf::token(),
            ], JSON_THROW_ON_ERROR);
            exit;
        }

        Session::put('flash_error', $message);
        header('Location: ' . $base . '/login', true, 302);
        exit;
    }

    public function showRegister(): void
    {
        View::render('auth.register', [
            'basePath' => rtrim($this->config['base_path'], '/'),
            'error' => Session::get('flash_error'),
            'oldFirstName' => Session::get('old_first_name', ''),
            'oldLastName' => Session::get('old_last_name', ''),
            'oldEmail' => Session::get('old_email', ''),
            'oldPhone' => Session::get('old_phone', ''),
            'csrfField' => Csrf::field(),
        ]);

        Session::forget('flash_error');
        Session::forget('old_first_name');
        Session::forget('old_last_name');
        Session::forget('old_email');
        Session::forget('old_phone');
    }
}
