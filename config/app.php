<?php

declare(strict_types=1);


/**
 * Application config — no secrets in this file for now.
 * Later: load DB credentials from environment variables, never commit them.
 */

return [
    // Public URL prefix (must match nginx location)
    'base_path' => '/daily-notes',

    // Absolute filesystem path to project root (parent of public)
    'base_path_fs' => dirname(__DIR__),

    // Show errors only on local machines — never in production
    'debug' => true,
];
