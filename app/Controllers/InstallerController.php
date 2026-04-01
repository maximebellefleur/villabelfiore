<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\CSRF;
use App\Support\DB;
use App\Support\Env;

class InstallerController
{
    public function index(Request $request, array $params = []): void
    {
        if (isInstalled()) {
            Response::redirect('/dashboard');
        }

        $checks = $this->runEnvChecks();
        Response::render('installer/welcome', [
            'title'  => 'Install Rooted',
            'checks' => $checks,
            'allPassed' => array_reduce($checks, fn($c, $i) => $c && $i['passed'], true),
        ]);
    }

    public function step1(Request $request, array $params = []): void
    {
        CSRF::validate($request->post('_token', ''));
        $_SESSION['installer']['step1_done'] = true;
        Response::render('installer/step2', ['title' => 'Database Setup']);
    }

    public function step2(Request $request, array $params = []): void
    {
        CSRF::validate($request->post('_token', ''));

        $data = [
            'db_host' => trim((string) $request->post('db_host', 'localhost')),
            'db_port' => trim((string) $request->post('db_port', '3306')),
            'db_name' => trim((string) $request->post('db_name', '')),
            'db_user' => trim((string) $request->post('db_user', '')),
            'db_pass' => (string) $request->post('db_pass', ''),
        ];

        $errors = [];
        if (empty($data['db_name'])) { $errors['db_name'] = 'Database name is required.'; }
        if (empty($data['db_user'])) { $errors['db_user'] = 'Database user is required.'; }

        if (!empty($errors)) {
            flash('errors', $errors);
            flash('old', $data);
            Response::render('installer/step2', ['title' => 'Database Setup']);
            return;
        }

        // Test connection
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4";
        try {
            $pdo = new \PDO($dsn, $data['db_user'], $data['db_pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $e) {
            flash('error', 'Database connection failed: ' . $e->getMessage());
            flash('old', $data);
            Response::render('installer/step2', ['title' => 'Database Setup']);
            return;
        }

        // Import schema
        $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
        if ($schema) {
            // Execute statement by statement
            foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
                if ($stmt !== '') {
                    try { $pdo->exec($stmt . ';'); } catch (\PDOException $e) { /* skip duplicates */ }
                }
            }
        }

        $_SESSION['installer']['db'] = $data;
        Response::render('installer/step3', ['title' => 'Land Identity']);
    }

    public function step3(Request $request, array $params = []): void
    {
        CSRF::validate($request->post('_token', ''));

        $data = [
            'land_name' => trim((string) $request->post('land_name', '')),
            'timezone'  => trim((string) $request->post('timezone', 'Europe/Rome')),
            'language'  => trim((string) $request->post('language', 'en')),
            'currency'  => trim((string) $request->post('currency', 'EUR')),
        ];

        if (empty($data['land_name'])) {
            flash('error', 'Land name is required.');
            flash('old', $data);
            Response::render('installer/step3', ['title' => 'Land Identity']);
            return;
        }

        $_SESSION['installer']['land'] = $data;
        Response::render('installer/step4', ['title' => 'Storage Setup']);
    }

    public function step4(Request $request, array $params = []): void
    {
        CSRF::validate($request->post('_token', ''));
        $_SESSION['installer']['storage'] = [
            'driver' => (string) $request->post('storage_driver', 'local'),
        ];
        Response::render('installer/step5', ['title' => 'Integrations (Optional)']);
    }

    public function step5(Request $request, array $params = []): void
    {
        CSRF::validate($request->post('_token', ''));
        $_SESSION['installer']['integrations'] = [];
        Response::render('installer/finish', ['title' => 'Create Admin Account']);
    }

    public function finish(Request $request, array $params = []): void
    {
        CSRF::validate($request->post('_token', ''));

        $name     = trim((string) $request->post('admin_name', ''));
        $email    = trim((string) $request->post('admin_email', ''));
        $password = (string) $request->post('admin_password', '');
        $confirm  = (string) $request->post('admin_password_confirm', '');

        $errors = [];
        if (empty($name))                { $errors['admin_name']  = 'Name is required.'; }
        if (empty($email))               { $errors['admin_email'] = 'Email is required.'; }
        if (strlen($password) < 8)       { $errors['admin_password'] = 'Password must be at least 8 characters.'; }
        if ($password !== $confirm)      { $errors['admin_password_confirm'] = 'Passwords do not match.'; }

        if (!empty($errors)) {
            flash('errors', $errors);
            Response::render('installer/finish', ['title' => 'Create Admin Account']);
            return;
        }

        $dbConfig = $_SESSION['installer']['db'] ?? [];
        $land     = $_SESSION['installer']['land'] ?? [];

        // Reconnect with installer credentials
        $dsn = "mysql:host={$dbConfig['db_host']};port={$dbConfig['db_port']};dbname={$dbConfig['db_name']};charset=utf8mb4";
        try {
            $pdo = new \PDO($dsn, $dbConfig['db_user'], $dbConfig['db_pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            flash('error', 'Could not reconnect to database: ' . $e->getMessage());
            Response::render('installer/finish', ['title' => 'Create Admin Account']);
            return;
        }

        // Create admin user
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO users (email, password_hash, display_name, is_active, created_at, updated_at) VALUES (?,?,?,1,NOW(),NOW())')
            ->execute([$email, $hash, $name]);

        // Save land settings
        $this->saveSetting($pdo, 'app.name',      $land['land_name'] ?? 'Rooted', 'text');
        $this->saveSetting($pdo, 'app.timezone',   $land['timezone']  ?? 'Europe/Rome', 'text');
        $this->saveSetting($pdo, 'app.language',   $land['language']  ?? 'en', 'text');
        $this->saveSetting($pdo, 'app.currency',   $land['currency']  ?? 'EUR', 'text');
        $this->saveSetting($pdo, 'app.installed',  '1', 'number');

        // Write .env file with INSTALL_LOCK=true
        $this->writeEnv($dbConfig);

        // Write lock file — primary installed check, doesn't depend on .env parse
        file_put_contents(BASE_PATH . '/storage/installed.lock', date('Y-m-d H:i:s'));

        unset($_SESSION['installer']);
        flash('success', 'Installation complete. Please sign in.');
        Response::redirect('/login');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function runEnvChecks(): array
    {
        return [
            ['check' => 'PHP >= 8.2', 'passed' => version_compare(PHP_VERSION, '8.2.0', '>='), 'detail' => PHP_VERSION],
            ['check' => 'PDO MySQL', 'passed' => extension_loaded('pdo_mysql'), 'detail' => ''],
            ['check' => 'JSON',      'passed' => extension_loaded('json'),      'detail' => ''],
            ['check' => 'Mbstring',  'passed' => extension_loaded('mbstring'),  'detail' => ''],
            ['check' => 'storage/ writable', 'passed' => is_writable(BASE_PATH . '/storage'), 'detail' => BASE_PATH . '/storage'],
        ];
    }

    private function saveSetting(\PDO $pdo, string $key, string $value, string $type): void
    {
        $pdo->prepare('INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
                       VALUES (?,?,?,1,NOW())
                       ON DUPLICATE KEY UPDATE setting_value_text=VALUES(setting_value_text), updated_at=NOW()')
            ->execute([$key, $value, $type]);
    }

    private function writeEnv(array $db): void
    {
        $target = BASE_PATH . '/.env';

        // If an existing .env is already there and has credentials, update in place
        // Otherwise build from .env.example, or generate a minimal one
        $example = BASE_PATH . '/.env.example';
        if (file_exists($target)) {
            $content = file_get_contents($target);
        } elseif (file_exists($example)) {
            $content = file_get_contents($example);
        } else {
            // Generate minimal .env from scratch
            $content = "APP_DEBUG=false\nAPP_TIMEZONE=Europe/Rome\nSESSION_NAME=rooted_session\nSESSION_LIFETIME=7200\n"
                     . "DB_HOST=localhost\nDB_PORT=3306\nDB_NAME=rooted\nDB_USER=root\nDB_PASS=\nINSTALL_LOCK=false\n";
        }

        $content = preg_replace('/^DB_HOST=.*/m',      'DB_HOST=' . $db['db_host'], $content);
        $content = preg_replace('/^DB_PORT=.*/m',      'DB_PORT=' . $db['db_port'], $content);
        $content = preg_replace('/^DB_NAME=.*/m',      'DB_NAME=' . $db['db_name'], $content);
        $content = preg_replace('/^DB_USER=.*/m',      'DB_USER=' . $db['db_user'], $content);
        $content = preg_replace('/^DB_PASS=.*/m',      'DB_PASS=' . $db['db_pass'], $content);
        $content = preg_replace('/^INSTALL_LOCK=.*/m', 'INSTALL_LOCK=true', $content);

        // If INSTALL_LOCK line didn't exist yet, append it
        if (!str_contains($content, 'INSTALL_LOCK=')) {
            $content .= "\nINSTALL_LOCK=true\n";
        }

        file_put_contents($target, $content);
    }
}
