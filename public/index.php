<?php

/**
 * Application entry point.
 * All requests are routed through here.
 * Web server root must be set to the /public directory.
 */

declare(strict_types=1);

// Standard deployment: app root is one level above public/
// cPanel subdirectory deployment: app files are in sibling folder rooted-files/
if (file_exists(dirname(__DIR__) . '/bootstrap/init.php')) {
    define('BASE_PATH', dirname(__DIR__));
} else {
    define('BASE_PATH', dirname(__DIR__) . '/rooted-files');
}

// APP_BASE is the URL subdirectory prefix (e.g. '/rooted' or '' for root installs).
// Detected from SCRIPT_NAME so no config is needed.
define('APP_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));

require BASE_PATH . '/bootstrap/init.php';

use App\Support\Router;
use App\Support\Request;

$router  = new Router();
$request = new Request();

// Load route definitions
require BASE_PATH . '/config/routes.php';

// Dispatch
$router->dispatch($request->method(), $request->uri());
