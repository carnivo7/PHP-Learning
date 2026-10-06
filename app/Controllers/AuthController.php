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

        // 1) CSRF - reject forged cross-site posts
        if (!Csrf::validate($_POST['_token']) ?? null) {
            http_response_code(419);
            Session::put('flash_error', 'Invalid CSRF token. Please try again.');
            header('Location: ' . $base . '/login', true, 302);
            exit;
        }

        // Sanitize whole POST bag (or just the fields you need)
        $input = Input::fromRequest('post');

        $email = trim((string) ($input['email'] ?? ''));
        // Passwords: do NOT strip/normalize heavily — validate length only.
        // Prefer reading password from raw POST, then password_verify.
        // Never log passwords. Never htmlspecialchars passwords into HTML.
        $password = trim((string) ($_POST['password'] ?? ''));

        // 2) Basic validation (expand later)
        if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::put('flash_error', 'Enter a valid email and password.');
            Session::put('old_email', $email);
            header('Location: ' . $base . '/login', true, 302);
            exit;
        }

        /*
         * DEMO ONLY (local) — replace ASAP with:
         *   $user = User::findByEmail($email); // prepared statement
         *   $ok = $user && password_verify($password, $user->password_hash);
         * Never store or log plain passwords.
         */
        $ok = (strtolower($email) === 'demo@dayfold.test' && $password === 'ChangeMe123!');

        if (!$ok) {
            // Generic message — do not reveal whether email exists (user enumeration)
            Session::put('flash_error', 'Those credentials do not match our records.');
            Session::put('old_email', $email);
            header('Location: ' . $base . '/login', true, 302);
            exit;
        }

        Session::regenerate();
        Session::put('user_id', 1);
        Session::put('user_email', $email);

        header('Location: ' . $base . '/', true, 302);
        exit;
    }

    public function logout(): void
    {
        $base = rtrim($this->config['base_path'], '/');

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            header('Location: ' . $base . '/', true, 302);
            exit;
        }

        Session::destroy();
        header('Location: ' . $base . '/login', true, 302);
        exit;
    }
}
