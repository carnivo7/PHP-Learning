<?php

declare(strict_types=1);

/**
 * Front Controller - every /daily-notes/* request enters here (via nginx).
 * Do not put business logic here; only bootstrap + dispatch.
 */

use App\Middleware\AuthMiddleware;
use App\Support\Session;
use App\Support\Env;
use App\Support\Database;

define('BASE_PATH', dirname(__DIR__));

// --- Autoload (simple PSR-4 style without Composer for now) ---
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    /**
     * For example, if the $class is App\Support\Env, the $prefix is App\.
     * So, if the $class does not start with $prefix, we return.
     */
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    /**
     * Replace the "\" with DIRECTORY_SEPARATOR("/") and concatenate with BASE_PATH and the rest of the path.
     * For example, if the $class is App\Support\Env, the $relative is Support\Env.
     * So, the $file is BASE_PATH/app/Support/Env.php.
     */
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = BASE_PATH. '/app/'. $relative . '.php';
    /**
     * If the $file is a file, we require it.
     */
    if (is_file($file)) {
        require_once $file;
    }
});

$config = require_once BASE_PATH. '/config/app.php';

Env::load(BASE_PATH . '/.env');
Database::boot($config['debug']);

// Hardening: never leak errors to the browser in production
ini_set('display_errors', $config['debug'] ? '1' : '0');
error_reporting(E_ALL);

// Security headers (baseline)
/**
 * Prevent the browser from sniffing the content type and rendering it as something else than the declared one.
 */
header('X-Content-Type-Options: nosniff');
/**
 * Prevent the browser from framing the page in an iframe.
 */
header('X-Frame-Options: DENY');

/**
 * Prevent the browser from sending the Referer header to the server.
 */
header('Referrer-Policy: no-referrer');

Session::start();

// --- Parse path (strip /daily-notes) ---
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ? : '/';
$base = rtrim($config['base_path'], '/');

$path = $uri;
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ? : '/';
}

$path = '/' .trim($path, '/');
if ($path !== '/') {
    // keep as /login, /logout, etc.
    $path = '/'. trim($path, '/');
} else {
    $path = '/';
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$routes = require_once BASE_PATH. '/config/routes.php';
$matched = null;

foreach ($routes as $route) {
    if ($route['method'] === $method && $route['path'] === $path) {
        $matched = $route;
        break;
    }
}

if ($matched === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    exit;
}

// Run middleware (auth / guest)
$middleware = $matched['middleware'] ?? [];
foreach ($middleware as $name) {
    if ($name === 'auth') {
        AuthMiddleware::requireLogin($config['base_path']);
    }
    if ($name === 'guest') {
        AuthMiddleware::redirectIfAuthenticated($config['base_path']);
    }
}

[$class, $action] = $matched['handler'];
$controller = new $class($config);
$controller->$action();
