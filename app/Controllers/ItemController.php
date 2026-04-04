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

        Response::render('items/index', [
            'title'     => 'Items',
            'items'     => $items,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => (int) ceil($total / $perPage),
            'filters'   => compact('type', 'status', 'search'),
            'itemTypes' => $itemTypes,
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $itemTypes = require BASE_PATH . '/config/item_types.php';
        Response::render('items/create', ['title' => 'Add Item', 'itemTypes' => $itemTypes]);
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
                     'bed_length_m', 'bed_width_m', 'garden_area_m2', 'line_crop_mix', 'cover_crop_type', 'mobile_asset_history_enabled'];
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

        $meta        = $db->fetchAll('SELECT meta_key, meta_value_text FROM item_meta WHERE item_id = ?', [$id]);
        $attachments = $db->fetchAll("SELECT * FROM attachments WHERE item_id = ? AND status = 'active'", [$id]);
        $activityLog = $db->fetchAll('SELECT * FROM activity_log WHERE item_id = ? ORDER BY performed_at DESC LIMIT 20', [$id]);
        $reminders   = $db->fetchAll("SELECT * FROM reminders WHERE item_id = ? AND status = 'pending' ORDER BY due_at ASC", [$id]);
        $harvests    = $db->fetchAll('SELECT * FROM harvest_entries WHERE item_id = ? ORDER BY recorded_at DESC LIMIT 10', [$id]);
        $finances    = $db->fetchAll('SELECT * FROM finance_entries WHERE item_id = ? ORDER BY entry_date DESC LIMIT 10', [$id]);

        $metaMap = [];
        foreach ($meta as $m) { $metaMap[$m['meta_key']] = $m['meta_value_text']; }

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

        $meta     = $db->fetchAll('SELECT meta_key, meta_value_text FROM item_meta WHERE item_id = ?', [$id]);
        $metaMap  = [];
        foreach ($meta as $m) { $metaMap[$m['meta_key']] = $m['meta_value_text']; }

        $actions  = $db->fetchAll('SELECT * FROM activity_log WHERE item_id = ? ORDER BY performed_at DESC', [$id]);
        $harvests = $db->fetchAll('SELECT * FROM harvest_entries WHERE item_id = ? ORDER BY recorded_at DESC', [$id]);
        $finances = $db->fetchAll('SELECT * FROM finance_entries WHERE item_id = ? ORDER BY entry_date DESC', [$id]);
        $reminders= $db->fetchAll('SELECT * FROM reminders WHERE item_id = ? ORDER BY due_at DESC', [$id]);

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

        $lines[] = '';
        $lines[] = '=== ACTIONS & OBSERVATIONS ===';
        if (empty($actions)) {
            $lines[] = 'No actions recorded.';
        } else {
            foreach ($actions as $a) {
                $date = date('Y-m-d', strtotime($a['performed_at']));
                $line = $date . ' | ' . ($a['action_label'] ?? $a['action_type']);
                if (!empty($a['description'])) $line .= ' — ' . $a['description'];
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

        $itemTypes = require BASE_PATH . '/config/item_types.php';
        Response::render('items/edit', ['title' => 'Edit ' . e($item['name']), 'item' => $item, 'meta' => $metaMap, 'itemTypes' => $itemTypes]);
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

        $id          = (int) ($params['id'] ?? 0);
        $actionKey   = $request->post('action_type', '');
        $description = trim((string) $request->post('description', ''));
        $db          = DB::getInstance();

        $db->execute(
            'INSERT INTO activity_log (item_id, action_type, action_label, description, performed_by, performed_at) VALUES (?,?,?,?,?,NOW())',
            [$id, $actionKey, ucfirst(str_replace('_', ' ', $actionKey)), $description, $_SESSION['user_id']]
        );

        if ($request->isAjax()) {
            Response::json(['success' => true, 'message' => 'Action logged.']);
        }
        flash('success', 'Action logged.');
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
            "SELECT * FROM attachments WHERE item_id = ? AND status = 'active' ORDER BY uploaded_at DESC",
            [$id]
        );
        // Keep only the latest attachment per category
        $byCategory = [];
        foreach ($attachments as $att) {
            if (!isset($byCategory[$att['category']])) {
                $byCategory[$att['category']] = $att;
            }
        }

        Response::render('items/photos', [
            'title'      => 'Photos — ' . $item['name'],
            'item'       => $item,
            'byCategory' => $byCategory,
        ]);
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
