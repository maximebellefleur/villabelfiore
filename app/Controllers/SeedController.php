<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;
use App\Support\GardenHelpers;
use App\Support\GardenSchema;

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

        // Migrate: add seed_ids JSON column to family_needs (multi-seed support)
        $seedIdsCol = $db->fetchAll("SHOW COLUMNS FROM family_needs LIKE 'seed_ids'");
        if (empty($seedIdsCol)) {
            try { $db->execute("ALTER TABLE family_needs ADD COLUMN seed_ids TEXT DEFAULT NULL AFTER seed_id"); } catch (\Throwable $e) {}
        }

        // Migrate: add needs_restock column to seeds
        $restockCol = $db->fetchAll("SHOW COLUMNS FROM seeds LIKE 'needs_restock'");
        if (empty($restockCol)) {
            $db->execute("ALTER TABLE seeds ADD COLUMN needs_restock TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_enabled");
        }

        // Migrate: add display color column (used in planting view chips)
        $colorCol = $db->fetchAll("SHOW COLUMNS FROM seeds LIKE 'color'");
        if (empty($colorCol)) {
            try { $db->execute("ALTER TABLE seeds ADD COLUMN color CHAR(7) DEFAULT NULL"); } catch (\Throwable $e) {}
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
        $sort   = in_array($request->get('sort', 'name'), ['name', 'type', 'days'], true) ? $request->get('sort', 'name') : 'name';
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

        $orderBy = match($sort) {
            'type'  => 'type ASC, name ASC',
            'days'  => 'COALESCE(days_to_maturity,9999) ASC, name ASC',
            default => 'name ASC',
        };

        $seeds    = $db->fetchAll("SELECT * FROM seeds $where ORDER BY $orderBy", $bind);
        $lowStock = array_filter($seeds, fn($s) => $s['stock_enabled'] && $s['stock_low_threshold'] !== null && (float)$s['stock_qty'] <= (float)$s['stock_low_threshold']);

        Response::render('seeds/index', [
            'title'    => 'Seed Catalog',
            'seeds'    => $seeds,
            'lowStock' => $lowStock,
            'type'     => $type,
            'search'   => $search,
            'sort'     => $sort,
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
             stock_qty, stock_unit, stock_low_threshold, stock_enabled, notes, color)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['name'], $data['variety'], $data['botanical_family'], $data['type'], $data['sowing_type'],
                $data['days_to_germinate'], $data['days_to_maturity'], $data['spacing_cm'],
                $data['row_spacing_cm'], $data['sowing_depth_mm'],
                $data['sun_exposure'], $data['soil_notes'],
                $data['planting_months'], $data['harvest_months'], $data['frost_hardy'],
                $data['companions'], $data['antagonists'], $data['yield_per_plant_kg'],
                $data['stock_qty'], $data['stock_unit'], $data['stock_low_threshold'],
                $data['stock_enabled'], $data['notes'], $data['color'],
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

        $familyNeeds = $db->fetchAll('SELECT * FROM family_needs WHERE seed_id = ? ORDER BY priority ASC', [$id]);

        Response::render('seeds/show', [
            'title'       => e($seed['name']),
            'seed'        => $seed,
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

        $gardenerNote = trim($request->post('gardener_note', '')) ?: null;
        $db->execute(
            "UPDATE seeds SET name=?, variety=?, botanical_family=?, type=?, sowing_type=?,
             days_to_germinate=?, days_to_maturity=?, spacing_cm=?, row_spacing_cm=?, sowing_depth_mm=?,
             sun_exposure=?, soil_notes=?, planting_months=?, harvest_months=?, frost_hardy=?,
             companions=?, antagonists=?, yield_per_plant_kg=?,
             stock_qty=?, stock_unit=?, stock_low_threshold=?, stock_enabled=?, notes=?, color=?,
             gardener_note=?
             WHERE id=?",
            [
                $data['name'], $data['variety'], $data['botanical_family'], $data['type'], $data['sowing_type'],
                $data['days_to_germinate'], $data['days_to_maturity'], $data['spacing_cm'],
                $data['row_spacing_cm'], $data['sowing_depth_mm'],
                $data['sun_exposure'], $data['soil_notes'],
                $data['planting_months'], $data['harvest_months'], $data['frost_hardy'],
                $data['companions'], $data['antagonists'], $data['yield_per_plant_kg'],
                $data['stock_qty'], $data['stock_unit'], $data['stock_low_threshold'],
                $data['stock_enabled'], $data['notes'], $data['color'],
                $gardenerNote,
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

    // ── Family needs ──────────────────────────────────────────────────────────

    public function familyNeeds(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db    = DB::getInstance();
        $this->ensureTables($db);
        try { GardenSchema::ensure($db); }
        catch (\Throwable $e) { \App\Support\Logger::error('Family needs: GardenSchema::ensure failed — ' . $e->getMessage()); }

        // 1. Load the user's needs. This is the data they care about — it MUST always render
        //    even if downstream enrichment fails. Do NOT wrap this in a wider try/catch
        //    that could blank the list on an unrelated error.
        $rows = $db->fetchAll(
            "SELECT fn.* FROM family_needs fn ORDER BY fn.priority ASC, fn.vegetable_name ASC"
        ) ?: [];

        // Build set of all seed IDs referenced
        $allSeedIds = [];
        foreach ($rows as $r) {
            foreach (self::parseSeedIds($r) as $sid) $allSeedIds[$sid] = true;
        }

        // 2. Enrich with seed data. Wrapped so the list still renders if columns
        //    are missing (e.g. v3.1.74 schema migration didn't fire on this server)
        //    or any other DB error. A fallback query without the v3.1.74 columns
        //    keeps the seed names visible at minimum.
        $seedMap = [];
        if (!empty($allSeedIds)) {
            $ph     = implode(',', array_fill(0, count($allSeedIds), '?'));
            $params = array_keys($allSeedIds);
            try {
                $sRows = $db->fetchAll(
                    "SELECT id, name, variety, projected_seed_prod_count, harvested_seed_prod_count
                     FROM seeds WHERE id IN ($ph)",
                    $params
                );
                foreach ($sRows as $s) $seedMap[(int)$s['id']] = $s;
            } catch (\Throwable $e) {
                \App\Support\Logger::error('Family needs: seed enrichment query failed (likely missing v3.1.74 columns) — ' . $e->getMessage());
                try {
                    $sRows = $db->fetchAll(
                        "SELECT id, name, variety FROM seeds WHERE id IN ($ph)",
                        $params
                    );
                    foreach ($sRows as $s) {
                        $s['projected_seed_prod_count'] = 0;
                        $s['harvested_seed_prod_count'] = 0;
                        $seedMap[(int)$s['id']] = $s;
                    }
                } catch (\Throwable $e2) {
                    \App\Support\Logger::error('Family needs: fallback seed query also failed — ' . $e2->getMessage());
                }
            }
        }

        // 3. Future-planned plants (status='planned' AND planted_at > today). Optional.
        $plannedFuture = [];
        if (!empty($allSeedIds)) {
            try {
                $ph = implode(',', array_fill(0, count($allSeedIds), '?'));
                $pfRows = $db->fetchAll(
                    "SELECT gp.seed_id, SUM(COALESCE(gp.plant_count,1)) AS total
                     FROM garden_plantings gp
                     LEFT JOIN item_meta im ON im.item_id = gp.item_id AND im.meta_key = 'bed_rows'
                     WHERE gp.seed_id IN ($ph)
                       AND gp.status = 'planned'
                       AND (gp.planted_at IS NULL OR gp.planted_at > CURDATE())
                       AND gp.line_number <= CAST(COALESCE(im.meta_value_text, '9999') AS UNSIGNED)
                     GROUP BY gp.seed_id",
                    array_keys($allSeedIds)
                );
                foreach ($pfRows as $pr) $plannedFuture[(int)$pr['seed_id']] = (int)$pr['total'];
            } catch (\Throwable $e) {
                \App\Support\Logger::error('Family needs: plannedFuture query failed — ' . $e->getMessage());
            }
        }

        // 4. Bulk-fetch harvest totals from log (current year + 2 previous). Optional.
        $harvestMap = [];
        if (!empty($allSeedIds)) {
            try {
                $ph = implode(',', array_fill(0, count($allSeedIds), '?'));
                $hRows = $db->fetchAll(
                    "SELECT seed_id, YEAR(harvested_on) AS yr, SUM(plant_count) AS total
                     FROM seed_harvest_log
                     WHERE seed_id IN ($ph)
                       AND YEAR(harvested_on) >= YEAR(CURDATE()) - 2
                     GROUP BY seed_id, YEAR(harvested_on)
                     ORDER BY seed_id, yr DESC",
                    array_keys($allSeedIds)
                );
                foreach ($hRows as $hr) {
                    $harvestMap[(int)$hr['seed_id']][] = [
                        'year'  => (int)$hr['yr'],
                        'total' => (int)$hr['total'],
                    ];
                }
            } catch (\Throwable $e) {
                \App\Support\Logger::error('Family needs: harvest-log query failed — ' . $e->getMessage());
            }
        }

        // 5. Assemble the final needs list. Each row is independently safe.
        $needs = [];
        foreach ($rows as $need) {
            $ids = self::parseSeedIds($need);
            $agg = ['plants_in_ground' => 0, 'plants_planned' => 0];
            foreach ($ids as $sid) {
                $s = $seedMap[$sid] ?? null;
                if ($s) {
                    $agg['plants_in_ground'] += (int)($s['projected_seed_prod_count'] ?? 0);
                }
                $agg['plants_planned'] += $plannedFuture[$sid] ?? 0;
            }
            $harvestByYearMerged = [];
            foreach ($ids as $sid) {
                foreach ($harvestMap[$sid] ?? [] as $hy) {
                    $yr = $hy['year'];
                    $harvestByYearMerged[$yr] = ($harvestByYearMerged[$yr] ?? 0) + $hy['total'];
                }
            }
            krsort($harvestByYearMerged);
            $harvestByYear = [];
            foreach ($harvestByYearMerged as $yr => $total) {
                $harvestByYear[] = ['year' => $yr, 'total' => $total];
            }
            $need['linked_seed_ids']   = $ids;
            $need['linked_seed_names'] = array_values(array_filter(array_map(function($sid) use ($seedMap) {
                if (!isset($seedMap[$sid])) return null;
                $s = $seedMap[$sid];
                return $s['name'] . ($s['variety'] ? ' ('.$s['variety'].')' : '');
            }, $ids)));
            $first = $ids[0] ?? 0;
            $need['seed_name'] = $first && isset($seedMap[$first])
                ? $seedMap[$first]['name'] . ($seedMap[$first]['variety'] ? ' ('.$seedMap[$first]['variety'].')' : '')
                : null;
            $need['harvest_by_year']     = $harvestByYear;
            $need['harvest_est_ground']  = null;
            $need['harvest_est_planned'] = null;
            $need = array_merge($need, $agg);
            $needs[] = $need;
        }

        $seeds = $db->fetchAll('SELECT id, name, variety FROM seeds ORDER BY name ASC') ?: [];

        Response::render('seeds/family-needs', [
            'title' => 'Family Needs',
            'needs' => $needs,
            'seeds' => $seeds,
        ]);
    }

    private static function parseSeedIds(array $row): array
    {
        if (!empty($row['seed_ids'])) {
            $decoded = json_decode($row['seed_ids'], true);
            if (is_array($decoded) && !empty($decoded)) {
                return array_values(array_map('intval', array_filter($decoded)));
            }
        }
        $legacy = (int)($row['seed_id'] ?? 0);
        return $legacy > 0 ? [$legacy] : [];
    }

    private static function postSeedIds(Request $request): array
    {
        $raw = $request->post('seed_ids', []);
        if (is_string($raw)) $raw = array_filter(array_map('trim', explode(',', $raw)));
        if (!is_array($raw)) $raw = [];
        return array_values(array_unique(array_filter(array_map('intval', $raw))));
    }

    public function storeFamilyNeed(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTables($db);

        $seedIds  = self::postSeedIds($request);
        $firstId  = $seedIds[0] ?? null;

        $db->execute(
            "INSERT INTO family_needs (vegetable_name, seed_id, seed_ids, yearly_qty, yearly_unit, priority, notes) VALUES (?,?,?,?,?,?,?)",
            [
                trim($request->post('vegetable_name', '')),
                $firstId,
                $seedIds ? json_encode($seedIds) : null,
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

        $seedIds  = self::postSeedIds($request);
        $firstId  = $seedIds[0] ?? null;

        $db->execute(
            "UPDATE family_needs SET vegetable_name=?, seed_id=?, seed_ids=?, yearly_qty=?, yearly_unit=?, priority=?, notes=? WHERE id=?",
            [
                trim($request->post('vegetable_name', '')),
                $firstId,
                $seedIds ? json_encode($seedIds) : null,
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

    public function syncFamilyNeeds(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTables($db);
        try { GardenSchema::ensure($db); } catch (\Throwable $e) {}
        try {
            $count = GardenHelpers::recalcAllSeedsProjected($db);
            flash('success', '✅ Ground sync complete — ' . $count . ' seed(s) recomputed.');
        } catch (\Throwable $e) {
            \App\Support\Logger::error('Family needs sync failed — ' . $e->getMessage());
            flash('error', 'Sync failed: ' . $e->getMessage());
        }
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
            'color'              => (function() use ($request) {
                $c = trim((string)$request->post('color', ''));
                return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? strtolower($c) : null;
            })(),
        ];
    }

    /** AJAX: save gardener's personal note for a seed inline. */
    public function saveGardenerNote(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id   = (int)($params['id'] ?? 0);
        $note = trim($request->post('note', ''));
        $db   = DB::getInstance();
        $this->ensureTables($db);
        try {
            $db->execute("UPDATE seeds SET gardener_note = ?, updated_at = NOW() WHERE id = ?", [$note ?: null, $id]);
            Response::json(['success' => true, 'note' => $note]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
