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

        $itemId = (int) ($params['id'] ?? 0);
        $file   = $request->file('file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'File upload failed.');
            Response::redirect('/items/' . $itemId);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedMimes, true)) {
            flash('error', 'File type not allowed.');
            Response::redirect('/items/' . $itemId);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uuid     = bin2hex(random_bytes(16));
        $stored   = date('dmY_Hi') . '_' . $itemId . '_' . $uuid . '.' . $ext;
        $dir      = STORAGE_PATH . '/uploads/items/';
        $destPath = $dir . $stored;

        if (!is_dir($dir)) { mkdir($dir, 0755, true); }
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            flash('error', 'Could not save file.');
            Response::redirect('/items/' . $itemId);
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

        if ($request->isAjax()) {
            Response::json(['success' => true, 'message' => 'File uploaded.', 'data' => ['filename' => $stored]]);
        }
        flash('success', 'File uploaded.');
        Response::redirect('/items/' . $itemId);
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
