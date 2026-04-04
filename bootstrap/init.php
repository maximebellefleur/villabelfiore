<?php

/**
 * Bootstrap — sets up the application environment.
 * Required by public/index.php before any route is dispatched.
 */

declare(strict_types=1);

// -------------------------------------------------------------------------
// Path constants
// -------------------------------------------------------------------------

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
define('PUBLIC_PATH',  BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('APP_PATH',     BASE_PATH . '/app');

// -------------------------------------------------------------------------
// Autoloader (PSR-4: App\ → /app/)
// -------------------------------------------------------------------------

spl_autoload_register(function (string $class): void {
    $prefix   = 'App\\';
    $baseDir  = APP_PATH . '/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// -------------------------------------------------------------------------
// Load environment
// -------------------------------------------------------------------------

require_once APP_PATH . '/Support/Env.php';
\App\Support\Env::load(BASE_PATH . '/.env');

// -------------------------------------------------------------------------
// Load helpers (global functions)
// -------------------------------------------------------------------------

require_once APP_PATH . '/Support/Helpers.php';

// -------------------------------------------------------------------------
// Error reporting
// -------------------------------------------------------------------------

$debug = (bool) env('APP_DEBUG', false);
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// -------------------------------------------------------------------------
// Timezone
// -------------------------------------------------------------------------

date_default_timezone_set((string) env('APP_TIMEZONE', 'Europe/Rome'));

// -------------------------------------------------------------------------
// Session
// -------------------------------------------------------------------------

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');

$sessionName     = (string) env('SESSION_NAME', 'rooted_session');
$sessionLifetime = (int) env('SESSION_LIFETIME', 7200);

session_name($sessionName);
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------------------------
// Exception + error handler (log to file, show generic message in prod)
// -------------------------------------------------------------------------

set_exception_handler(function (Throwable $e): void {
    \App\Support\Logger::critical('Uncaught exception: ' . $e->getMessage(), [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => substr($e->getTraceAsString(), 0, 2000),
    ]);

    $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
           || ($_POST['_ajax'] ?? '') === '1';

    http_response_code(500);
    if ($isAjax) {
        header('Content-Type: application/json');
        $msg = (bool) env('APP_DEBUG', false)
            ? $e->getMessage()
            : 'Server error — please try again.';
        echo json_encode(['success' => false, 'error' => $msg]);
    } elseif ((bool) env('APP_DEBUG', false)) {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES) . '</pre>';
    } else {
        echo '<h1>Something went wrong. Please try again.</h1>';
    }
    exit(1);
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    \App\Support\Logger::error("PHP error [{$errno}]: {$errstr}", [
        'file' => $errfile,
        'line' => $errline,
    ]);
    return true;
});
