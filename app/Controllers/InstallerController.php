<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\CSRF;

class InstallerController
{
    public function index(Request $request, array $params = []): void
    {
        if (isInstalled()) {
            Response::redirect('/dashboard');
        }

        // Auto-create storage directories — fixes "storage/ not writable" for most cases
        $this->ensureStorageDirs();

        $checks = $this->runEnvChecks();
        Response::render('installer/welcome', [
            'title'     => 'Install Rooted',
            'checks'    => $checks,
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

        // Import schema — skip statements that already exist (safe to re-run)
        $schemaFile = BASE_PATH . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
                try { $pdo->exec($stmt . ';'); } catch (\PDOException $e) { /* table already exists — skip */ }
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

        // ── 1. Validate form fields ──────────────────────────────────────────
        $name     = trim((string) $request->post('admin_name', ''));
        $email    = trim((string) $request->post('admin_email', ''));
        $password = (string) $request->post('admin_password', '');
        $confirm  = (string) $request->post('admin_password_confirm', '');

        $errors = [];
        if (empty($name))           { $errors['admin_name']             = 'Name is required.'; }
        if (empty($email))          { $errors['admin_email']            = 'Email is required.'; }
        if (strlen($password) < 8)  { $errors['admin_password']         = 'Password must be at least 8 characters.'; }
        if ($password !== $confirm) { $errors['admin_password_confirm'] = 'Passwords do not match.'; }

        if (!empty($errors)) {
            flash('errors', $errors);
            Response::render('installer/finish', ['title' => 'Create Admin Account']);
            return;
        }

        // ── 2. Check session hasn't expired ──────────────────────────────────
        $dbConfig = $_SESSION['installer']['db'] ?? [];
        $land     = $_SESSION['installer']['land'] ?? [];

        if (empty($dbConfig['db_name']) || empty($dbConfig['db_user'])) {
            flash('error', 'Your session expired during installation. Please start again from Step 1.');
            Response::redirect('/install');
        }

        // ── 3. Reconnect to database ─────────────────────────────────────────
        $dsn = "mysql:host={$dbConfig['db_host']};port={$dbConfig['db_port']};dbname={$dbConfig['db_name']};charset=utf8mb4";
        try {
            $pdo = new \PDO($dsn, $dbConfig['db_user'], $dbConfig['db_pass'], [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            flash('error', 'Could not connect to database: ' . $e->getMessage());
            Response::render('installer/finish', ['title' => 'Create Admin Account']);
            return;
        }

        // ── 4. Create admin user ─────────────────────────────────────────────
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare(
                'INSERT INTO users (email, password_hash, display_name, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, 1, NOW(), NOW())'
            )->execute([$email, $hash, $name]);
        } catch (\PDOException $e) {
            // Duplicate email — still continue, account may already exist from a previous attempt
            if ($e->getCode() !== '23000') {
                flash('error', 'Could not create admin account: ' . $e->getMessage());
                Response::render('installer/finish', ['title' => 'Create Admin Account']);
                return;
            }
        }

        // ── 5. Save land settings ────────────────────────────────────────────
        try {
            $this->saveSetting($pdo, 'app.name',     $land['land_name'] ?? 'Rooted',       'text');
            $this->saveSetting($pdo, 'app.timezone',  $land['timezone']  ?? 'Europe/Rome',  'text');
            $this->saveSetting($pdo, 'app.language',  $land['language']  ?? 'en',           'text');
            $this->saveSetting($pdo, 'app.currency',  $land['currency']  ?? 'EUR',          'text');
            $this->saveSetting($pdo, 'app.installed', '1',                                  'number');
        } catch (\PDOException $e) {
            flash('error', 'Could not save settings: ' . $e->getMessage());
            Response::render('installer/finish', ['title' => 'Create Admin Account']);
            return;
        }

        // ── 6. Ensure storage directories exist ──────────────────────────────
        $this->ensureStorageDirs();

        // ── 7. Write lock file ───────────────────────────────────────────────
        $lockPath    = BASE_PATH . '/storage/installed.lock';
        $lockWritten = @file_put_contents($lockPath, date('Y-m-d H:i:s'));
        if ($lockWritten === false) {
            flash('error',
                'Installation completed but could not write the lock file at storage/installed.lock. ' .
                'Please set storage/ to chmod 755 in cPanel File Manager and click Complete Installation again.'
            );
            Response::render('installer/finish', ['title' => 'Create Admin Account']);
            return;
        }

        // ── 8. Write .env ────────────────────────────────────────────────────
        $envWritten = $this->writeEnv($dbConfig);
        if (!$envWritten) {
            // Not fatal — lock file was written so isInstalled() will return true.
            // App will work but DB credentials are not persisted in .env.
            // Show a warning rather than blocking the user.
            flash('warning',
                'Installation complete, but the .env file could not be written automatically. ' .
                'Please create rooted-files/.env manually — see docs/INSTALL.md for the template.'
            );
        } else {
            flash('success', 'Installation complete. Please sign in.');
        }

        unset($_SESSION['installer']);
        Response::redirect('/login');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Create all required storage subdirectories if they don't exist.
     * Called at both step 1 (env check) and finish, so storage is always ready.
     */
    private function ensureStorageDirs(): void
    {
        $dirs = [
            BASE_PATH . '/storage',
            BASE_PATH . '/storage/logs',
            BASE_PATH . '/storage/uploads',
            BASE_PATH . '/storage/uploads/generated-icons',
            BASE_PATH . '/storage/backups',
            BASE_PATH . '/storage/exports',
            BASE_PATH . '/storage/cache',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    private function runEnvChecks(): array
    {
        $storagePath  = BASE_PATH . '/storage';
        $storageOk    = is_dir($storagePath) && is_writable($storagePath);
        $envTargetDir = BASE_PATH;
        $envWritable  = is_writable($envTargetDir) || file_exists($envTargetDir . '/.env');

        return [
            [
                'check'  => 'PHP >= 8.2',
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'detail' => PHP_VERSION,
                'fix'    => '',
            ],
            [
                'check'  => 'PDO MySQL',
                'passed' => extension_loaded('pdo_mysql'),
                'detail' => '',
                'fix'    => 'Enable the pdo_mysql extension in your PHP settings.',
            ],
            [
                'check'  => 'JSON',
                'passed' => extension_loaded('json'),
                'detail' => '',
                'fix'    => '',
            ],
            [
                'check'  => 'Mbstring',
                'passed' => extension_loaded('mbstring'),
                'detail' => '',
                'fix'    => 'Enable the mbstring extension in your PHP settings.',
            ],
            [
                'check'  => 'storage/ writable',
                'passed' => $storageOk,
                'detail' => $storagePath,
                'fix'    => 'In cPanel File Manager, right-click the storage/ folder → Change Permissions → 755, apply recursively.',
            ],
            [
                'check'  => '.env writable',
                'passed' => $envWritable,
                'detail' => $envTargetDir,
                'fix'    => 'In cPanel File Manager, right-click the rooted-files/ folder → Change Permissions → 755.',
            ],
        ];
    }

    private function saveSetting(\PDO $pdo, string $key, string $value, string $type): void
    {
        $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
             VALUES (?, ?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE setting_value_text = VALUES(setting_value_text), updated_at = NOW()'
        )->execute([$key, $value, $type]);
    }

    /**
     * Write (or update) the .env file with DB credentials and INSTALL_LOCK=true.
     * Returns true on success, false on failure.
     */
    private function writeEnv(array $db): bool
    {
        $target  = BASE_PATH . '/.env';
        $example = BASE_PATH . '/.env.example';

        if (file_exists($target)) {
            $content = file_get_contents($target);
        } elseif (file_exists($example)) {
            $content = file_get_contents($example);
        } else {
            $content =
                "APP_NAME=Rooted\n" .
                "APP_ENV=production\n" .
                "APP_DEBUG=false\n" .
                "APP_URL=\n" .
                "APP_KEY=\n\n" .
                "DB_HOST=localhost\n" .
                "DB_PORT=3306\n" .
                "DB_NAME=rooted\n" .
                "DB_USER=root\n" .
                "DB_PASS=\n\n" .
                "SESSION_LIFETIME=7200\n" .
                "SESSION_NAME=rooted_session\n\n" .
                "STORAGE_DRIVER=local\n" .
                "STORAGE_PATH=../storage/uploads\n\n" .
                "LOG_LEVEL=error\n" .
                "LOG_FILE=../storage/logs/app.log\n" .
                "ERROR_LOG_FILE=../storage/logs/error.log\n\n" .
                "INSTALL_LOCK=false\n";
        }

        $content = preg_replace('/^DB_HOST=.*/m',      'DB_HOST='      . $db['db_host'], $content);
        $content = preg_replace('/^DB_PORT=.*/m',      'DB_PORT='      . $db['db_port'], $content);
        $content = preg_replace('/^DB_NAME=.*/m',      'DB_NAME='      . $db['db_name'], $content);
        $content = preg_replace('/^DB_USER=.*/m',      'DB_USER='      . $db['db_user'], $content);
        $content = preg_replace('/^DB_PASS=.*/m',      'DB_PASS='      . $db['db_pass'], $content);
        $content = preg_replace('/^INSTALL_LOCK=.*/m', 'INSTALL_LOCK=true', $content);

        if (!str_contains($content, 'INSTALL_LOCK=')) {
            $content .= "\nINSTALL_LOCK=true\n";
        }

        return @file_put_contents($target, $content) !== false;
    }
}
