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

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id          = (int) ($params['id'] ?? 0);
        $db          = DB::getInstance();
        $item        = $db->fetchOne('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$item) { http_response_code(404); echo '<h1>Item not found</h1>'; return; }
        $attachments = $db->fetchAll("SELECT * FROM attachments WHERE item_id = ? AND status = 'active' ORDER BY uploaded_at DESC", [$id]);
        Response::render('items/attachments', ['title' => 'Attachments', 'item' => $item, 'attachments' => $attachments]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $itemId   = (int) ($params['id'] ?? 0);
        $file     = $request->file('file');
        $isAjax   = $request->isAjax() || $request->post('_ajax', '') === '1';
        $redirect = $request->post('_redirect', '/items/' . $itemId . '/photos');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errMsgs = [
                UPLOAD_ERR_INI_SIZE   => 'The photo is too large (server limit). Try a smaller image.',
                UPLOAD_ERR_FORM_SIZE  => 'The photo exceeds the allowed size.',
                UPLOAD_ERR_PARTIAL    => 'Upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_FILE    => 'No file was selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server error: missing temp folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server error: cannot write file.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
            ];
            $msg = $errMsgs[$file['error'] ?? -1] ?? 'Upload failed. Please try again.';
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg);
            Response::redirect($redirect);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedMimes, true)) {
            $msg = 'File type not allowed. Upload a JPEG, PNG, WebP, or PDF.';
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg);
            Response::redirect($redirect);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uuid     = sprintf('%08x-%04x-%04x-%04x-%012s',
            random_int(0, 0xffffffff),
            random_int(0, 0xffff),
            random_int(0x4000, 0x4fff),
            random_int(0x8000, 0xbfff),
            bin2hex(random_bytes(6))
        );
        $stored   = date('Ymd_Hi') . '_' . $itemId . '_' . substr($uuid, 0, 8) . '.' . $ext;
        $dir      = STORAGE_PATH . '/uploads/items/';
        $destPath = $dir . $stored;

        if (!is_dir($dir)) { mkdir($dir, 0755, true); }
        if (!is_writable($dir)) {
            $msg = 'Server error: uploads directory is not writable.';
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg);
            Response::redirect($redirect);
        }
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $msg = 'Could not save the file. Please try again.';
            if ($isAjax) { Response::json(['success' => false, 'error' => $msg]); }
            flash('error', $msg);
            Response::redirect($redirect);
        }

        $db = DB::getInstance();
        $db->execute(
            'INSERT INTO attachments (uuid, item_id, category, original_filename, stored_filename, mime_type, extension, storage_driver, storage_path, file_size_bytes, is_primary, uploaded_at, uploaded_by, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),?,?)',
            [
                $uuid, $itemId,
                $request->post('category', 'general_attachment'),
                $file['name'], $stored, $mime, $ext,
                'local', 'uploads/items/' . $stored,
                $file['size'],
                0, $_SESSION['user_id'], 'active',
            ]
        );

        $db->execute(
            'INSERT INTO activity_log (item_id, action_type, action_label, description, performed_by, performed_at) VALUES (?,?,?,?,?,NOW())',
            [$itemId, 'image_uploaded', 'Image Uploaded', 'File "' . $file['name'] . '" uploaded.', $_SESSION['user_id']]
        );

        $attId = $db->lastInsertId();
        if ($isAjax) {
            Response::json(['success' => true, 'message' => 'Photo uploaded.', 'data' => [
                'id'       => $attId,
                'uuid'     => $uuid,
                'filename' => $stored,
                'url'      => '/attachments/' . $attId . '/download',
                'category' => $request->post('category', 'general_attachment'),
            ]]);
        }
        flash('success', 'Photo uploaded successfully.');
        Response::redirect($redirect);
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

    public function download(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id  = (int) ($params['id'] ?? 0);
        $att = DB::getInstance()->fetchOne('SELECT * FROM attachments WHERE id=?', [$id]);

        if (!$att) { http_response_code(404); echo 'Not found'; return; }

        $path = STORAGE_PATH . '/' . $att['storage_path'];
        if (!file_exists($path)) { http_response_code(404); echo 'File not found'; return; }

        header('Content-Type: ' . $att['mime_type']);
        header('Content-Disposition: attachment; filename="' . $att['original_filename'] . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
