<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Simple view renderer - Views stay dumb HTML + escaped PHP.
 */
final class View
{
    public static function render(string $view, array $data = []): void
    {
        // Prevent variable injection from unexpected keys later if you expand this
        extract($data, EXTR_SKIP);

        $file = dirname(__DIR__). '/Views/'. str_replace('.', '/', $view). '.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'View not found: '.$view;
            exit;
        }

        require_once $file;
    }

    /** Escape output — always use this when echoing user or DB data. */
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
