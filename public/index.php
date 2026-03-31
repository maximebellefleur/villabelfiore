<?php

/**
 * Application entry point.
 * All requests are routed through here.
 * Web server root must be set to the /public directory.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/bootstrap/init.php';

use App\Support\Router;
use App\Support\Request;

$router  = new Router();
$request = new Request();

// Load route definitions
require BASE_PATH . '/config/routes.php';

// Dispatch
$router->dispatch($request->method(), $request->uri());
