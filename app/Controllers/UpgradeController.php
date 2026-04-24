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

        // Never let browser or proxy cache the upgrade page — version info must always be live
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        $defaults  = require BASE_PATH . '/config/defaults.php';
        $changelog = require BASE_PATH . '/config/changelog.php';

        $currentVersion = $defaults['version'] ?? '1.0.0';
        $upgradeLog     = $this->readUpgradeLog();
        $zipSupported   = class_exists('ZipArchive');
        $zipUrl         = $defaults['update_zip_url'] ?? '';

        // Quick check: peek at the remote ZIP to get the latest available version.
        // Uses a HEAD+Range request so we only pull the first ~8KB (enough for defaults.php).
        $latestVersion  = null;
        $versionUrl     = $defaults['update_version_url'] ?? '';
        if ($versionUrl && function_exists('curl_init')) {
            $latestVersion = $this->fetchLatestVersion($versionUrl);
        }

        Response::render('settings/upgrade', [
            'title'          => 'Upgrade',
            'currentVersion' => $currentVersion,
            'currentName'    => $defaults['version_name'] ?? '',
            'changelog'      => $changelog,
            'upgradeLog'     => $upgradeLog,
            'zipSupported'   => $zipSupported,
            'latestVersion'  => $latestVersion,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /settings/upgrade/upload  — always returns JSON
    // -------------------------------------------------------------------------
    public function upload(Request $request, array $params = []): void
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        // CSRF check — use validateToken() so we can return JSON instead of calling exit
        if (!CSRF::validateToken($request->post('_token', ''))) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh the page and try again.']);
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

        $result = $this->extractAndApply($tmpPath);

        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }

        if (!$result['success']) {
            echo json_encode($result);
            return;
        }

        flash('success', 'Upgrade to v' . $result['new_version'] . ' complete. ' . $result['extracted'] . ' files updated.');

        echo json_encode(['success' => true, 'redirect' => '/settings/upgrade']);
    }

    // -------------------------------------------------------------------------
    // POST /settings/upgrade/github  — download & apply latest release from GitHub
    // -------------------------------------------------------------------------
    public function applyFromGitHub(Request $request, array $params = []): void
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        if (!CSRF::validateToken($request->post('_token', ''))) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh the page and try again.']);
            return;
        }

        if (!class_exists('ZipArchive')) {
            echo json_encode(['success' => false, 'message' => 'ZipArchive PHP extension is not available on this server.']);
            return;
        }

        // Download the ZIP directly from the repository
        $defaults = require BASE_PATH . '/config/defaults.php';
        $rawUrl   = $defaults['update_zip_url'] ?? 'https://raw.githubusercontent.com/maximebellefleur/villabelfiore/main/rooted-cpanel-update.zip';

        // Optional GitHub token — set GITHUB_TOKEN in .env for private repos
        $token = $_ENV['GITHUB_TOKEN'] ?? getenv('GITHUB_TOKEN') ?? '';

        $tmpPath = tempnam(sys_get_temp_dir(), 'rooted_gh_update_');

        // Convert raw.githubusercontent.com URL → GitHub API URL to bypass CDN cache.
        // api.github.com with Accept: application/vnd.github.v3.raw always serves fresh content.
        $apiUrl = $this->rawUrlToApiUrl($rawUrl);

        $downloaded = false;
        if (function_exists('curl_init') && $apiUrl) {
            $apiHeaders = [
                'Accept: application/vnd.github.v3.raw',
                'User-Agent: Rooted-Updater/1.0',
                'Cache-Control: no-cache',
            ];
            if ($token) $apiHeaders[] = 'Authorization: token ' . $token;

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER     => $apiHeaders,
            ]);
            $data     = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($data !== false && $httpCode === 200 && strlen($data) > 1000) {
                file_put_contents($tmpPath, $data);
                $downloaded = true;
            } elseif ($httpCode === 404) {
                @unlink($tmpPath);
                $hint = $token ? '' : ' If the repository is private, add GITHUB_TOKEN=your_token to your .env file.';
                echo json_encode(['success' => false, 'message' => 'GitHub returned 404 — the ZIP was not found at the expected URL.' . $hint]);
                return;
            } elseif ($httpCode !== 200) {
                @unlink($tmpPath);
                echo json_encode(['success' => false, 'message' => 'GitHub API returned HTTP ' . $httpCode . ($curlErr ? ': ' . $curlErr : '') . '.']);
                return;
            } else {
                @unlink($tmpPath);
                echo json_encode(['success' => false, 'message' => 'Download failed: ' . ($curlErr ?: 'empty response')]);
                return;
            }
        }

        if (!$downloaded) {
            // Fallback: file_get_contents with SSL context
            $headers = 'User-Agent: Rooted-Updater/1.0' . ($token ? "\r\nAuthorization: token " . $token : '');
            $ctx  = stream_context_create(['http' => ['timeout' => 120, 'header' => $headers, 'follow_location' => true]]);
            $data = @file_get_contents($fetchUrl, false, $ctx);
            if ($data === false || strlen($data) < 1000) {
                @unlink($tmpPath);
                echo json_encode(['success' => false, 'message' => 'Could not download from GitHub. Enable allow_url_fopen or the curl extension.']);
                return;
            }
            file_put_contents($tmpPath, $data);
        }

        // Reuse the extraction logic
        $result = $this->extractAndApply($tmpPath);

        @unlink($tmpPath);

        if (!$result['success']) {
            echo json_encode($result);
            return;
        }

        flash('success', 'Upgrade to v' . $result['new_version'] . ' complete via GitHub. ' . $result['extracted'] . ' files updated.');
        echo json_encode(['success' => true, 'redirect' => '/settings/upgrade']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Open a ZIP at $tmpPath, validate it, extract safe files, write upgrade log.
     * Returns ['success'=>bool, 'message'=>string, 'new_version'=>string, 'extracted'=>int]
     */
    private function extractAndApply(string $tmpPath): array
    {
        $zip    = new \ZipArchive();
        $opened = $zip->open($tmpPath);
        if ($opened !== true) {
            return ['success' => false, 'message' => 'The file is not a valid ZIP archive (code: ' . $opened . ').'];
        }

        if ($zip->numFiles === 0) {
            $zip->close();
            return ['success' => false, 'message' => 'ZIP appears empty or unreadable.'];
        }

        $extractBase = dirname(BASE_PATH);

        if (!is_writable($extractBase)) {
            $zip->close();
            return ['success' => false, 'message' => 'Destination "' . $extractBase . '" is not writable. Check file permissions.'];
        }

        $newDefaults = $this->readPhpArrayFromZip($zip, 'rooted-files/config/defaults.php');
        $newChangelog = $this->readPhpArrayFromZip($zip, 'rooted-files/config/changelog.php');
        $newVersion  = $newDefaults['version']      ?? 'unknown';
        $newName     = $newDefaults['version_name'] ?? '';

        $defaults       = require BASE_PATH . '/config/defaults.php';
        $currentVersion = $defaults['version'] ?? '1.0.0';

        $extracted = [];
        $skipped   = [];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (substr($name, -1) === '/') continue;
                if ($this->isProtected($name)) { $skipped[] = $name; continue; }
                if (!str_starts_with($name, 'rooted/') && !str_starts_with($name, 'rooted-files/')) {
                    $skipped[] = $name;
                    continue;
                }
                $destPath = $extractBase . '/' . $name;
                $destDir  = dirname($destPath);
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                $contents = $zip->getFromIndex($i);
                if ($contents !== false) {
                    file_put_contents($destPath, $contents);
                    $extracted[] = $name;
                }
            }
        } catch (\Throwable $e) {
            $zip->close();
            return ['success' => false, 'message' => 'Extraction failed: ' . $e->getMessage()];
        }

        $zip->close();

        if (count($extracted) === 0) {
            return ['success' => false, 'message' => 'No files were extracted. Check the ZIP has rooted/ and rooted-files/ folders.'];
        }

        // Build new changelog entries
        $newEntries = [];
        if ($newChangelog) {
            foreach ($newChangelog as $ver => $entry) {
                if (version_compare($ver, $currentVersion, '>')) {
                    $newEntries[$ver] = $entry;
                }
            }
        }

        $this->writeUpgradeLog($currentVersion, $newVersion);

        $_SESSION['upgrade_result'] = [
            'from'        => $currentVersion,
            'to'          => $newVersion,
            'to_name'     => $newName,
            'extracted'   => count($extracted),
            'skipped'     => count($skipped),
            'new_entries' => $newEntries,
        ];

        return ['success' => true, 'new_version' => $newVersion, 'extracted' => count($extracted)];
    }

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

    /**
     * Fetch version.json from the remote URL and return the version string.
     * Much faster than downloading the full ZIP.
     */
    private function fetchLatestVersion(string $versionUrl): ?string
    {
        $token    = $_ENV['GITHUB_TOKEN'] ?? getenv('GITHUB_TOKEN') ?? '';
        $fetchUrl = $versionUrl . '?_ts=' . time();

        $ch = curl_init($fetchUrl);
        $headers = ['Cache-Control: no-cache, no-store', 'Pragma: no-cache'];
        if ($token) {
            $headers[] = 'Authorization: token ' . $token;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Rooted-Updater/1.0',
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        $data     = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($data === false || $httpCode !== 200) {
            return null;
        }

        $json = json_decode($data, true);
        return $json['version'] ?? null;
    }

    /**
     * Convert raw.githubusercontent.com/OWNER/REPO/BRANCH/PATH
     * → https://api.github.com/repos/OWNER/REPO/contents/PATH?ref=BRANCH
     * The API with Accept: application/vnd.github.v3.raw bypasses CDN caching.
     */
    private function rawUrlToApiUrl(string $rawUrl): ?string
    {
        if (preg_match('#raw\.githubusercontent\.com/([^/]+)/([^/]+)/([^/]+)/(.+)#', $rawUrl, $m)) {
            return 'https://api.github.com/repos/' . $m[1] . '/' . $m[2]
                 . '/contents/' . $m[4] . '?ref=' . urlencode($m[3]);
        }
        return null;
    }
}
