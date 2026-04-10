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

        if (!function_exists('imagecreatetruecolor')) {
            flash('error', 'GD library is not available on this server.');
            Response::redirect('/settings/pwa');
        }

        $file = $_FILES['icon_source'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Upload failed — please try again.');
            Response::redirect('/settings/pwa');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            flash('error', 'Only PNG, JPG, or WebP images are accepted.');
            Response::redirect('/settings/pwa');
        }

        // Load source image
        $src = match ($mime) {
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
            default      => false,
        };

        if (!$src) {
            flash('error', 'Could not read the uploaded image. Make sure it is a valid PNG/JPG/WebP file.');
            Response::redirect('/settings/pwa');
        }

        $imgDir = PUBLIC_PATH . '/assets/images/';
        $sizes  = [512, 192, 180, 32];
        $names  = [
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
            flash('error', 'Some icons failed to save: ' . implode(', ', $errors));
        } else {
            flash('success', 'Icon uploaded and all sizes generated successfully.');
        }

        Response::redirect('/settings/pwa');
    }
}
