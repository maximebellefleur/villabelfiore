<?php
/**
 * Standalone PHP-CLI cron — recomputes seeds.projected_seed_prod_count.
 *
 * Usage (cPanel Cron Jobs):
 *   /usr/local/bin/php /home/<user>/public_html/<path>/rooted-files/cron-seed-counts.php
 *
 * The correct full path for your installation is shown in
 * Settings → ⏰ Cron inside the admin panel.
 *
 * This script is CLI-only and does not require an HTTP request or cron key.
 */

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

define('BASE_PATH',    __DIR__);
define('STORAGE_PATH', BASE_PATH . '/storage');
define('APP_PATH',     BASE_PATH . '/app');

// Minimal PSR-4 autoloader (no session, no routing needed)
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP_PATH . '/Support/Env.php';
\App\Support\Env::load(BASE_PATH . '/.env');
require_once APP_PATH . '/Support/Helpers.php';

date_default_timezone_set((string) env('APP_TIMEZONE', 'Europe/Rome'));

$db    = \App\Support\DB::getInstance();
\App\Support\GardenSchema::ensure($db);
$count = \App\Support\GardenHelpers::recalcAllSeedsProjected($db);

echo date('c') . " [OK] {$count} seed(s) recomputed.\n";
exit(0);
