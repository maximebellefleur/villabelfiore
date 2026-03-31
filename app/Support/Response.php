<?php

namespace App\Support;

/**
 * HTTP response helpers.
 */
class Response
{
    /**
     * Render a PHP view template with optional layout.
     *
     * @param string $view   Relative path under resources/views/, e.g. 'auth/login'
     * @param array  $data   Variables extracted into view scope
     * @param int    $status HTTP status code
     */
    public static function render(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);

        $viewFile = BASE_PATH . '/resources/views/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewFile)) {
            Logger::error('View not found', ['view' => $viewFile]);
            self::notFound();
            return;
        }

        // Capture view content
        extract($data, EXTR_SKIP);
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Check if view set a $layout variable; default to 'main'
        $layout = $data['layout'] ?? ($layout ?? 'main');
        $layoutFile = BASE_PATH . '/resources/views/layouts/' . $layout . '.php';

        if (!file_exists($layoutFile)) {
            echo $content;
            return;
        }

        // Render layout with $content injected
        include $layoutFile;
    }

    /**
     * Send a JSON response and exit.
     */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * JSON response in standard envelope format.
     */
    public static function jsonSuccess(mixed $data = null, string $message = 'OK'): void
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data, 'errors' => []]);
    }

    public static function jsonError(string $message, array $errors = [], int $status = 422): void
    {
        self::json(['success' => false, 'message' => $message, 'data' => null, 'errors' => $errors], $status);
    }

    /**
     * Redirect and exit.
     * Internal paths (starting with /) are prefixed with APP_BASE automatically
     * so redirects work whether the app is at / or /rooted/ or any subdir.
     */
    public static function redirect(string $url, int $status = 302): void
    {
        if (str_starts_with($url, '/') && defined('APP_BASE') && APP_BASE !== '') {
            $url = APP_BASE . $url;
        }
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Redirect back to referrer.
     */
    public static function back(string $fallback = '/'): void
    {
        $referrer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        self::redirect($referrer);
    }

    /**
     * Send a 404 response.
     */
    public static function notFound(): void
    {
        http_response_code(404);
        $file = BASE_PATH . '/resources/views/errors/404.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<h1>404 — Not Found</h1>';
        }
        exit;
    }

    /**
     * Send a 403 response.
     */
    public static function forbidden(): void
    {
        http_response_code(403);
        echo '<h1>403 — Forbidden</h1>';
        exit;
    }

    /**
     * Send a 500 response.
     */
    public static function serverError(string $message = 'Internal Server Error'): void
    {
        http_response_code(500);
        echo '<h1>500 — ' . htmlspecialchars($message) . '</h1>';
        exit;
    }
}
