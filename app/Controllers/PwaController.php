<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class PwaController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getSetting(DB $db, string $key, string $default = ''): string
    {
        $row = $db->fetchOne(
            "SELECT setting_value_text FROM settings WHERE setting_key = ? LIMIT 1",
            [$key]
        );
        return $row ? (string)($row['setting_value_text'] ?? $default) : $default;
    }

    private function saveSetting(DB $db, string $key, string $value): void
    {
        $db->execute(
            "INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
             VALUES (?, ?, 'text', 0, NOW())
             ON DUPLICATE KEY UPDATE setting_value_text=VALUES(setting_value_text), updated_at=NOW()",
            [$key, $value]
        );
    }

    // ── Manifest writer ───────────────────────────────────────────────────────

    private function writeManifest(array $cfg): void
    {
        $base = defined('APP_BASE') ? APP_BASE : '';

        $manifest = [
            'name'             => $cfg['name']        ?: 'Rooted',
            'short_name'       => $cfg['short_name']  ?: 'Rooted',
            'description'      => $cfg['description'] ?: 'Land management system for orchards, gardens, and productive land.',
            'display'          => $cfg['display']     ?: 'standalone',
            'orientation'      => $cfg['orientation'] ?: 'portrait-primary',
            'start_url'        => $base . '/' . ltrim($cfg['start_url'] ?: '/dashboard', '/'),
            'theme_color'      => $cfg['theme_color'] ?: '#29402B',
            'background_color' => $cfg['bg_color']    ?: '#F5F0EA',
            'icons'            => [
                [
                    'src'     => $base . '/assets/images/' . ($cfg['icon_192'] ?: 'icon-192.png'),
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src'     => $base . '/assets/images/' . ($cfg['icon_512'] ?: 'icon-512.png'),
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        file_put_contents(
            PUBLIC_PATH . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    // ── Icon generator from uploaded source ──────────────────────────────────

    /**
     * Resize source GD image to $size x $size, save as PNG.
     */
    private function resizeIcon(\GdImage $src, int $size, string $destPath): bool
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);

        $dst = imagecreatetruecolor($size, $size);
        imagesavealpha($dst, true);
        imagealphablending($dst, false);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
        imagealphablending($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $srcW, $srcH);

        $ok = imagepng($dst, $destPath, 9);
        imagedestroy($dst);
        return $ok;
    }

    // ── Controller actions ───────────────────────────────────────────────────

    public function pwa(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db  = DB::getInstance();
        $cfg = [];

        foreach ($db->fetchAll("SELECT setting_key, setting_value_text FROM settings WHERE setting_key LIKE 'pwa.%'") as $r) {
            $cfg[$r['setting_key']] = $r['setting_value_text'];
        }

        Response::render('settings/pwa', ['title' => 'PWA Settings', 'cfg' => $cfg]);
    }

    public function updatePwa(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();

        $keys = [
            'pwa.enabled', 'pwa.name', 'pwa.short_name', 'pwa.description',
            'pwa.theme_color', 'pwa.bg_color', 'pwa.display', 'pwa.orientation',
            'pwa.start_url',
        ];

        foreach ($keys as $key) {
            $postKey = str_replace('.', '_', $key);
            $value   = (string)($request->post($postKey) ?? '');
            $this->saveSetting($db, $key, $value);
        }

        // Regenerate manifest.json on every save
        $this->writeManifest([
            'name'        => $this->getSetting($db, 'pwa.name',        'Rooted'),
            'short_name'  => $this->getSetting($db, 'pwa.short_name',  'Rooted'),
            'description' => $this->getSetting($db, 'pwa.description', ''),
            'theme_color' => $this->getSetting($db, 'pwa.theme_color', '#29402B'),
            'bg_color'    => $this->getSetting($db, 'pwa.bg_color',    '#F5F0EA'),
            'display'     => $this->getSetting($db, 'pwa.display',     'standalone'),
            'orientation' => $this->getSetting($db, 'pwa.orientation', 'portrait-primary'),
            'start_url'   => $this->getSetting($db, 'pwa.start_url',   '/dashboard'),
            'icon_192'    => 'icon-192.png',
            'icon_512'    => 'icon-512.png',
        ]);

        flash('success', 'PWA settings saved and manifest updated.');
        Response::redirect('/settings/pwa');
    }

    public function uploadIcon(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        try {
            if (!function_exists('imagecreatetruecolor')) {
                flash('error', 'GD library is not available on this server. Contact your host to enable the GD extension.');
                Response::redirect('/settings/pwa');
            }

            $file = $_FILES['icon_source'] ?? null;
            if (!$file || ($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
                $errMsgs = [
                    UPLOAD_ERR_INI_SIZE   => 'File too large (exceeds PHP upload_max_filesize).',
                    UPLOAD_ERR_FORM_SIZE  => 'File too large.',
                    UPLOAD_ERR_PARTIAL    => 'Upload interrupted — please try again.',
                    UPLOAD_ERR_NO_FILE    => 'No file selected.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server error: missing temp folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Server error: cannot write temp file.',
                ];
                $code = $file['error'] ?? -1;
                flash('error', $errMsgs[$code] ?? 'Upload failed (code ' . $code . ').');
                Response::redirect('/settings/pwa');
            }

            $tmpName = $file['tmp_name'] ?? '';
            if ($tmpName === '' || !file_exists($tmpName)) {
                flash('error', 'Uploaded file could not be found in temp storage. Check upload_tmp_dir server config.');
                Response::redirect('/settings/pwa');
            }

            // Use finfo for reliable MIME detection
            try {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($tmpName) ?: '';
            } catch (\Throwable $e) {
                $ext  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
                $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? '';
            }

            if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
                flash('error', 'Only PNG, JPG, or WebP images are accepted. Detected type: ' . ($mime ?: 'unknown'));
                Response::redirect('/settings/pwa');
            }

            // Load source image
            $src = false;
            if ($mime === 'image/png') {
                $src = @imagecreatefrompng($tmpName);
            } elseif ($mime === 'image/jpeg') {
                $src = @imagecreatefromjpeg($tmpName);
            } elseif ($mime === 'image/webp') {
                if (!function_exists('imagecreatefromwebp')) {
                    flash('error', 'WebP is not supported by the GD library on this server. Please upload a PNG or JPG instead.');
                    Response::redirect('/settings/pwa');
                }
                $src = @imagecreatefromwebp($tmpName);
            }

            if (!$src) {
                flash('error', 'Could not decode the uploaded image. Please use a valid PNG or JPG file.');
                Response::redirect('/settings/pwa');
            }

            $imgDir = PUBLIC_PATH . '/assets/images/';

            // Ensure the directory exists and is writable
            if (!is_dir($imgDir) && !@mkdir($imgDir, 0755, true)) {
                imagedestroy($src);
                flash('error', 'Cannot create images directory: ' . $imgDir . '. Check folder permissions.');
                Response::redirect('/settings/pwa');
            }
            if (!is_writable($imgDir)) {
                imagedestroy($src);
                flash('error', 'Images directory is not writable: ' . $imgDir . '. Contact your host to fix permissions.');
                Response::redirect('/settings/pwa');
            }

            $sizes = [512, 192, 180, 32];
            $names = [
                512 => 'icon-512.png',
                192 => 'icon-192.png',
                180 => 'apple-touch-icon.png',
                32  => 'favicon-32.png',
            ];

            $errors = [];
            foreach ($sizes as $size) {
                if (!$this->resizeIcon($src, $size, $imgDir . $names[$size])) {
                    $errors[] = $names[$size];
                }
            }

            imagedestroy($src);

            if ($errors) {
                flash('error', 'Some icons failed to save: ' . implode(', ', $errors) . '. Check write permissions on ' . $imgDir);
            } else {
                flash('success', 'Icon uploaded and all sizes generated successfully.');
            }

        } catch (\Throwable $e) {
            \App\Support\Logger::critical('PWA icon upload failed: ' . $e->getMessage(), [
                'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            flash('error', 'Icon upload error: ' . $e->getMessage());
        }

        Response::redirect('/settings/pwa');
    }
}
