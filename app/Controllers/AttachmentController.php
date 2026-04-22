<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class AttachmentController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    /** Lazily add the caption column if the table was created before v1.7.0 */
    private function ensureCaptionColumn(DB $db): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attachments' AND COLUMN_NAME = 'caption'"
            );
            if (($row['cnt'] ?? 0) == 0) {
                $db->execute("ALTER TABLE attachments ADD COLUMN caption VARCHAR(500) NULL DEFAULT NULL AFTER category");
            }
        } catch (\Throwable $e) { /* non-fatal */ }
    }

    /** Return custom categories already in use (not in the built-in list). */
    private function customCategories(DB $db): array
    {
        $builtin = ['identification_photo','yearly_refresh_north','yearly_refresh_south',
                    'yearly_refresh_east','yearly_refresh_west','harvest_photo',
                    'treatment_photo','general_attachment'];
        $rows = $db->fetchAll(
            "SELECT DISTINCT category FROM attachments
             WHERE status='active' AND category NOT IN (" . implode(',', array_fill(0, count($builtin), '?')) . ")
             ORDER BY category ASC
             LIMIT 20",
            $builtin
        );
        return array_column($rows, 'category');
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id  = (int) ($params['id'] ?? 0);
        $db  = DB::getInstance();
        $this->ensureCaptionColumn($db);
        $item = $db->fetchOne('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$item) { http_response_code(404); echo '<h1>Item not found</h1>'; return; }
        $attachments    = $db->fetchAll("SELECT * FROM attachments WHERE item_id = ? AND (status = 'active' OR status IS NULL) ORDER BY uploaded_at DESC", [$id]);
        $customCategories = $this->customCategories($db);
        Response::render('items/photos', ['title' => 'Photos', 'item' => $item, 'attachments' => $attachments, 'customCategories' => $customCategories]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $itemId  = (int) ($params['id'] ?? 0);
        $isAjax  = $request->isAjax() || $request->post('_ajax', '') === '1';
        $redirect = $request->post('_redirect', '/items/' . $itemId . '/photos');

        // Detect post_max_size overflow (PHP empties $_FILES when exceeded)
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMax       = $this->iniBytes(ini_get('post_max_size'));
        if ($contentLength > 0 && $postMax > 0 && $contentLength > $postMax) {
            $mb = round($postMax / 1048576);
            $msg = "Photo exceeds server limit ({$mb} MB). It was compressed — try a lower-resolution image.";
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg); Response::redirect($redirect);
        }

        $file = $request->file('file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errMsgs = [
                UPLOAD_ERR_INI_SIZE   => 'Photo too large for server. Try a smaller image.',
                UPLOAD_ERR_FORM_SIZE  => 'Photo exceeds allowed size.',
                UPLOAD_ERR_PARTIAL    => 'Upload interrupted — please try again.',
                UPLOAD_ERR_NO_FILE    => 'No file received.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server error: missing temp folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server error: cannot write to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by server.',
            ];
            $code = $file['error'] ?? -1;
            $msg  = $errMsgs[$code] ?? "Upload error (code {$code}).";
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg); Response::redirect($redirect);
        }

        // MIME detection — finfo preferred, extension fallback
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        } catch (\Throwable $e) {
            $extMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
                       'gif'=>'image/gif','webp'=>'image/webp','pdf'=>'application/pdf'];
            $mime = $extMap[$ext] ?? 'application/octet-stream';
        }

        $allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
        if (!in_array($mime, $allowed, true)) {
            $msg = 'File type not allowed. Upload a JPEG, PNG, WebP, or PDF.';
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg); Response::redirect($redirect);
        }

        // Storage
        $dir      = STORAGE_PATH . '/uploads/items/';
        $uuid     = bin2hex(random_bytes(16));
        $stored   = date('Ymd_Hi') . '_' . $itemId . '_' . substr($uuid, 0, 8) . '.' . ($ext ?: 'jpg');
        $destPath = $dir . $stored;

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $msg = 'Server error: could not create uploads folder. Contact admin.';
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg); Response::redirect($redirect);
        }
        if (!is_writable($dir)) {
            $msg = 'Server error: uploads folder is not writable. Contact admin.';
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg); Response::redirect($redirect);
        }
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $msg = 'Could not save the file — please try again.';
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg); Response::redirect($redirect);
        }

        // Database
        try {
            $db = DB::getInstance();
            $this->ensureCaptionColumn($db);

            // Resolve category — handle "other" custom input
            $rawCat   = trim((string)$request->post('category', 'general_attachment'));
            $customCat= trim((string)$request->post('custom_category', ''));
            if ($rawCat === '__custom__' && $customCat !== '') {
                $category = mb_substr($customCat, 0, 100);
            } else {
                $category = $rawCat ?: 'general_attachment';
            }

            $caption = mb_substr(trim((string)$request->post('caption', '')), 0, 500) ?: null;

            $db->execute(
                'INSERT INTO attachments (uuid, item_id, category, caption, original_filename, stored_filename, mime_type, extension, storage_driver, storage_path, file_size_bytes, is_primary, uploaded_at, uploaded_by, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?,?)',
                [
                    $uuid, $itemId, $category, $caption,
                    $file['name'], $stored, $mime, $ext,
                    'local', 'uploads/items/' . $stored,
                    $file['size'], 0, $_SESSION['user_id'], 'active',
                ]
            );
            $db->execute(
                'INSERT INTO activity_log (item_id, action_type, action_label, description, performed_by, performed_at) VALUES (?,?,?,?,?,NOW())',
                [$itemId, 'image_uploaded', 'Image Uploaded', 'File "' . $file['name'] . '" uploaded.', $_SESSION['user_id']]
            );
            $attId = $db->lastInsertId();
        } catch (\Throwable $e) {
            \App\Support\Logger::critical('Attachment DB insert failed: ' . $e->getMessage(), ['item' => $itemId]);
            @unlink($destPath); // clean up the saved file
            $msg = 'Database error saving photo: ' . $e->getMessage();
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg); Response::redirect($redirect);
        }

        if ($isAjax) {
            Response::json(['success' => true, 'message' => 'Photo uploaded.', 'data' => [
                'id'       => $attId,
                'uuid'     => $uuid,
                'filename' => $stored,
                'url'      => '/attachments/' . $attId . '/download',
                'category' => $category,
                'caption'  => $caption,
            ]]);
        }
        flash('success', 'Photo uploaded successfully.');
        Response::redirect($redirect);
    }

    private function iniBytes(string $val): int
    {
        $val  = trim($val);
        $last = strtolower($val[-1] ?? '');
        $num  = (int) $val;
        return match($last) { 'g' => $num * 1073741824, 'm' => $num * 1048576, 'k' => $num * 1024, default => $num };
    }

    public function trash(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute("UPDATE attachments SET status='trashed' WHERE id=?", [$id]);
        flash('success', 'Attachment removed.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/items');
    }

    public function restore(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute("UPDATE attachments SET status='active' WHERE id=?", [$id]);
        flash('success', 'Attachment restored.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/items');
    }

    public function updateCategory(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $isAjax = ($request->header('X-Requested-With') === 'XMLHttpRequest')
               || ($request->post('_ajax') === '1');
        CSRF::validate($request->post('_token', ''));
        $id  = (int) ($params['id'] ?? 0);
        $cat = mb_substr(trim((string) $request->post('category', '')), 0, 100);
        if ($cat === '') {
            if ($isAjax) { Response::json(['success' => false, 'error' => 'Category cannot be empty']); }
            flash('error', 'Category cannot be empty.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/items');
        }
        DB::getInstance()->execute("UPDATE attachments SET category=? WHERE id=?", [$cat, $id]);
        if ($isAjax) { Response::json(['success' => true, 'category' => $cat]); }
        flash('success', 'Category updated.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/items');
    }

    public function updateCaption(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $isAjax = ($request->header('X-Requested-With') === 'XMLHttpRequest')
               || ($request->post('_ajax') === '1');
        CSRF::validate($request->post('_token', ''));
        $id      = (int) ($params['id'] ?? 0);
        $caption = mb_substr(trim((string) $request->post('caption', '')), 0, 500) ?: null;
        $db = DB::getInstance();
        $this->ensureCaptionColumn($db);
        $db->execute("UPDATE attachments SET caption=? WHERE id=?", [$caption, $id]);
        if ($isAjax) { Response::json(['success' => true, 'caption' => $caption]); }
        flash('success', 'Caption updated.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/items');
    }

    public function quickPhotos(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();

        // All active items sorted by name; GPS items first for client-side distance sort
        $items = $db->fetchAll(
            "SELECT i.id, i.name, i.type, i.gps_lat, i.gps_lng
             FROM items i
             WHERE i.status = 'active' AND i.deleted_at IS NULL
             ORDER BY (i.gps_lat IS NOT NULL) DESC, i.name ASC"
        );

        $customCategories = $this->customCategories($db);
        Response::render('photos/quick', [
            'title'            => 'Quick Photos',
            'items'            => $items,
            'customCategories' => $customCategories,
        ]);
    }

    public function download(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id  = (int) ($params['id'] ?? 0);
        $att = DB::getInstance()->fetchOne('SELECT * FROM attachments WHERE id=?', [$id]);

        if (!$att) { http_response_code(404); echo 'Not found'; return; }

        $path = STORAGE_PATH . '/' . $att['storage_path'];
        if (!file_exists($path)) { http_response_code(404); echo 'File not found'; return; }

        $mtime = filemtime($path);
        $etag  = '"' . $mtime . '-' . filesize($path) . '"';

        header('Content-Type: ' . $att['mime_type']);

        $isImage = strpos($att['mime_type'], 'image/') === 0;
        if ($isImage) {
            header('Content-Disposition: inline; filename="' . $att['original_filename'] . '"');
            header('Cache-Control: private, max-age=31536000, immutable');
        } else {
            header('Content-Disposition: attachment; filename="' . $att['original_filename'] . '"');
            header('Cache-Control: private, max-age=86400');
        }

        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('ETag: ' . $etag);

        // Conditional GET — return 304 if browser already has this version
        $ifNoneMatch    = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
        if (($ifNoneMatch && trim($ifNoneMatch) === $etag) ||
            (!$ifNoneMatch && $ifModifiedSince && strtotime($ifModifiedSince) >= $mtime)) {
            http_response_code(304);
            exit;
        }

        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
