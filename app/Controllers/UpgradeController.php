<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\CSRF;

class UpgradeController
{
    // Paths inside the ZIP that must never be overwritten
    private const PROTECTED_PATHS = [
        'rooted-files/.env',
        'rooted-files/storage/',
    ];

    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::redirect('/login');
        }
    }

    // -------------------------------------------------------------------------
    // GET /settings/upgrade
    // -------------------------------------------------------------------------
    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $defaults  = require BASE_PATH . '/config/defaults.php';
        $changelog = require BASE_PATH . '/config/changelog.php';

        $currentVersion = $defaults['version'] ?? '1.0.0';
        $upgradeLog     = $this->readUpgradeLog();
        $zipSupported   = class_exists('ZipArchive');

        Response::render('settings/upgrade', [
            'title'          => 'Upgrade',
            'currentVersion' => $currentVersion,
            'currentName'    => $defaults['version_name'] ?? '',
            'changelog'      => $changelog,
            'upgradeLog'     => $upgradeLog,
            'zipSupported'   => $zipSupported,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /settings/upgrade/upload  — always returns JSON
    // -------------------------------------------------------------------------
    public function upload(Request $request, array $params = []): void
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        // CSRF check
        try {
            CSRF::validate($request->post('_token', ''));
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh and try again.']);
            return;
        }

        if (!class_exists('ZipArchive')) {
            echo json_encode(['success' => false, 'message' => 'ZipArchive PHP extension is not available on this server. Use the manual update process instead.']);
            return;
        }

        $file = $_FILES['upgrade_zip'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errCode = $file['error'] ?? -1;
            if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                $msg = 'The uploaded file exceeds the maximum allowed size configured on this server (upload_max_filesize / post_max_size). Ask your host to increase these limits or use the manual update process.';
            } elseif ($errCode === UPLOAD_ERR_NO_FILE) {
                $msg = 'No file was uploaded. Please select the update ZIP and try again.';
            } else {
                $msg = 'Upload failed (error code ' . $errCode . '). Please try again.';
            }
            echo json_encode(['success' => false, 'message' => $msg]);
            return;
        }

        $tmpPath = $file['tmp_name'];

        // Validate it's actually a ZIP
        $zip    = new \ZipArchive();
        $opened = $zip->open($tmpPath);
        if ($opened !== true) {
            echo json_encode(['success' => false, 'message' => 'The uploaded file is not a valid ZIP archive (error code: ' . $opened . ').']);
            return;
        }

        // Empty ZIP check
        if ($zip->numFiles === 0) {
            $zip->close();
            echo json_encode(['success' => false, 'message' => 'ZIP appears empty or unreadable.']);
            return;
        }

        $extractBase = dirname(BASE_PATH); // e.g. public_html/

        // Write-permission pre-check
        if (!is_writable($extractBase)) {
            $zip->close();
            echo json_encode(['success' => false, 'message' => 'Destination directory "' . $extractBase . '" is not writable. Check file permissions and try again.']);
            return;
        }

        // Read the incoming version from config/defaults.php inside the ZIP
        $newDefaults  = $this->readPhpArrayFromZip($zip, 'rooted-files/config/defaults.php');
        $newChangelog = $this->readPhpArrayFromZip($zip, 'rooted-files/config/changelog.php');

        $newVersion  = $newDefaults['version']      ?? 'unknown';
        $newName     = $newDefaults['version_name'] ?? '';

        $defaults       = require BASE_PATH . '/config/defaults.php';
        $currentVersion = $defaults['version'] ?? '1.0.0';

        // Extract all safe files
        $extracted = [];
        $skipped   = [];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                // Skip directories
                if (substr($name, -1) === '/') continue;

                // Skip protected paths
                if ($this->isProtected($name)) {
                    $skipped[] = $name;
                    continue;
                }

                // Only extract known top-level folders
                if (!str_starts_with($name, 'rooted/') && !str_starts_with($name, 'rooted-files/')) {
                    $skipped[] = $name;
                    continue;
                }

                $destPath = $extractBase . '/' . $name;
                $destDir  = dirname($destPath);

                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                $contents = $zip->getFromIndex($i);
                if ($contents !== false) {
                    file_put_contents($destPath, $contents);
                    $extracted[] = $name;
                }
            }
        } catch (\Throwable $e) {
            $zip->close();
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
            echo json_encode(['success' => false, 'message' => 'Extraction failed: ' . $e->getMessage()]);
            return;
        }

        $zip->close();
        // Explicitly delete the uploaded ZIP — don't rely on PHP auto-cleanup
        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }

        // Nothing extracted — likely wrong ZIP structure
        if (count($extracted) === 0) {
            echo json_encode(['success' => false, 'message' => 'No files were extracted. Check that the ZIP has the correct structure (rooted/ and rooted-files/ folders).']);
            return;
        }

        // Log the upgrade
        $this->writeUpgradeLog($currentVersion, $newVersion);

        // Figure out what's new in this version vs current
        $newEntries = [];
        if ($newChangelog) {
            foreach ($newChangelog as $ver => $entry) {
                if (version_compare($ver, $currentVersion, '>')) {
                    $newEntries[$ver] = $entry;
                }
            }
        }

        // Store result in session for display after redirect
        $_SESSION['upgrade_result'] = [
            'from'        => $currentVersion,
            'to'          => $newVersion,
            'to_name'     => $newName,
            'extracted'   => count($extracted),
            'skipped'     => count($skipped),
            'new_entries' => $newEntries,
        ];

        flash('success', 'Upgrade to v' . $newVersion . ' complete. ' . count($extracted) . ' files updated.');

        echo json_encode(['success' => true, 'redirect' => '/settings/upgrade']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isProtected(string $zipPath): bool
    {
        foreach (self::PROTECTED_PATHS as $protected) {
            if ($zipPath === $protected || str_starts_with($zipPath, $protected)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Safely evaluate a PHP file from inside a ZIP that returns an array.
     * We write it to a temp file, include it, then delete.
     */
    private function readPhpArrayFromZip(\ZipArchive $zip, string $path): array
    {
        $contents = $zip->getFromName($path);
        if ($contents === false) return [];

        $tmp = tempnam(sys_get_temp_dir(), 'rooted_upgrade_');
        file_put_contents($tmp, $contents);

        try {
            $result = include $tmp;
        } catch (\Throwable $e) {
            $result = [];
        }

        unlink($tmp);
        return is_array($result) ? $result : [];
    }

    private function readUpgradeLog(): array
    {
        $path = BASE_PATH . '/storage/upgrade.log';
        if (!file_exists($path)) return [];

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entries = [];
        foreach (array_reverse($lines ?: []) as $line) {
            $parts = explode('|', $line, 3);
            if (count($parts) === 3) {
                $entries[] = ['date' => $parts[0], 'from' => $parts[1], 'to' => $parts[2]];
            }
        }
        return array_slice($entries, 0, 10);
    }

    private function writeUpgradeLog(string $from, string $to): void
    {
        $path = BASE_PATH . '/storage/upgrade.log';
        $line = date('Y-m-d H:i:s') . '|' . $from . '|' . $to . PHP_EOL;
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
