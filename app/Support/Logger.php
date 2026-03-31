<?php

namespace App\Support;

/**
 * Simple file-based PSR-style logger.
 *
 * Channels:
 *   - app   → LOG_FILE env var
 *   - error → ERROR_LOG_FILE env var  (used for error + critical)
 *
 * Log levels (ascending severity):
 *   debug < info < warning < error < critical
 */
class Logger
{
    private const LEVELS = [
        'debug'    => 0,
        'info'     => 1,
        'warning'  => 2,
        'error'    => 3,
        'critical' => 4,
    ];

    private static ?self $instance = null;

    private string $appLogFile;
    private string $errorLogFile;
    private int    $minLevel;

    public function __construct()
    {
        $this->appLogFile   = $this->resolvePath(Env::get('LOG_FILE', BASE_PATH . '/storage/logs/app.log'));
        $this->errorLogFile = $this->resolvePath(Env::get('ERROR_LOG_FILE', BASE_PATH . '/storage/logs/error.log'));
        $levelName          = strtolower((string) Env::get('LOG_LEVEL', 'error'));
        $this->minLevel     = self::LEVELS[$levelName] ?? self::LEVELS['error'];
    }

    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // -------------------------------------------------------------------------
    // Static convenience methods
    // -------------------------------------------------------------------------

    public static function info(string $msg, array $ctx = []): void
    {
        self::getInstance()->write('info', $msg, $ctx);
    }

    public static function warning(string $msg, array $ctx = []): void
    {
        self::getInstance()->write('warning', $msg, $ctx);
    }

    public static function error(string $msg, array $ctx = []): void
    {
        self::getInstance()->write('error', $msg, $ctx);
    }

    public static function critical(string $msg, array $ctx = []): void
    {
        self::getInstance()->write('critical', $msg, $ctx);
    }

    public static function debug(string $msg, array $ctx = []): void
    {
        self::getInstance()->write('debug', $msg, $ctx);
    }

    // -------------------------------------------------------------------------
    // Core write logic
    // -------------------------------------------------------------------------

    public function write(string $level, string $msg, array $ctx = []): void
    {
        $levelInt = self::LEVELS[$level] ?? 0;

        if ($levelInt < $this->minLevel) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelUp   = strtoupper($level);
        $ctxJson   = empty($ctx) ? '' : ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $line      = "[{$timestamp}] [{$levelUp}] {$msg}{$ctxJson}" . PHP_EOL;

        // Write to app log for all levels
        $this->writeToFile($this->appLogFile, $line);

        // Also write to error log for error and critical
        if ($levelInt >= self::LEVELS['error']) {
            $this->writeToFile($this->errorLogFile, $line);
        }
    }

    private function writeToFile(string $filePath, string $line): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Resolve a relative path relative to BASE_PATH.
     */
    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || (strlen($path) > 1 && $path[1] === ':')) {
            return $path; // Absolute path
        }

        // Handle ../relative paths
        return realpath(BASE_PATH . '/' . $path) ?: BASE_PATH . '/' . ltrim($path, './');
    }

    /**
     * Re-instantiate (useful after env is loaded).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
