<?php

namespace App\Support;

/**
 * Simple regex-based HTTP router.
 *
 * Supports:
 *   - GET / POST route registration
 *   - Path parameters:  /items/{id}
 *   - Handler formats:  'ControllerClass@method'  or  callable
 *   - 404 / 405 responses
 */
class Router
{
    /** @var array<string, array<array{pattern: string, params: string[], handler: mixed}>> */
    private array $routes = [];

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function get(string $path, mixed $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, mixed $handler): void
    {
        [$pattern, $params] = $this->compilePath($path);
        $this->routes[strtoupper($method)][] = [
            'pattern' => $pattern,
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    // -------------------------------------------------------------------------
    // Dispatch
    // -------------------------------------------------------------------------

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri    = parse_url($uri, PHP_URL_PATH) ?? '/';
        $uri    = '/' . trim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        // Check matching routes for this method
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    $params = [];
                    foreach ($route['params'] as $name) {
                        $params[$name] = $matches[$name] ?? '';
                    }
                    $this->callHandler($route['handler'], $params);
                    return;
                }
            }
        }

        // Check if any other method matches the URI (405 vs 404)
        foreach ($this->routes as $m => $routeList) {
            if ($m === $method) {
                continue;
            }
            foreach ($routeList as $route) {
                if (preg_match($route['pattern'], $uri)) {
                    $this->methodNotAllowed();
                    return;
                }
            }
        }

        $this->notFound();
    }

    // -------------------------------------------------------------------------
    // Handler resolution
    // -------------------------------------------------------------------------

    private function callHandler(mixed $handler, array $params): void
    {
        if (is_callable($handler)) {
            $request = new Request();
            call_user_func($handler, $request, $params);
            return;
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);

            // Resolve full class name
            if (!str_contains($class, '\\')) {
                $class = 'App\\Controllers\\' . $class;
            }

            if (!class_exists($class)) {
                Logger::error("Router: controller class not found", ['class' => $class]);
                $this->notFound();
                return;
            }

            $controller = new $class();
            if (!method_exists($controller, $method)) {
                Logger::error("Router: method not found on controller", [
                    'class'  => $class,
                    'method' => $method,
                ]);
                $this->notFound();
                return;
            }

            $request = new Request();
            $controller->$method($request, $params);
            return;
        }

        Logger::error("Router: invalid handler", ['handler' => print_r($handler, true)]);
        $this->notFound();
    }

    // -------------------------------------------------------------------------
    // Path compilation
    // -------------------------------------------------------------------------

    /**
     * Convert a route path like /items/{id} to a named-capture regex.
     *
     * @return array{0: string, 1: string[]}
     */
    private function compilePath(string $path): array
    {
        $params  = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$params) {
            $params[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $path);

        // Escape slashes and anchor
        $pattern = '#^' . $pattern . '$#';

        return [$pattern, $params];
    }

    // -------------------------------------------------------------------------
    // Error responses
    // -------------------------------------------------------------------------

    private function notFound(): void
    {
        http_response_code(404);
        if (file_exists(BASE_PATH . '/resources/views/errors/404.php')) {
            include BASE_PATH . '/resources/views/errors/404.php';
        } else {
            echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>';
            echo '<h1>404 &mdash; Page Not Found</h1>';
            echo '<p>The page you requested could not be found.</p>';
            echo '</body></html>';
        }
    }

    private function methodNotAllowed(): void
    {
        http_response_code(405);
        echo '<!DOCTYPE html><html><head><title>405 Method Not Allowed</title></head><body>';
        echo '<h1>405 &mdash; Method Not Allowed</h1>';
        echo '</body></html>';
    }
}
