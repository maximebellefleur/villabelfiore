<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class ItemController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::redirect('/login');
        }
    }

    /** Lazily add attachment_id to activity_log if created before v1.8.0 */
    private function ensureLogAttachmentColumn(DB $db): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            $row = $db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_log' AND COLUMN_NAME = 'attachment_id'"
            );
            if (($row['cnt'] ?? 0) == 0) {
                $db->execute("ALTER TABLE activity_log ADD COLUMN attachment_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER description");
            }
        } catch (\Throwable $e) { /* non-fatal */ }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $db      = DB::getInstance();
        $type    = $request->get('type', '');
        $status  = $request->get('status', 'active');
        $search  = $request->get('search', '');
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where  = ['deleted_at IS NULL'];
        $binds  = [];

        if ($status) { $where[] = 'status = ?'; $binds[] = $status; }
        if ($type)   { $where[] = 'type = ?';   $binds[] = $type; }
        if ($search) {
            $where[] = 'name LIKE ?';
            $binds[] = '%' . $search . '%';
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);

        $total = (int) ($db->fetchOne("SELECT COUNT(*) AS cnt FROM items {$whereStr}", $binds)['cnt'] ?? 0);
        $binds[] = $perPage;
        $binds[] = $offset;
        $items = $db->fetchAll("SELECT * FROM items {$whereStr} ORDER BY sort_order ASC, name ASC LIMIT ? OFFSET ?", $binds);

        $itemTypes = require BASE_PATH . '/config/item_types.php';

        // Load identification photo IDs for all items on this page in one query
        $photoMap = [];
        if (!empty($items)) {
            $itemIds = array_column($items, 'id');
            $ph      = implode(',', array_fill(0, count($itemIds), '?'));
            $photos  = $db->fetchAll(
                "SELECT item_id, MIN(id) AS photo_id FROM attachments
                 WHERE item_id IN ({$ph}) AND category = 'identification_photo'
                 AND (status = 'active' OR status IS NULL) AND mime_type LIKE 'image/%'
                 GROUP BY item_id",
                $itemIds
            );
            foreach ($photos as $p) { $photoMap[(int)$p['item_id']] = (int)$p['photo_id']; }
        }

        Response::render('items/index', [
            'title'     => 'Items',
            'items'     => $items,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => (int) ceil($total / $perPage),
            'filters'   => compact('type', 'status', 'search'),
            'itemTypes' => $itemTypes,
            'photoMap'  => $photoMap,
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $itemTypes   = require BASE_PATH . '/config/item_types.php';
        $row         = DB::getInstance()->fetchOne("SELECT setting_value_json FROM settings WHERE setting_key = 'tree_types.custom' LIMIT 1");
        $customTypes = ($row && !empty($row['setting_value_json']))
            ? (json_decode($row['setting_value_json'], true) ?: [])
            : [];
        Response::render('items/create', [
            'title'       => 'Add Item',
            'itemTypes'   => $itemTypes,
            'customTypes' => $customTypes,
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $data   = $request->all();
        $errors = $this->validateItem($data);

        if (!empty($errors)) {
            flash('errors', $errors);
            flash('old', $data);
            Response::redirect('/items/create');
        }

        $db   = DB::getInstance();
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $db->execute(
            'INSERT INTO items (uuid, type, subtype, name, parent_id, status, gps_lat, gps_lng, gps_accuracy, gps_source, is_finance_enabled, is_mobile_asset, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',
            [
                $uuid,
                $data['type'] ?? '',
                $data['subtype'] ?? null,
                $data['name'] ?? '',
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                'active',
                !empty($data['gps_lat'])  ? (float)$data['gps_lat']  : null,
                !empty($data['gps_lng'])  ? (float)$data['gps_lng']  : null,
                !empty($data['gps_accuracy']) ? (float)$data['gps_accuracy'] : null,
                $data['gps_source'] ?? 'manual',
                0, 0,
                $_SESSION['user_id'],
            ]
        );

        $id = (int) $db->lastInsertId();

        // Save meta fields
        $metaKeys = ['variety', 'latin_name', 'estimated_age_years', 'purpose', 'sun_exposure', 'soil_type', 'irrigation_type',
                     'bed_length_m', 'bed_width_m', 'garden_area_m2', 'line_crop_mix', 'cover_crop_type', 'mobile_asset_history_enabled',
                     'tree_type'];
        foreach ($metaKeys as $key) {
            if (isset($data['meta'][$key]) && $data['meta'][$key] !== '') {
                $db->execute(
                    'INSERT INTO item_meta (item_id, meta_key, meta_value_text, value_type, created_at, updated_at)
                     VALUES (?,?,?,?,NOW(),NOW())
                     ON DUPLICATE KEY UPDATE meta_value_text=VALUES(meta_value_text), updated_at=NOW()',
                    [$id, $key, $data['meta'][$key], 'text']
                );
            }
        }

        // Activity log
        $db->execute(
            'INSERT INTO activity_log (item_id, item_type, action_type, action_label, description, performed_by, performed_at)
             VALUES (?,?,?,?,?,?,NOW())',
            [$id, $data['type'] ?? '', 'item_created', 'Item Created', 'Item "' . ($data['name'] ?? '') . '" created.', $_SESSION['user_id']]
        );

        flash('success', 'Item created successfully.');
        Response::redirect('/items/' . $id);
    }

    public function show(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id   = (int) ($params['id'] ?? 0);
        $db   = DB::getInstance();
        $item = $db->fetchOne('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', [$id]);

        if (!$item) {
            http_response_code(404);
            echo '<h1>Item not found</h1>';
            return;
        }

        $this->ensureLogAttachmentColumn($db);
        $meta        = $db->fetchAll('SELECT meta_key, meta_value_text FROM item_meta WHERE item_id = ?', [$id]);
        $attachments = $db->fetchAll("SELECT * FROM attachments WHERE item_id = ? AND (status = 'active' OR status IS NULL)", [$id]);
        $activityLog = $db->fetchAll(
            'SELECT al.*, a.id AS att_id, a.stored_filename AS att_filename, a.mime_type AS att_mime
             FROM activity_log al
             LEFT JOIN attachments a ON a.id = al.attachment_id
             WHERE al.item_id = ? ORDER BY al.performed_at DESC LIMIT 20',
            [$id]
        );
        $reminders   = $db->fetchAll("SELECT * FROM reminders WHERE item_id = ? AND status = 'pending' ORDER BY due_at ASC", [$id]);
        $harvests    = $db->fetchAll('SELECT * FROM harvest_entries WHERE item_id = ? ORDER BY recorded_at DESC LIMIT 10', [$id]);
        $finances    = $db->fetchAll('SELECT * FROM finance_entries WHERE item_id = ? ORDER BY entry_date DESC LIMIT 10', [$id]);

        $metaMap = [];
        foreach ($meta as $m) { $metaMap[$m['meta_key']] = $m['meta_value_text']; }

        // Load boundary GeoJSON if set
        $boundaryRow    = $db->fetchOne(
            "SELECT meta_value_text FROM item_meta WHERE item_id = ? AND meta_key = 'boundary_geojson' LIMIT 1",
            [$id]
        );
        $boundaryGeojson = $boundaryRow['meta_value_text'] ?? null;

        // Load irrigation plan if table exists
        $irrigationPlan = null;
        try {
            $irrigationPlan = $db->fetchOne('SELECT * FROM irrigation_plans WHERE item_id = ? LIMIT 1', [$id]);
        } catch (\Throwable $e) { /* table created on first use */ }

        Response::render('items/show', [
            'title'          => e($item['name']),
            'item'           => $item,
            'meta'           => $metaMap,
            'attachments'    => $attachments,
            'activityLog'    => $activityLog,
            'reminders'      => $reminders,
            'harvests'       => $harvests,
            'finances'       => $finances,
            'miniMapEnabled' => !empty($item['gps_lat']) && !empty($item['gps_lng']),
            'boundaryGeojson'=> $boundaryGeojson,
            'irrigationPlan' => $irrigationPlan,
        ]);
    }

    public function aiPrompt(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id   = (int) ($params['id'] ?? 0);
        $db   = DB::getInstance();
        $item = $db->fetchOne('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', [$id]);

        if (!$item) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
            return;
        }

        $this->ensureLogAttachmentColumn($db);
        $meta     = $db->fetchAll('SELECT meta_key, meta_value_text FROM item_meta WHERE item_id = ?', [$id]);
        $metaMap  = [];
        foreach ($meta as $m) { $metaMap[$m['meta_key']] = $m['meta_value_text']; }

        $actions  = $db->fetchAll(
            'SELECT al.*, a.id AS att_id FROM activity_log al
             LEFT JOIN attachments a ON a.id = al.attachment_id
             WHERE al.item_id = ? ORDER BY al.performed_at DESC',
            [$id]
        );
        $harvests = $db->fetchAll('SELECT * FROM harvest_entries WHERE item_id = ? ORDER BY recorded_at DESC', [$id]);
        $finances = $db->fetchAll('SELECT * FROM finance_entries WHERE item_id = ? ORDER BY entry_date DESC', [$id]);
        $reminders= $db->fetchAll('SELECT * FROM reminders WHERE item_id = ? ORDER BY due_at DESC', [$id]);
        $photos   = $db->fetchAll(
            "SELECT * FROM attachments WHERE item_id = ? AND mime_type LIKE 'image/%'
             AND (status = 'active' OR status IS NULL) ORDER BY uploaded_at DESC",
            [$id]
        );

        // Build absolute base URL for attachment links
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host . APP_BASE;

        $typeLabel = ucwords(str_replace('_', ' ', $item['type']));

        $lines = [];
        $lines[] = 'You are an agricultural assistant. Below is the complete recorded history of one item on my land. Use it as context when I ask you questions.';
        $lines[] = '';
        $lines[] = '=== ITEM: ' . $item['name'] . ' ===';
        $lines[] = 'Type: ' . $typeLabel;
        if (!empty($metaMap['variety']))              $lines[] = 'Variety: '      . $metaMap['variety'];
        if (!empty($metaMap['latin_name']))           $lines[] = 'Latin name: '   . $metaMap['latin_name'];
        if (!empty($metaMap['estimated_age_years']))  $lines[] = 'Estimated age: '. $metaMap['estimated_age_years'] . ' years';
        if (!empty($item['gps_lat']))                 $lines[] = 'GPS: '          . $item['gps_lat'] . ', ' . $item['gps_lng'];
        if (!empty($metaMap['soil_type']))            $lines[] = 'Soil: '         . $metaMap['soil_type'];
        if (!empty($metaMap['irrigation_type']))      $lines[] = 'Irrigation: '   . $metaMap['irrigation_type'];
        if (!empty($metaMap['sun_exposure']))         $lines[] = 'Sun exposure: '  . $metaMap['sun_exposure'];
        if (!empty($item['notes']))                   $lines[] = 'Notes: '        . $item['notes'];

        // Photos overview
        if (!empty($photos)) {
            $lines[] = '';
            $lines[] = '=== PHOTOS ===';
            foreach ($photos as $p) {
                $cat  = ucwords(str_replace('_', ' ', $p['category'] ?? 'general'));
                $line = '[' . $cat . '] ' . $baseUrl . '/attachments/' . (int)$p['id'] . '/download';
                if (!empty($p['caption'])) $line .= ' — ' . $p['caption'];
                $lines[] = $line;
            }
        }

        $lines[] = '';
        $lines[] = '=== ACTIONS & OBSERVATIONS ===';
        if (empty($actions)) {
            $lines[] = 'No actions recorded.';
        } else {
            foreach ($actions as $a) {
                $date = date('Y-m-d', strtotime($a['performed_at']));
                $line = $date . ' | ' . ($a['action_label'] ?? $a['action_type']);
                if (!empty($a['description'])) $line .= ' — ' . $a['description'];
                if (!empty($a['att_id']))       $line .= ' [photo: ' . $baseUrl . '/attachments/' . (int)$a['att_id'] . '/download]';
                $lines[] = $line;
            }
        }

        $lines[] = '';
        $lines[] = '=== HARVEST RECORDS ===';
        if (empty($harvests)) {
            $lines[] = 'No harvest records.';
        } else {
            foreach ($harvests as $h) {
                $date = date('Y-m-d', strtotime($h['recorded_at']));
                $line = $date . ' | ' . $h['quantity'] . ' ' . $h['unit'];
                if (!empty($h['quality_grade']))  $line .= ' | Grade: ' . $h['quality_grade'];
                if (!empty($h['harvest_type']))   $line .= ' | Type: '  . $h['harvest_type'];
                if (!empty($h['notes']))          $line .= ' | '        . $h['notes'];
                $lines[] = $line;
            }
        }

        $lines[] = '';
        $lines[] = '=== FINANCE ===';
        if (empty($finances)) {
            $lines[] = 'No finance records.';
        } else {
            foreach ($finances as $f) {
                $date = date('Y-m-d', strtotime($f['entry_date']));
                $type = ucfirst($f['entry_type'] ?? 'entry');
                $line = $date . ' | ' . $type . ' ' . ($f['currency'] ?? 'EUR') . ' ' . $f['amount'];
                if (!empty($f['label']))  $line .= ' — ' . $f['label'];
                if (!empty($f['notes'])) $line .= ' (' . $f['notes'] . ')';
                $lines[] = $line;
            }
        }

        $lines[] = '';
        $lines[] = '=== REMINDERS ===';
        if (empty($reminders)) {
            $lines[] = 'No reminders recorded.';
        } else {
            foreach ($reminders as $r) {
                $date   = date('Y-m-d', strtotime($r['due_at']));
                $status = strtoupper($r['status'] ?? 'pending');
                $line   = '[' . $status . '] ' . $date . ' — ' . ($r['title'] ?? '');
                if (!empty($r['description'])) $line .= ' — ' . $r['description'];
                $lines[] = $line;
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['prompt' => implode("\n", $lines)]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id   = (int) ($params['id'] ?? 0);
        $db   = DB::getInstance();
        $item = $db->fetchOne('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', [$id]);

        if (!$item) { http_response_code(404); echo '<h1>Item not found</h1>'; return; }

        $meta = $db->fetchAll('SELECT meta_key, meta_value_text FROM item_meta WHERE item_id = ?', [$id]);
        $metaMap = [];
        foreach ($meta as $m) { $metaMap[$m['meta_key']] = $m['meta_value_text']; }

        $itemTypes   = require BASE_PATH . '/config/item_types.php';
        $row         = $db->fetchOne("SELECT setting_value_json FROM settings WHERE setting_key = 'tree_types.custom' LIMIT 1");
        $customTypes = ($row && !empty($row['setting_value_json']))
            ? (json_decode($row['setting_value_json'], true) ?: [])
            : [];
        $boundaryRow = $db->fetchOne(
            "SELECT meta_value_text FROM item_meta WHERE item_id = ? AND meta_key = 'boundary_geojson' LIMIT 1",
            [$id]
        );
        $boundaryGeojson = $boundaryRow['meta_value_text'] ?? null;

        $defaultBoundaryTypes = ['garden', 'bed', 'orchard', 'zone', 'prep_zone', 'mobile_coop', 'building'];
        $btRow       = $db->fetchOne("SELECT setting_value_json FROM settings WHERE setting_key = 'map.boundary_types' LIMIT 1");
        $boundaryTypes = (!empty($btRow['setting_value_json']))
            ? (json_decode($btRow['setting_value_json'], true) ?: $defaultBoundaryTypes)
            : $defaultBoundaryTypes;

        Response::render('items/edit', [
            'title'          => 'Edit ' . e($item['name']),
            'item'           => $item,
            'meta'           => $metaMap,
            'itemTypes'      => $itemTypes,
            'customTypes'    => $customTypes,
            'boundaryTypes'  => $boundaryTypes,
            'boundaryGeojson'=> $boundaryGeojson,
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $id   = (int) ($params['id'] ?? 0);
        $data = $request->all();
        $db   = DB::getInstance();

        $errors = $this->validateItem($data);
        if (!empty($errors)) {
            flash('errors', $errors);
            flash('old', $data);
            Response::redirect('/items/' . $id . '/edit');
        }

        $db->execute(
            'UPDATE items SET name=?, subtype=?, parent_id=?, gps_lat=?, gps_lng=?, gps_accuracy=?, gps_source=?, updated_at=NOW() WHERE id=?',
            [
                $data['name'] ?? '',
                $data['subtype'] ?? null,
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                !empty($data['gps_lat'])   ? (float)$data['gps_lat'] : null,
                !empty($data['gps_lng'])   ? (float)$data['gps_lng'] : null,
                !empty($data['gps_accuracy']) ? (float)$data['gps_accuracy'] : null,
                $data['gps_source'] ?? 'manual',
                $id,
            ]
        );

        // Save meta fields
        $metaKeys = ['variety', 'latin_name', 'estimated_age_years', 'purpose', 'sun_exposure', 'soil_type', 'irrigation_type',
                     'bed_length_m', 'bed_width_m', 'garden_area_m2', 'line_crop_mix', 'cover_crop_type', 'mobile_asset_history_enabled',
                     'tree_type'];
        foreach ($metaKeys as $key) {
            if (isset($data['meta'][$key])) {
                $val = $data['meta'][$key];
                if ($val !== '') {
                    $db->execute(
                        'INSERT INTO item_meta (item_id, meta_key, meta_value_text, value_type, created_at, updated_at)
                         VALUES (?,?,?,?,NOW(),NOW())
                         ON DUPLICATE KEY UPDATE meta_value_text=VALUES(meta_value_text), updated_at=NOW()',
                        [$id, $key, $val, 'text']
                    );
                } else {
                    $db->execute('DELETE FROM item_meta WHERE item_id = ? AND meta_key = ?', [$id, $key]);
                }
            }
        }

        $db->execute(
            'INSERT INTO activity_log (item_id, item_type, action_type, action_label, description, performed_by, performed_at) VALUES (?,?,?,?,?,?,NOW())',
            [$id, $data['type'] ?? '', 'item_updated', 'Item Updated', 'Item "' . ($data['name'] ?? '') . '" updated.', $_SESSION['user_id']]
        );

        flash('success', 'Item updated.');
        Response::redirect('/items/' . $id);
    }

    public function trash(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        $db = DB::getInstance();
        $db->execute("UPDATE items SET status='trashed', deleted_at=NOW(), updated_at=NOW() WHERE id=?", [$id]);
        flash('success', 'Item moved to trash.');
        Response::redirect('/items');
    }

    public function restore(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        $db = DB::getInstance();
        $db->execute("UPDATE items SET status='active', deleted_at=NULL, updated_at=NOW() WHERE id=?", [$id]);
        flash('success', 'Item restored.');
        Response::redirect('/items/' . $id);
    }

    public function archive(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        $db = DB::getInstance();
        $db->execute("UPDATE items SET status='archived', updated_at=NOW() WHERE id=?", [$id]);
        flash('success', 'Item archived.');
        Response::redirect('/items');
    }

    public function actions(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id  = (int) ($params['id'] ?? 0);
        $db  = DB::getInstance();
        $log = $db->fetchAll('SELECT * FROM activity_log WHERE item_id = ? ORDER BY performed_at DESC', [$id]);
        Response::render('items/actions', ['title' => 'Action Log', 'log' => $log, 'item_id' => $id]);
    }

    public function addAction(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $id           = (int) ($params['id'] ?? 0);
        $actionKey    = $request->post('action_type', 'note');
        $customLabel  = trim((string) $request->post('custom_action_label', ''));
        $description  = trim((string) $request->post('description', ''));
        $db           = DB::getInstance();
        $this->ensureLogAttachmentColumn($db);

        $actionStored = $actionKey === 'other' ? 'other' : $actionKey;
        $actionLabel  = ($actionKey === 'other' && $customLabel !== '')
            ? ucfirst($customLabel)
            : ucfirst(str_replace('_', ' ', $actionKey));

        $db->execute(
            'INSERT INTO activity_log (item_id, action_type, action_label, description, performed_by, performed_at) VALUES (?,?,?,?,?,NOW())',
            [$id, $actionStored, $actionLabel, $description, $_SESSION['user_id']]
        );
        $logId = (int) $db->lastInsertId();

        // Optional photos attached to this log (supports multiple via log_photos[])
        if ($logId && $request->post('attach_photo', '0') === '1') {
            $logPhotos = $_FILES['log_photos'] ?? null;
            if ($logPhotos && is_array($logPhotos['name']) && count($logPhotos['name']) > 0) {
                $firstAttId = null;
                $dir        = STORAGE_PATH . '/uploads/items/';
                $finfo      = new \finfo(FILEINFO_MIME_TYPE);
                $allowedMimes = ['image/jpeg','image/png','image/webp','image/gif'];
                for ($fi = 0; $fi < count($logPhotos['name']); $fi++) {
                    if ($logPhotos['error'][$fi] !== UPLOAD_ERR_OK) continue;
                    try {
                        $mime = $finfo->file($logPhotos['tmp_name'][$fi]) ?: 'application/octet-stream';
                        if (!in_array($mime, $allowedMimes, true)) continue;
                        $ext    = strtolower(pathinfo($logPhotos['name'][$fi], PATHINFO_EXTENSION)) ?: 'jpg';
                        $uuid   = bin2hex(random_bytes(16));
                        $stored = date('Ymd_Hi') . '_' . $id . '_' . substr($uuid, 0, 8) . '.' . $ext;
                        if ((is_dir($dir) || mkdir($dir, 0755, true)) && move_uploaded_file($logPhotos['tmp_name'][$fi], $dir . $stored)) {
                            $db->execute(
                                'INSERT INTO attachments (uuid, item_id, category, original_filename, stored_filename, mime_type, extension, storage_driver, storage_path, file_size_bytes, is_primary, uploaded_at, uploaded_by, status)
                                 VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),?,?)',
                                [$uuid, $id, 'log_photo', $logPhotos['name'][$fi], $stored, $mime, $ext, 'local', 'uploads/items/' . $stored, $logPhotos['size'][$fi], 0, $_SESSION['user_id'], 'active']
                            );
                            $attId = (int) $db->lastInsertId();
                            // Link first photo to the log entry (for feed thumbnail)
                            if ($attId && $firstAttId === null) {
                                $firstAttId = $attId;
                                $db->execute('UPDATE activity_log SET attachment_id = ? WHERE id = ?', [$attId, $logId]);
                            }
                        }
                    } catch (\Throwable $e) { /* non-fatal */ }
                }
            }
        }

        // Optional reminder attached to this log
        $setReminder = $request->post('set_reminder', '0') === '1';
        $dueAt       = trim((string) $request->post('reminder_due_at', ''));
        $newReminderId = null;

        if ($setReminder && $dueAt && $description) {
            $reminderTitle = mb_substr($description, 0, 160);
            $db->execute(
                'INSERT INTO reminders (item_id, type, title, due_at, is_recurring, status, created_at, updated_at)
                 VALUES (?,?,?,?,0,?,NOW(),NOW())',
                [$id, 'log_reminder', $reminderTitle, $dueAt, 'pending']
            );
            $newReminderId = (int)$db->lastInsertId();

            // Auto-push to Google Calendar if connected
            if ($newReminderId) {
                try {
                    (new \App\Controllers\CalendarController())->pushReminderById($db, $newReminderId);
                } catch (\Throwable $e) { /* non-fatal */ }
            }
        }

        if ($request->isAjax()) {
            Response::json(['success' => true, 'message' => 'Action logged.']);
        }
        flash('success', 'Action logged.' . ($newReminderId ? ' Reminder set.' : ''));
        Response::redirect('/items/' . $id);
    }

    // -------------------------------------------------------------------------
    // API
    // -------------------------------------------------------------------------

    public function apiNearby(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) { Response::json(['success' => false, 'message' => 'Unauthenticated'], 401); }

        $lat    = (float) $request->get('lat', 0);
        $lng    = (float) $request->get('lng', 0);
        $radius = (float) $request->get('radius', 1.0);

        $db    = DB::getInstance();
        $items = $db->fetchAll(
            "SELECT *, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(gps_lat)) * COS(RADIANS(gps_lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(gps_lat)))) AS distance_km
             FROM items
             WHERE gps_lat IS NOT NULL AND status = 'active' AND deleted_at IS NULL
             HAVING distance_km <= ?
             ORDER BY distance_km ASC
             LIMIT 50",
            [$lat, $lng, $lat, $radius]
        );

        Response::json(['success' => true, 'data' => $items]);
    }

    public function apiShow(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) { Response::json(['success' => false, 'message' => 'Unauthenticated'], 401); }
        $id   = (int) ($params['id'] ?? 0);
        $db   = DB::getInstance();
        $item = $db->fetchOne('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$item) { Response::json(['success' => false, 'message' => 'Not found'], 404); }
        Response::json(['success' => true, 'data' => $item]);
    }

    public function apiStore(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) { Response::json(['success' => false, 'message' => 'Unauthenticated'], 401); }
        CSRF::validate($request->post('_token', ''));

        $data   = $request->all();
        $errors = $this->validateItem($data);
        if (!empty($errors)) {
            Response::json(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        $db   = DB::getInstance();
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $db->execute(
            'INSERT INTO items (uuid, type, subtype, name, parent_id, status, gps_lat, gps_lng, gps_accuracy, gps_source, is_finance_enabled, is_mobile_asset, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',
            [
                $uuid,
                $data['type'] ?? '',
                $data['subtype'] ?? null,
                $data['name'] ?? '',
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                'active',
                !empty($data['gps_lat'])      ? (float)$data['gps_lat']      : null,
                !empty($data['gps_lng'])      ? (float)$data['gps_lng']      : null,
                !empty($data['gps_accuracy']) ? (float)$data['gps_accuracy'] : null,
                $data['gps_source'] ?? 'map',
                0, 0,
                $_SESSION['user_id'],
            ]
        );

        $id = (int) $db->lastInsertId();

        $db->execute(
            'INSERT INTO activity_log (item_id, item_type, action_type, action_label, description, performed_by, performed_at)
             VALUES (?,?,?,?,?,?,NOW())',
            [$id, $data['type'] ?? '', 'item_created', 'Item Created',
             'Item "' . ($data['name'] ?? '') . '" created via map.', $_SESSION['user_id']]
        );

        Response::json(['success' => true, 'message' => 'Item created.', 'data' => [
            'id'      => $id,
            'name'    => $data['name'] ?? '',
            'type'    => $data['type'] ?? '',
            'gps_lat' => !empty($data['gps_lat']) ? (float)$data['gps_lat'] : null,
            'gps_lng' => !empty($data['gps_lng']) ? (float)$data['gps_lng'] : null,
            'status'  => 'active',
        ]]);
    }

    public function apiAddAction(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) { Response::json(['success' => false, 'message' => 'Unauthenticated'], 401); }
        $this->addAction($request, $params);
    }

    public function photos(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id   = (int)($params['id'] ?? 0);
        $db   = DB::getInstance();
        $item = $db->fetchOne('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$item) { http_response_code(404); echo '<h1>Item not found</h1>'; return; }

        $attachments = $db->fetchAll(
            "SELECT * FROM attachments WHERE item_id = ? AND (status = 'active' OR status IS NULL) ORDER BY uploaded_at DESC",
            [$id]
        );

        Response::render('items/photos', [
            'title'       => 'Photos — ' . $item['name'],
            'item'        => $item,
            'attachments' => $attachments,
        ]);
    }

    public function deleteLog(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute('DELETE FROM activity_log WHERE id = ?', [$id]);
        flash('success', 'Log entry deleted.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/items');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    private function validateItem(array $data): array
    {
        $errors = [];
        if (empty($data['type']))  { $errors['type']  = 'Item type is required.'; }
        if (empty($data['name']))  { $errors['name']  = 'Item name is required.'; }
        return $errors;
    }
}
