<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;
use App\Support\HarvestConfig;

class SettingsController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db       = DB::getInstance();
        $settings = [];
        $rows     = $db->fetchAll('SELECT setting_key, setting_value_text, setting_value_json, value_type FROM settings');
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value_text'];
        }

        // Boundary types (stored as JSON)
        $defaultBoundaryTypes = ['garden', 'bed', 'orchard', 'zone', 'prep_zone', 'mobile_coop', 'building'];
        $btRow = null;
        foreach ($rows as $row) {
            if ($row['setting_key'] === 'map.boundary_types') { $btRow = $row; break; }
        }
        $boundaryTypes = (!empty($btRow['setting_value_json']))
            ? (json_decode($btRow['setting_value_json'], true) ?: $defaultBoundaryTypes)
            : $defaultBoundaryTypes;

        $itemTypes = require BASE_PATH . '/config/item_types.php';

        Response::render('settings/index', [
            'title'         => 'Settings',
            'settings'      => $settings,
            'boundaryTypes' => $boundaryTypes,
            'itemTypes'     => $itemTypes,
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $allowed = [
            'app.name', 'app.owner_name', 'app.currency', 'app.language', 'app.timezone',
            'gps.accuracy_threshold', 'gps.detect_zoom_level', 'image.refresh_interval_days',
            'reminder.default_lead_days', 'integration.google_calendar',
            'integration.weather', 'quote.api_url',
            'ai.endpoint', 'ai.vision_model',
            'ai.mode', 'ai.hf_endpoint', 'ai.hf_model', 'ai.hf_token', 'ai.extra_prompt',
            'ai.codex_token', 'ai.codex_model', 'ai.gemini_model_list',
            'garden.climate_zone',
            'companion_api_provider', 'companion_api_key', 'companion_api_model', 'companion_api_url',
        ];

        $db = DB::getInstance();
        foreach ($allowed as $key) {
            $value = $request->post(str_replace('.', '_', $key));
            if ($value !== null) {
                $db->execute(
                    'INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
                     VALUES (?,?,?,0,NOW())
                     ON DUPLICATE KEY UPDATE setting_value_text=VALUES(setting_value_text), updated_at=NOW()',
                    [$key, $value, 'text']
                );
            }
        }

        flash('success', 'Settings saved.');
        Response::redirect('/settings');
    }

    public function clearImageCache(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $ver = (string) time();
        DB::getInstance()->execute(
            "INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
             VALUES ('att_cache_ver',?,?,0,NOW())
             ON DUPLICATE KEY UPDATE setting_value_text=VALUES(setting_value_text), updated_at=NOW()",
            [$ver, 'text']
        );
        flash('success', 'Image cache cleared. Browsers will reload fresh images.');
        Response::redirect('/settings');
    }

    // ── Harvest settings ──────────────────────────────────────────────────────

    public function harvest(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $itemTypes     = require BASE_PATH . '/config/item_types.php';
        $harvestConfig = HarvestConfig::get();

        Response::render('settings/harvest', [
            'title'         => 'Harvest Settings',
            'itemTypes'     => $itemTypes,
            'harvestConfig' => $harvestConfig,
        ]);
    }

    public function updateHarvest(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $itemTypes = require BASE_PATH . '/config/item_types.php';
        $config    = [];

        foreach ($itemTypes as $typeKey => $typeCfg) {
            $config[$typeKey] = [
                'enabled'         => $request->post('enabled_' . $typeKey) === '1' ? 1 : 0,
                'max_per_year'    => max(1,    (int)   $request->post('max_per_year_' . $typeKey, 1)),
                'unit'            => (trim((string) $request->post('unit_' . $typeKey, 'units')) ?: 'units'),
                'slider_max'      => max(0.25, (float) $request->post('slider_max_'  . $typeKey, 5)),
                'slider_step'     => max(0.05, (float) $request->post('slider_step_' . $typeKey, 0.25)),
                'finance_enabled' => $request->post('finance_enabled_' . $typeKey) === '1' ? 1 : 0,
                'finance_rule'    => trim((string) $request->post('finance_rule_' . $typeKey, '')),
            ];
        }

        DB::getInstance()->execute(
            "INSERT INTO settings (setting_key, setting_value_json, value_type, autoload, updated_at)
             VALUES (?, ?, 'json', 0, NOW())
             ON DUPLICATE KEY UPDATE setting_value_json=VALUES(setting_value_json), updated_at=NOW()",
            ['harvest.type_config', json_encode($config)]
        );

        flash('success', 'Harvest settings saved.');
        Response::redirect('/settings/harvest');
    }

    // ── Custom tree types ─────────────────────────────────────────────────────

    public function addTreeType(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $label = trim((string) $request->post('label', ''));
        if (strlen($label) < 2 || strlen($label) > 64) {
            Response::json(['success' => false, 'error' => 'Name must be 2–64 characters.']);
        }

        // Generate slug: lowercase, letters/digits only, underscores
        $slug = 'custom_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($label));
        $slug = trim($slug, '_');

        $db  = DB::getInstance();
        $row = $db->fetchOne("SELECT setting_value_json FROM settings WHERE setting_key = 'tree_types.custom' LIMIT 1");
        $existing = ($row && !empty($row['setting_value_json']))
            ? (json_decode($row['setting_value_json'], true) ?: [])
            : [];

        // Check for duplicate slug
        foreach ($existing as $t) {
            if ($t['key'] === $slug) {
                Response::json(['success' => true, 'key' => $slug, 'label' => $t['label'], 'existed' => true]);
            }
        }

        $existing[] = ['key' => $slug, 'label' => $label];
        $db->execute(
            "INSERT INTO settings (setting_key, setting_value_json, value_type, autoload, updated_at)
             VALUES ('tree_types.custom', ?, 'json', 0, NOW())
             ON DUPLICATE KEY UPDATE setting_value_json=VALUES(setting_value_json), updated_at=NOW()",
            [json_encode($existing)]
        );

        Response::json(['success' => true, 'key' => $slug, 'label' => $label]);
    }

    // ── Other settings pages ──────────────────────────────────────────────────

    public function storage(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $targets = DB::getInstance()->fetchAll('SELECT * FROM storage_targets ORDER BY id ASC');
        Response::render('settings/storage', ['title' => 'Storage Settings', 'targets' => $targets]);
    }

    public function updateStorage(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        flash('success', 'Storage settings saved.');
        Response::redirect('/settings/storage');
    }

    public function actionTypes(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $types = DB::getInstance()->fetchAll('SELECT * FROM action_types ORDER BY action_label ASC');
        Response::render('settings/action-types', ['title' => 'Action Types', 'types' => $types]);
    }

    public function updateActionTypes(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        flash('success', 'Action types saved.');
        Response::redirect('/settings/action-types');
    }

    public function upcoming(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $tasks = self::loadMergedTasks();
        usort($tasks, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        $steps = self::readSteps();
        usort($steps, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        Response::render('settings/upcoming', ['title' => 'Task Log', 'tasks' => $tasks, 'steps' => $steps]);
    }

    // POST /settings/tasks/{id}/status  — cycle or set explicit status
    public function taskStatus(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id     = (int)($params['id'] ?? 0);
        $status = $request->post('status', '');
        $note   = trim($request->post('note', ''));

        $tasks    = self::loadMergedTasks();
        $statuses = self::readStatuses();
        $cycle    = ['empty', 'done', 'error', 'trigger_ai'];
        foreach ($tasks as &$t) {
            if ((int)$t['id'] !== $id) continue;
            if ($status && in_array($status, $cycle, true)) {
                $t['status'] = $status;
            } else {
                $cur = array_search($t['status'] ?? 'empty', $cycle, true);
                if ($cur === false) $cur = 0;
                $t['status'] = $cycle[($cur + 1) % count($cycle)];
            }
            if ($note !== '') $t['note'] = $note;
            if ($t['status'] === 'done' && empty($t['resolved_at'])) {
                $t['resolved_at'] = date('Y-m-d H:i:s');
            }
            $statuses[(string)$id] = [
                'status'      => $t['status'],
                'note'        => $t['note']        ?? null,
                'resolved_at' => $t['resolved_at'] ?? null,
            ];
            break;
        }
        unset($t);
        self::writeStatuses($statuses);
        Response::json(['success' => true, 'tasks' => $tasks]);
    }

    // POST /settings/tasks/batch  — set status for multiple IDs
    public function taskBatch(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $ids    = $request->post('ids', '');
        if (is_string($ids)) $ids = json_decode($ids, true) ?: [];
        $status = $request->post('status', '');
        $cycle  = ['empty', 'done', 'error', 'trigger_ai'];
        if (!in_array($status, $cycle, true) || empty($ids)) {
            Response::json(['success' => false, 'error' => 'Invalid']); return;
        }
        $tasks    = self::loadMergedTasks();
        $statuses = self::readStatuses();
        $idSet    = array_flip(array_map('intval', $ids));
        foreach ($tasks as &$t) {
            if (!isset($idSet[(int)$t['id']])) continue;
            $t['status'] = $status;
            if ($status === 'done' && empty($t['resolved_at'])) {
                $t['resolved_at'] = date('Y-m-d H:i:s');
            }
            $statuses[(string)$t['id']] = [
                'status'      => $t['status'],
                'note'        => $t['note']        ?? null,
                'resolved_at' => $t['resolved_at'] ?? null,
            ];
        }
        unset($t);
        self::writeStatuses($statuses);
        Response::json(['success' => true, 'tasks' => $tasks]);
    }

    // GET /settings/tasks/archive/download
    public function taskArchiveDownload(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $tasks = self::loadMergedTasks();
        $done  = array_filter($tasks, fn($t) => ($t['status'] ?? '') === 'done');
        usort($done, fn($a, $b) => strcmp(
            $b['resolved_at'] ?? $b['created_at'] ?? '',
            $a['resolved_at'] ?? $a['created_at'] ?? ''
        ));
        $lines = [
            'ROOTED — Archived Tasks',
            'Generated: ' . date('Y-m-d H:i'),
            str_repeat('=', 50),
            '',
        ];
        foreach ($done as $t) {
            $lines[] = '#' . $t['id'] . ' — ' . ($t['title'] ?? '');
            if (!empty($t['description'])) $lines[] = $t['description'];
            $lines[] = 'Created:   ' . ($t['created_at'] ?? '');
            if (!empty($t['resolved_at'])) $lines[] = 'Completed: ' . $t['resolved_at'];
            if (!empty($t['note'])) $lines[] = 'Note: ' . $t['note'];
            $lines[] = str_repeat('-', 40);
            $lines[] = '';
        }
        if (empty($done)) $lines[] = 'No archived tasks.';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="rooted-archived-tasks.txt"');
        header('Cache-Control: no-cache');
        echo implode("\n", $lines);
        exit;
    }

    private static function tasksPath(): string
    {
        return BASE_PATH . '/config/platform_tasks.json';
    }

    public static function readTasks(): array
    {
        $path = self::tasksPath();
        if (!file_exists($path)) return [];
        $raw = file_get_contents($path);
        return json_decode($raw, true) ?: [];
    }

    public static function writeTasks(array $tasks): void
    {
        file_put_contents(self::tasksPath(), json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public static function appendTask(string $title, string $description): void
    {
        $tasks = self::readTasks();
        $maxId = array_reduce($tasks, fn($m, $t) => max($m, (int)$t['id']), 0);
        $tasks[] = [
            'id'          => $maxId + 1,
            'title'       => $title,
            'description' => $description,
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        self::writeTasks($tasks);
    }

    // ── Task statuses (user-only, never deployed via upgrade ZIP) ────────────

    private static function statusesPath(): string
    {
        return STORAGE_PATH . '/task_statuses.json';
    }

    public static function readStatuses(): array
    {
        $path = self::statusesPath();
        if (file_exists($path)) {
            $raw = file_get_contents($path);
            $arr = json_decode($raw, true);
            if (is_array($arr)) return $arr;
        }
        // First-time bootstrap: migrate from any inline statuses still in
        // platform_tasks.json (so v3.1.39 → v3.1.40 upgrade preserves user data
        // even before UpgradeController hook runs).
        $migrated = [];
        foreach (self::readTasks() as $t) {
            if (!isset($t['status'])) continue;
            $st = (string)$t['status'];
            if ($st === '' || $st === 'empty') continue;
            $migrated[(string)$t['id']] = [
                'status'      => $st,
                'note'        => $t['note']        ?? null,
                'resolved_at' => $t['resolved_at'] ?? null,
            ];
        }
        self::writeStatuses($migrated);
        return $migrated;
    }

    public static function writeStatuses(array $statuses): void
    {
        $path = self::statusesPath();
        $dir  = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        file_put_contents($path, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Read tasks (definitions) and overlay user statuses from storage.
     * Statuses always win over any inline status field in platform_tasks.json.
     */
    public static function loadMergedTasks(): array
    {
        $tasks    = self::readTasks();
        $statuses = self::readStatuses();
        foreach ($tasks as &$t) {
            $sid = (string)$t['id'];
            $s   = $statuses[$sid] ?? [];
            $t['status']      = $s['status']      ?? ($t['status']      ?? 'empty');
            $t['note']        = $s['note']        ?? ($t['note']        ?? null);
            $t['resolved_at'] = $s['resolved_at'] ?? ($t['resolved_at'] ?? null);
        }
        unset($t);
        return $tasks;
    }

    // ── Future steps ─────────────────────────────────────────────────────────

    private static function stepsPath(): string
    {
        return STORAGE_PATH . '/future_steps.json';
    }

    public static function readSteps(): array
    {
        $path = self::stepsPath();
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?: [];
    }

    public static function writeSteps(array $steps): void
    {
        file_put_contents(self::stepsPath(), json_encode($steps, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    // POST /settings/future-steps — add new step
    public function addFutureStep(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $content = trim($request->post('content', ''));
        if ($content === '') { Response::json(['success' => false, 'error' => 'Empty']); return; }

        $steps  = self::readSteps();
        $maxId  = array_reduce($steps, fn($m, $s) => max($m, (int)$s['id']), 0);
        $zone   = null;
        if (preg_match('/^\(([^)]+)\)\s*/u', $content, $m)) {
            $zone    = strtoupper(trim($m[1]));
            $content = trim(substr($content, strlen($m[0])));
        }
        $step = [
            'id'         => $maxId + 1,
            'zone'       => $zone,
            'content'    => $content,
            'sort_order' => count($steps) + 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $steps[] = $step;
        self::writeSteps($steps);
        Response::json(['success' => true, 'step' => $step]);
    }

    // POST /settings/future-steps/{id}/update
    public function updateFutureStep(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id      = (int)($params['id'] ?? 0);
        $content = trim($request->post('content', ''));
        if ($content === '') { Response::json(['success' => false, 'error' => 'Empty']); return; }

        $steps = self::readSteps();
        $zone  = null;
        if (preg_match('/^\(([^)]+)\)\s*/u', $content, $m)) {
            $zone    = strtoupper(trim($m[1]));
            $content = trim(substr($content, strlen($m[0])));
        }
        foreach ($steps as &$s) {
            if ((int)$s['id'] !== $id) continue;
            $s['zone']    = $zone;
            $s['content'] = $content;
            break;
        }
        unset($s);
        self::writeSteps($steps);
        $updated = current(array_filter($steps, fn($s) => (int)$s['id'] === $id));
        Response::json(['success' => true, 'step' => $updated ?: []]);
    }

    // POST /settings/future-steps/{id}/delete
    public function deleteFutureStep(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id    = (int)($params['id'] ?? 0);
        $steps = array_values(array_filter(self::readSteps(), fn($s) => (int)$s['id'] !== $id));
        self::writeSteps($steps);
        Response::json(['success' => true]);
    }

    // POST /settings/future-steps/reorder
    public function reorderFutureSteps(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $ids   = $request->post('ids', '');
        if (is_string($ids)) $ids = json_decode($ids, true) ?: [];
        $steps = self::readSteps();
        $map   = [];
        foreach ($steps as $s) $map[(int)$s['id']] = $s;
        $ordered = [];
        foreach ($ids as $i => $id) {
            if (!isset($map[(int)$id])) continue;
            $map[(int)$id]['sort_order'] = $i + 1;
            $ordered[] = $map[(int)$id];
        }
        self::writeSteps($ordered);
        Response::json(['success' => true]);
    }

    // ── Weather settings ─────────────────────────────────────────────────────

    public function weather(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $ws = [];
        foreach ($db->fetchAll("SELECT setting_key, setting_value_text FROM settings WHERE setting_key LIKE 'weather.%'") as $r) {
            $ws[$r['setting_key']] = $r['setting_value_text'];
        }
        Response::render('settings/weather', ['title' => 'Weather Settings', 'ws' => $ws]);
    }

    public function updateWeather(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();

        $keys = ['weather.enabled','weather.city_name','weather.lat','weather.lng','weather.station_url','weather.forecast_url'];
        foreach ($keys as $key) {
            $value = $request->post(str_replace('.','_',$key), '');
            $db->execute(
                "INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
                 VALUES (?,?,'text',0,NOW())
                 ON DUPLICATE KEY UPDATE setting_value_text=VALUES(setting_value_text),updated_at=NOW()",
                [$key, $value]
            );
        }
        // Clear weather cache so next load re-fetches
        $db->execute("DELETE FROM settings WHERE setting_key = 'weather.cache'");

        flash('success', 'Weather settings saved.');
        Response::redirect('/settings/weather');
    }

    // ── Map settings ─────────────────────────────────────────────────────────

    public function updateMap(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $itemTypes   = require BASE_PATH . '/config/item_types.php';
        $validTypes  = array_keys($itemTypes);

        // Collect checked boundary types (only valid item type keys)
        $submitted = $_POST['boundary_types'] ?? [];
        $selected  = array_values(array_intersect((array) $submitted, $validTypes));

        DB::getInstance()->execute(
            "INSERT INTO settings (setting_key, setting_value_json, value_type, autoload, updated_at)
             VALUES ('map.boundary_types', ?, 'json', 0, NOW())
             ON DUPLICATE KEY UPDATE setting_value_json=VALUES(setting_value_json), updated_at=NOW()",
            [json_encode($selected)]
        );

        flash('success', 'Map settings saved.');
        Response::redirect('/settings');
    }

    // ── Logo upload ───────────────────────────────────────────────────────────

    public function uploadLogo(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $validSlots = ['icon-light', 'icon-dark', 'horizontal-light', 'horizontal-dark'];
        $slot       = $request->post('logo_slot', 'horizontal-light');
        if (!in_array($slot, $validSlots, true)) { $slot = 'horizontal-light'; }

        $file = $_FILES['logo_file'] ?? null;
        if (!$file || ($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
            flash('error', 'No file uploaded or upload error.');
            Response::redirect('/settings');
        }

        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name'] ?? '') ?: '';
        } catch (\Throwable $e) {
            $mime = '';
        }

        // finfo can return variant MIME strings (image/x-png, text/xml for SVG, etc.)
        $allowed = [
            'image/png'       => 'png',
            'image/x-png'     => 'png',
            'image/jpeg'      => 'jpg',
            'image/jpg'       => 'jpg',
            'image/pjpeg'     => 'jpg',
            'image/webp'      => 'webp',
            'image/svg+xml'   => 'svg',
            'text/xml'        => 'svg',   // some finfo builds return this for SVG
            'application/xml' => 'svg',
            'text/plain'      => null,    // resolved via extension below
        ];

        // Extension-based fallback when finfo is ambiguous
        $origName = strtolower($file['name'] ?? '');
        $extMap   = ['png' => 'png', 'jpg' => 'jpg', 'jpeg' => 'jpg', 'webp' => 'webp', 'svg' => 'svg'];
        $origExt  = pathinfo($origName, PATHINFO_EXTENSION);
        $extFallback = $extMap[$origExt] ?? null;

        $ext = null;
        if (isset($allowed[$mime]) && $allowed[$mime] !== null) {
            $ext = $allowed[$mime];
        } elseif (isset($allowed[$mime]) && $allowed[$mime] === null && $extFallback) {
            // text/plain — trust extension only for svg/png/jpg/webp
            $ext = $extFallback;
        } elseif (!isset($allowed[$mime]) && $extFallback) {
            // Unknown MIME but recognisable extension — use extension
            $ext = $extFallback;
        }

        if (!$ext) {
            flash('error', 'Only PNG, JPG, WebP, or SVG files are accepted.');
            Response::redirect('/settings');
        }
        $imgDir = PUBLIC_PATH . '/assets/images/';

        if (!is_dir($imgDir) && !@mkdir($imgDir, 0755, true)) {
            flash('error', 'Cannot create images directory. Check folder permissions.');
            Response::redirect('/settings');
        }

        // Remove existing files for this slot
        foreach (['png','jpg','webp','svg'] as $oldExt) {
            $old = $imgDir . 'logo-' . $slot . '.' . $oldExt;
            if (file_exists($old)) { @unlink($old); }
        }

        $dest = $imgDir . 'logo-' . $slot . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            flash('error', 'Failed to save logo. Check folder write permissions.');
            Response::redirect('/settings');
        }

        flash('success', ucwords(str_replace('-', ' ', $slot)) . ' logo updated.');
        Response::redirect('/settings');
    }

    public function deleteLogo(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $validSlots = ['icon-light', 'icon-dark', 'horizontal-light', 'horizontal-dark'];
        $slot       = $request->post('logo_slot', '');
        if (!in_array($slot, $validSlots, true)) {
            flash('error', 'Invalid logo slot.');
            Response::redirect('/settings');
        }

        $imgDir = PUBLIC_PATH . '/assets/images/';
        foreach (['png','jpg','webp','svg','ico'] as $ext) {
            $f = $imgDir . 'logo-' . $slot . '.' . $ext;
            if (file_exists($f)) { @unlink($f); }
        }

        flash('success', ucwords(str_replace('-', ' ', $slot)) . ' logo removed.');
        Response::redirect('/settings');
    }

    // ── Favicon upload ────────────────────────────────────────────────────────

    public function uploadFavicon(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $file = $_FILES['favicon_file'] ?? null;
        if (!$file || ($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
            flash('error', 'No file uploaded or upload error.');
            Response::redirect('/settings#favicon');
        }

        try {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'] ?? '') ?: '';
        } catch (\Throwable $e) { $mime = ''; }

        $origExt = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowed = ['ico' => 'ico', 'png' => 'png', 'svg' => 'svg',
                    'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico',
                    'image/png' => 'png', 'image/svg+xml' => 'svg'];

        $ext = $allowed[$mime] ?? $allowed[$origExt] ?? null;
        if (!$ext) {
            flash('error', 'Only ICO, PNG, or SVG files are accepted for favicons.');
            Response::redirect('/settings#favicon');
        }

        $imgDir = PUBLIC_PATH . '/assets/images/';
        if (!is_dir($imgDir) && !@mkdir($imgDir, 0755, true)) {
            flash('error', 'Cannot create images directory.');
            Response::redirect('/settings#favicon');
        }

        // Remove all previous favicon files
        foreach (['ico','png','svg'] as $oldExt) {
            $old = $imgDir . 'favicon.' . $oldExt;
            if (file_exists($old)) { @unlink($old); }
        }

        $dest = $imgDir . 'favicon.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            flash('error', 'Failed to save favicon. Check folder write permissions.');
            Response::redirect('/settings#favicon');
        }

        flash('success', 'Favicon updated.');
        Response::redirect('/settings#favicon');
    }

    public function deleteFavicon(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $imgDir = PUBLIC_PATH . '/assets/images/';
        foreach (['ico','png','svg'] as $ext) {
            $f = $imgDir . 'favicon.' . $ext;
            if (file_exists($f)) { @unlink($f); }
        }

        flash('success', 'Favicon removed.');
        Response::redirect('/settings#favicon');
    }
}
