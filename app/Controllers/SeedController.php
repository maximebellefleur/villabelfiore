<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class SeedController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    // ── Table bootstrap ───────────────────────────────────────────────────────

    private function ensureTables(DB $db): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        $db->execute("CREATE TABLE IF NOT EXISTS seeds (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name             VARCHAR(120) NOT NULL,
            variety          VARCHAR(120) DEFAULT '',
            botanical_family VARCHAR(120) DEFAULT '',
            type             ENUM('vegetable','herb','fruit','flower','other') NOT NULL DEFAULT 'vegetable',
            sowing_type      ENUM('direct','nursery','both') NOT NULL DEFAULT 'direct',
            days_to_germinate SMALLINT UNSIGNED DEFAULT NULL,
            days_to_maturity  SMALLINT UNSIGNED DEFAULT NULL,
            spacing_cm        SMALLINT UNSIGNED DEFAULT NULL,
            row_spacing_cm    SMALLINT UNSIGNED DEFAULT NULL,
            sowing_depth_mm   SMALLINT UNSIGNED DEFAULT NULL,
            sun_exposure      VARCHAR(60) DEFAULT '',
            soil_notes        TEXT DEFAULT NULL,
            planting_months   JSON DEFAULT NULL,
            harvest_months    JSON DEFAULT NULL,
            frost_hardy       TINYINT(1) NOT NULL DEFAULT 0,
            companions        JSON DEFAULT NULL,
            antagonists       JSON DEFAULT NULL,
            yield_per_plant_kg DECIMAL(8,3) DEFAULT NULL,
            stock_qty          DECIMAL(12,3) NOT NULL DEFAULT 0,
            stock_unit         ENUM('seeds','grams','packets') NOT NULL DEFAULT 'seeds',
            stock_low_threshold DECIMAL(12,3) DEFAULT NULL,
            stock_enabled      TINYINT(1) NOT NULL DEFAULT 1,
            notes              TEXT DEFAULT NULL,
            created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS bed_rows (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id          INT UNSIGNED NOT NULL,
            season_year      SMALLINT UNSIGNED NOT NULL,
            row_number       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            seed_id          INT UNSIGNED DEFAULT NULL,
            plant_count      SMALLINT UNSIGNED DEFAULT NULL,
            spacing_used_cm  SMALLINT UNSIGNED DEFAULT NULL,
            sowing_date      DATE DEFAULT NULL,
            transplant_date  DATE DEFAULT NULL,
            sowing_type      ENUM('direct','nursery','both') DEFAULT NULL,
            notes            TEXT DEFAULT NULL,
            status           ENUM('planned','sown','growing','harvested') NOT NULL DEFAULT 'planned',
            created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS family_needs (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            vegetable_name   VARCHAR(120) NOT NULL,
            seed_id          INT UNSIGNED DEFAULT NULL,
            yearly_qty       DECIMAL(10,3) DEFAULT NULL,
            yearly_unit      VARCHAR(30) NOT NULL DEFAULT 'kg',
            priority         TINYINT UNSIGNED NOT NULL DEFAULT 5,
            notes            TEXT DEFAULT NULL,
            created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Migrate old yearly_qty_kg column → yearly_qty if needed
        $cols = $db->fetchAll("SHOW COLUMNS FROM family_needs LIKE 'yearly_qty_kg'");
        if (!empty($cols)) {
            $db->execute("ALTER TABLE family_needs CHANGE yearly_qty_kg yearly_qty DECIMAL(10,3) DEFAULT NULL");
        }
        $unitCol = $db->fetchAll("SHOW COLUMNS FROM family_needs LIKE 'yearly_unit'");
        if (empty($unitCol)) {
            $db->execute("ALTER TABLE family_needs ADD COLUMN yearly_unit VARCHAR(30) NOT NULL DEFAULT 'kg' AFTER yearly_qty");
        }

        // Migrate: add needs_restock column to seeds
        $restockCol = $db->fetchAll("SHOW COLUMNS FROM seeds LIKE 'needs_restock'");
        if (empty($restockCol)) {
            $db->execute("ALTER TABLE seeds ADD COLUMN needs_restock TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_enabled");
        }
    }

    // ── Seed CRUD ─────────────────────────────────────────────────────────────

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $this->ensureTables($db);

        $type   = $request->get('type', '');
        $search = trim($request->get('q', ''));
        $where  = 'WHERE 1=1';
        $bind   = [];

        if ($type !== '') {
            $where .= ' AND type = ?';
            $bind[] = $type;
        }
        if ($search !== '') {
            $where .= ' AND (name LIKE ? OR variety LIKE ? OR botanical_family LIKE ?)';
            $bind[] = '%' . $search . '%';
            $bind[] = '%' . $search . '%';
            $bind[] = '%' . $search . '%';
        }

        $seeds      = $db->fetchAll("SELECT * FROM seeds $where ORDER BY name ASC", $bind);
        $lowStock   = array_filter($seeds, fn($s) => $s['stock_enabled'] && $s['stock_low_threshold'] !== null && (float)$s['stock_qty'] <= (float)$s['stock_low_threshold']);

        Response::render('seeds/index', [
            'title'    => 'Seed Catalog',
            'seeds'    => $seeds,
            'lowStock' => $lowStock,
            'type'     => $type,
            'search'   => $search,
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $this->ensureTables($db);
        $epRow = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key = 'ai.extra_prompt' LIMIT 1");
        Response::render('seeds/create', [
            'title'         => 'Add Seed',
            'aiExtraPrompt' => (string)($epRow['setting_value_text'] ?? ''),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTables($db);

        $data = $this->extractSeedData($request);

        $db->execute(
            "INSERT INTO seeds (name, variety, botanical_family, type, sowing_type,
             days_to_germinate, days_to_maturity, spacing_cm, row_spacing_cm, sowing_depth_mm,
             sun_exposure, soil_notes, planting_months, harvest_months, frost_hardy,
             companions, antagonists, yield_per_plant_kg,
             stock_qty, stock_unit, stock_low_threshold, stock_enabled, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['name'], $data['variety'], $data['botanical_family'], $data['type'], $data['sowing_type'],
                $data['days_to_germinate'], $data['days_to_maturity'], $data['spacing_cm'],
                $data['row_spacing_cm'], $data['sowing_depth_mm'],
                $data['sun_exposure'], $data['soil_notes'],
                $data['planting_months'], $data['harvest_months'], $data['frost_hardy'],
                $data['companions'], $data['antagonists'], $data['yield_per_plant_kg'],
                $data['stock_qty'], $data['stock_unit'], $data['stock_low_threshold'],
                $data['stock_enabled'], $data['notes'],
            ]
        );

        $id = (int) $db->lastInsertId();
        $addedName = trim($request->post('name', 'Seed'));
        flash('success', '✅ "' . $addedName . '" added to catalog — add another one below.');
        Response::redirect('/seeds/create');
    }

    public function show(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id   = (int)($params['id'] ?? 0);
        $db   = DB::getInstance();
        $this->ensureTables($db);

        $seed = $db->fetchOne('SELECT * FROM seeds WHERE id = ?', [$id]);
        if (!$seed) { Response::redirect('/seeds'); }

        $bedRows     = $db->fetchAll(
            'SELECT br.*, s.name AS seed_name FROM bed_rows br LEFT JOIN seeds s ON s.id = br.seed_id WHERE br.seed_id = ? ORDER BY br.season_year DESC, br.row_number ASC',
            [$id]
        );
        $familyNeeds = $db->fetchAll('SELECT * FROM family_needs WHERE seed_id = ? ORDER BY priority ASC', [$id]);

        Response::render('seeds/show', [
            'title'       => e($seed['name']),
            'seed'        => $seed,
            'bedRows'     => $bedRows,
            'familyNeeds' => $familyNeeds,
        ]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id   = (int)($params['id'] ?? 0);
        $db   = DB::getInstance();
        $this->ensureTables($db);

        $seed = $db->fetchOne('SELECT * FROM seeds WHERE id = ?', [$id]);
        if (!$seed) { Response::redirect('/seeds'); }

        $epRow = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key = 'ai.extra_prompt' LIMIT 1");
        Response::render('seeds/edit', [
            'title'         => 'Edit ' . $seed['name'],
            'seed'          => $seed,
            'aiExtraPrompt' => (string)($epRow['setting_value_text'] ?? ''),
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id   = (int)($params['id'] ?? 0);
        $db   = DB::getInstance();
        $this->ensureTables($db);

        $data = $this->extractSeedData($request);

        $db->execute(
            "UPDATE seeds SET name=?, variety=?, botanical_family=?, type=?, sowing_type=?,
             days_to_germinate=?, days_to_maturity=?, spacing_cm=?, row_spacing_cm=?, sowing_depth_mm=?,
             sun_exposure=?, soil_notes=?, planting_months=?, harvest_months=?, frost_hardy=?,
             companions=?, antagonists=?, yield_per_plant_kg=?,
             stock_qty=?, stock_unit=?, stock_low_threshold=?, stock_enabled=?, notes=?
             WHERE id=?",
            [
                $data['name'], $data['variety'], $data['botanical_family'], $data['type'], $data['sowing_type'],
                $data['days_to_germinate'], $data['days_to_maturity'], $data['spacing_cm'],
                $data['row_spacing_cm'], $data['sowing_depth_mm'],
                $data['sun_exposure'], $data['soil_notes'],
                $data['planting_months'], $data['harvest_months'], $data['frost_hardy'],
                $data['companions'], $data['antagonists'], $data['yield_per_plant_kg'],
                $data['stock_qty'], $data['stock_unit'], $data['stock_low_threshold'],
                $data['stock_enabled'], $data['notes'],
                $id,
            ]
        );

        flash('success', 'Seed updated.');
        Response::redirect('/seeds/' . $id);
    }

    public function trash(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        $db = DB::getInstance();
        $this->ensureTables($db);
        $db->execute('DELETE FROM seeds WHERE id = ?', [$id]);
        flash('success', 'Seed deleted.');
        Response::redirect('/seeds');
    }

    public function adjustStock(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id     = (int)($params['id'] ?? 0);
        $db     = DB::getInstance();
        $this->ensureTables($db);

        $action = $request->post('stock_action', 'set');
        $amount = (float) $request->post('stock_amount', 0);

        if ($action === 'add') {
            $db->execute('UPDATE seeds SET stock_qty = stock_qty + ? WHERE id = ?', [$amount, $id]);
        } elseif ($action === 'subtract') {
            $db->execute('UPDATE seeds SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?', [$amount, $id]);
        } else {
            $db->execute('UPDATE seeds SET stock_qty = ? WHERE id = ?', [max(0, $amount), $id]);
        }

        flash('success', 'Stock updated.');
        Response::redirect('/seeds/' . $id);
    }

    // ── Bed rows ──────────────────────────────────────────────────────────────

    public function bedRows(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $itemId = (int)($params['id'] ?? 0);
        $db     = DB::getInstance();
        $this->ensureTables($db);

        $item = $db->fetchOne('SELECT id, name, type FROM items WHERE id = ?', [$itemId]);
        if (!$item) { Response::redirect('/items'); }

        $rows  = $db->fetchAll(
            'SELECT br.*, s.name AS seed_name FROM bed_rows br LEFT JOIN seeds s ON s.id = br.seed_id WHERE br.item_id = ? ORDER BY br.season_year DESC, br.row_number ASC',
            [$itemId]
        );
        $seeds = $db->fetchAll('SELECT id, name, variety FROM seeds ORDER BY name ASC');

        Response::render('seeds/bed-rows', [
            'title' => 'Bed Planner — ' . $item['name'],
            'item'  => $item,
            'rows'  => $rows,
            'seeds' => $seeds,
        ]);
    }

    public function storeBedRow(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $itemId = (int)($params['id'] ?? 0);
        $db     = DB::getInstance();
        $this->ensureTables($db);

        $db->execute(
            "INSERT INTO bed_rows (item_id, season_year, row_number, seed_id, plant_count, spacing_used_cm, sowing_date, transplant_date, sowing_type, notes, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [
                $itemId,
                (int)$request->post('season_year', date('Y')),
                (int)$request->post('row_number', 1),
                ($request->post('seed_id', '') ?: null),
                ($request->post('plant_count', '') ?: null),
                ($request->post('spacing_used_cm', '') ?: null),
                ($request->post('sowing_date', '') ?: null),
                ($request->post('transplant_date', '') ?: null),
                ($request->post('sowing_type', '') ?: null),
                trim($request->post('notes', '')),
                $request->post('status', 'planned'),
            ]
        );

        flash('success', 'Row added.');
        Response::redirect('/items/' . $itemId . '/rows');
    }

    public function updateBedRow(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $rowId  = (int)($params['id'] ?? 0);
        $db     = DB::getInstance();
        $this->ensureTables($db);

        $row = $db->fetchOne('SELECT item_id FROM bed_rows WHERE id = ?', [$rowId]);

        $db->execute(
            "UPDATE bed_rows SET season_year=?, row_number=?, seed_id=?, plant_count=?, spacing_used_cm=?, sowing_date=?, transplant_date=?, sowing_type=?, notes=?, status=? WHERE id=?",
            [
                (int)$request->post('season_year', date('Y')),
                (int)$request->post('row_number', 1),
                ($request->post('seed_id', '') ?: null),
                ($request->post('plant_count', '') ?: null),
                ($request->post('spacing_used_cm', '') ?: null),
                ($request->post('sowing_date', '') ?: null),
                ($request->post('transplant_date', '') ?: null),
                ($request->post('sowing_type', '') ?: null),
                trim($request->post('notes', '')),
                $request->post('status', 'planned'),
                $rowId,
            ]
        );

        flash('success', 'Row updated.');
        Response::redirect('/items/' . ($row['item_id'] ?? '') . '/rows');
    }

    public function trashBedRow(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $rowId = (int)($params['id'] ?? 0);
        $db    = DB::getInstance();
        $this->ensureTables($db);
        $row   = $db->fetchOne('SELECT item_id FROM bed_rows WHERE id = ?', [$rowId]);
        $db->execute('DELETE FROM bed_rows WHERE id = ?', [$rowId]);
        flash('success', 'Row removed.');
        Response::redirect('/items/' . ($row['item_id'] ?? '') . '/rows');
    }

    // ── Family needs ──────────────────────────────────────────────────────────

    public function familyNeeds(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db    = DB::getInstance();
        $this->ensureTables($db);
        $needs = $db->fetchAll('SELECT fn.*, s.name AS seed_name FROM family_needs fn LEFT JOIN seeds s ON s.id = fn.seed_id ORDER BY fn.priority ASC, fn.vegetable_name ASC');
        $seeds = $db->fetchAll('SELECT id, name, variety FROM seeds ORDER BY name ASC');

        Response::render('seeds/family-needs', [
            'title' => 'Family Needs',
            'needs' => $needs,
            'seeds' => $seeds,
        ]);
    }

    public function storeFamilyNeed(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTables($db);

        $db->execute(
            "INSERT INTO family_needs (vegetable_name, seed_id, yearly_qty, yearly_unit, priority, notes) VALUES (?,?,?,?,?,?)",
            [
                trim($request->post('vegetable_name', '')),
                ($request->post('seed_id', '') ?: null),
                ($request->post('yearly_qty', '') ?: null),
                ($request->post('yearly_unit', 'kg') ?: 'kg'),
                (int)$request->post('priority', 5),
                trim($request->post('notes', '')),
            ]
        );

        flash('success', 'Need added.');
        Response::redirect('/seeds/family-needs');
    }

    public function updateFamilyNeed(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $needId = (int)($params['id'] ?? 0);
        $db     = DB::getInstance();
        $this->ensureTables($db);

        $db->execute(
            "UPDATE family_needs SET vegetable_name=?, seed_id=?, yearly_qty=?, yearly_unit=?, priority=?, notes=? WHERE id=?",
            [
                trim($request->post('vegetable_name', '')),
                ($request->post('seed_id', '') ?: null),
                ($request->post('yearly_qty', '') ?: null),
                ($request->post('yearly_unit', 'kg') ?: 'kg'),
                (int)$request->post('priority', 5),
                trim($request->post('notes', '')),
                $needId,
            ]
        );

        flash('success', 'Need updated.');
        Response::redirect('/seeds/family-needs');
    }

    public function trashFamilyNeed(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $needId = (int)($params['id'] ?? 0);
        $db     = DB::getInstance();
        $this->ensureTables($db);
        $db->execute('DELETE FROM family_needs WHERE id = ?', [$needId]);
        flash('success', 'Need removed.');
        Response::redirect('/seeds/family-needs');
    }

    // ── Buy list / out-of-seed ────────────────────────────────────────────────

    public function toggleRestock(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id  = (int)($params['id'] ?? 0);
        $db  = DB::getInstance();
        $this->ensureTables($db);
        $db->execute('UPDATE seeds SET needs_restock = IF(needs_restock = 1, 0, 1) WHERE id = ?', [$id]);
        Response::redirect('/seeds/' . $id);
    }

    public function markBought(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        $db = DB::getInstance();
        $this->ensureTables($db);
        $db->execute('UPDATE seeds SET needs_restock = 0 WHERE id = ?', [$id]);
        if ($request->post('_ajax') === '1') {
            Response::json(['success' => true]);
            return;
        }
        Response::redirect('/tasks?tab=achats');
    }

    public function buyList(Request $request, array $params = []): void
    {
        $this->requireAuth();
        Response::redirect('/tasks?tab=achats');
    }

    // ── Name uniqueness check (JSON API) ──────────────────────────────────────

    public function checkName(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db      = DB::getInstance();
        $this->ensureTables($db);
        $name    = trim($request->get('name', ''));
        $exclude = (int)$request->get('exclude', 0);
        header('Content-Type: application/json');
        if ($name === '') { echo json_encode(['exists' => false]); return; }
        $sql  = 'SELECT id, name, variety FROM seeds WHERE LOWER(name) = LOWER(?)';
        $bind = [$name];
        if ($exclude > 0) { $sql .= ' AND id != ?'; $bind[] = $exclude; }
        $row = $db->fetchOne($sql, $bind);
        echo json_encode($row ? ['exists' => true, 'id' => $row['id'], 'name' => $row['name'], 'variety' => $row['variety']] : ['exists' => false]);
    }

    // ── Data extraction helper ────────────────────────────────────────────────

    private function extractSeedData(Request $request): array
    {
        $plantingMonths = $request->post('planting_months', []);
        $harvestMonths  = $request->post('harvest_months', []);
        $companions     = trim($request->post('companions', ''));
        $antagonists    = trim($request->post('antagonists', ''));

        return [
            'name'               => trim($request->post('name', '')),
            'variety'            => trim($request->post('variety', '')),
            'botanical_family'   => trim($request->post('botanical_family', '')),
            'type'               => $request->post('type', 'vegetable'),
            'sowing_type'        => $request->post('sowing_type', 'direct'),
            'days_to_germinate'  => ($request->post('days_to_germinate', '') !== '' ? (int)$request->post('days_to_germinate') : null),
            'days_to_maturity'   => ($request->post('days_to_maturity', '') !== '' ? (int)$request->post('days_to_maturity') : null),
            'spacing_cm'         => ($request->post('spacing_cm', '') !== '' ? (int)$request->post('spacing_cm') : null),
            'row_spacing_cm'     => ($request->post('row_spacing_cm', '') !== '' ? (int)$request->post('row_spacing_cm') : null),
            'sowing_depth_mm'    => ($request->post('sowing_depth_mm', '') !== '' ? (int)$request->post('sowing_depth_mm') : null),
            'sun_exposure'       => trim($request->post('sun_exposure', '')),
            'soil_notes'         => trim($request->post('soil_notes', '')),
            'planting_months'    => is_array($plantingMonths) && count($plantingMonths) ? json_encode(array_map('intval', $plantingMonths)) : null,
            'harvest_months'     => is_array($harvestMonths) && count($harvestMonths) ? json_encode(array_map('intval', $harvestMonths)) : null,
            'frost_hardy'        => $request->post('frost_hardy', '0') === '1' ? 1 : 0,
            'companions'         => $companions !== '' ? json_encode(array_filter(array_map('trim', explode(',', $companions)))) : null,
            'antagonists'        => $antagonists !== '' ? json_encode(array_filter(array_map('trim', explode(',', $antagonists)))) : null,
            'yield_per_plant_kg' => ($request->post('yield_per_plant_kg', '') !== '' ? (float)$request->post('yield_per_plant_kg') : null),
            'stock_qty'          => (float)$request->post('stock_qty', 0),
            'stock_unit'         => $request->post('stock_unit', 'seeds'),
            'stock_low_threshold' => ($request->post('stock_low_threshold', '') !== '' ? (float)$request->post('stock_low_threshold') : null),
            'stock_enabled'      => $request->post('stock_enabled', '0') === '1' ? 1 : 0,
            'notes'              => trim($request->post('notes', '')),
        ];
    }
}
