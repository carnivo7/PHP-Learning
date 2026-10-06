<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\View;
use App\Support\Session;

/**
 * Protected home ("today"). Auth middleware already ran before this.
 * Real task UI comes later — for now prove redirect + auth gate work.
 */

final class HomeController
{
    public function __construct(private array $config)
    {}

    public function index(): void
    {
        View::render('notes', [
            'basePath' => rtrim($this->config['base_path'], '/'),
        ]);
    }
}
