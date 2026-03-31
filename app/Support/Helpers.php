<?php

use App\Support\Env;
use App\Support\CSRF;

// -----------------------------------------------------------------------
// HTML / Output helpers
// -----------------------------------------------------------------------

if (!function_exists('e')) {
    function e(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// -----------------------------------------------------------------------
// URL / Asset helpers
// -----------------------------------------------------------------------

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) Env::get('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $fullPath = BASE_PATH . '/public/assets/' . ltrim($path, '/');
        $version  = file_exists($fullPath) ? '?v=' . filemtime($fullPath) : '';
        return url('assets/' . ltrim($path, '/')) . $version;
    }
}

// -----------------------------------------------------------------------
// Redirect
// -----------------------------------------------------------------------

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }
}

// -----------------------------------------------------------------------
// Flash messages
// -----------------------------------------------------------------------

if (!function_exists('flash')) {
    function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }
}

if (!function_exists('getFlash')) {
    function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

// -----------------------------------------------------------------------
// CSRF helpers
// -----------------------------------------------------------------------

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return CSRF::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return CSRF::getToken();
    }
}

// -----------------------------------------------------------------------
// Old input (form repopulation)
// -----------------------------------------------------------------------

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = $_SESSION['_old_input'] ?? [];
        return $old[$key] ?? $default;
    }
}

if (!function_exists('setOldInput')) {
    function setOldInput(array $data): void
    {
        $_SESSION['_old_input'] = $data;
    }
}

if (!function_exists('clearOldInput')) {
    function clearOldInput(): void
    {
        unset($_SESSION['_old_input']);
    }
}

// -----------------------------------------------------------------------
// Installation check
// -----------------------------------------------------------------------

if (!function_exists('isInstalled')) {
    function isInstalled(): bool
    {
        // Check environment variable first (fastest check)
        if ((string) Env::get('INSTALL_LOCK', 'false') === 'true') {
            return true;
        }

        // Check lock file
        if (file_exists(BASE_PATH . '/storage/installed.lock')) {
            return true;
        }

        return false;
    }
}

// -----------------------------------------------------------------------
// Env / Config access
// -----------------------------------------------------------------------

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return \App\Support\Env::get($key, $default);
    }
}

// -----------------------------------------------------------------------
// Date / Time
// -----------------------------------------------------------------------

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

// -----------------------------------------------------------------------
// Debug
// -----------------------------------------------------------------------

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:1em;margin:1em 0;">';
            echo htmlspecialchars(print_r($var, true), ENT_QUOTES, 'UTF-8');
            echo '</pre>';
        }
        exit;
    }
}
