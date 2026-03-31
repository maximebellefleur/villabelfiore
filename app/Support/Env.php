<?php

namespace App\Support;

/**
 * Simple .env file loader and accessor.
 */
class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    /**
     * Load a .env file from the given path.
     * Skips blank lines and comments (#).
     * Handles KEY=VALUE and KEY="VALUE" formats.
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip blank lines and comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Must contain an = sign
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Strip surrounding quotes (single or double)
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Remove inline comments (value # comment)
            // Only strip if there is a space before the #
            if (preg_match('/^([^#]*)\s+#.*$/', $value, $m)) {
                $value = trim($m[1]);
            }

            self::$values[$key] = $value;

            // Also expose to $_ENV and putenv for compatibility
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    /**
     * Get an env value by key, with optional default.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$values)) {
            return self::castValue(self::$values[$key]);
        }

        $env = getenv($key);
        if ($env !== false) {
            return self::castValue($env);
        }

        if (isset($_ENV[$key])) {
            return self::castValue($_ENV[$key]);
        }

        return $default;
    }

    /**
     * Cast string values to appropriate PHP types.
     */
    private static function castValue(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }

    /**
     * Return all loaded values (raw strings, no casting).
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::$values;
    }
}
