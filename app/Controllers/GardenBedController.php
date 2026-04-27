<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class GardenBedController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    private function ensureTable(DB $db): void
    {
        static $done = false;
        if ($done) { return; }
        $db->execute("CREATE TABLE IF NOT EXISTS garden_plantings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id INT UNSIGNED NOT NULL,
            line_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            crop_name VARCHAR(200) DEFAULT NULL,
            variety VARCHAR(200) DEFAULT NULL,
            status ENUM('empty','planned','growing','harvested') NOT NULL DEFAULT 'empty',
            planted_at DATE DEFAULT NULL,
            expected_harvest_at DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Migrate: seed_id and plant_count
        $col = $db->fetchAll("SHOW COLUMNS FROM garden_plantings LIKE 'seed_id'");
        if (empty($col)) {
            $db->execute("ALTER TABLE garden_plantings ADD COLUMN seed_id INT UNSIGNED DEFAULT NULL AFTER notes");
        }
        $col2 = $db->fetchAll("SHOW COLUMNS FROM garden_plantings LIKE 'plant_count'");
        if (empty($col2)) {
            $db->execute("ALTER TABLE garden_plantings ADD COLUMN plant_count SMALLINT UNSIGNED DEFAULT NULL AFTER seed_id");
        }
        $done = true;
    }

    public function show(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $id = (int)($params['id'] ?? 0);

        $this->ensureTable($db);

        $item = $db->fetchOne("SELECT * FROM items WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$item) {
            http_response_code(404);
            Response::render('errors/404', ['title' => 'Not Found']);
            return;
        }

        $metaRows = $db->fetchAll("SELECT meta_key, meta_value_text FROM item_meta WHERE item_id = ?", [$id]);
        $meta = [];
        foreach ($metaRows as $row) {
            $meta[$row['meta_key']] = $row['meta_value_text'];
        }

        $bedRows   = (int)($meta['bed_rows'] ?? 0);
        $lengthM   = (float)($meta['bed_length_m'] ?? 0);
        $widthM    = (float)($meta['bed_width_m'] ?? 0);
        $lineDir   = $meta['line_direction'] ?? 'NS';

        $parentGarden = null;
        if (!empty($item['parent_id'])) {
            $parentGarden = $db->fetchOne("SELECT * FROM items WHERE id = ?", [(int)$item['parent_id']]);
        }

        $plantings = $db->fetchAll(
            "SELECT * FROM garden_plantings WHERE item_id = ? ORDER BY line_number ASC",
            [$id]
        );

        $plantingMap = [];
        foreach ($plantings as $planting) {
            $plantingMap[(int)$planting['line_number']] = $planting;
        }

        $now45 = (new \DateTime())->modify('+45 days');
        $prepNext = [];
        foreach ($plantings as $planting) {
            if (in_array($planting['status'], ['empty', 'harvested'], true)) {
                $prepNext[] = array_merge($planting, ['reason' => 'ready_to_plant']);
            } elseif (!empty($planting['expected_harvest_at'])) {
                $harvestDate = new \DateTime($planting['expected_harvest_at']);
                if ($harvestDate <= $now45) {
                    $prepNext[] = array_merge($planting, ['reason' => 'harvest_soon']);
                }
            }
        }

        $climateRow  = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key = 'garden.climate_zone'");
        $climateZone = $climateRow['setting_value_text'] ?? 'mediterranean_sicily';

        $apiKeyRow        = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key = 'companion_api_key'");
        $hasCompanionApi  = !empty($apiKeyRow['setting_value_text']);

        // Family needs + seeds for suggestions and planting backlog
        // These queries are optional — wrap in try/catch so missing columns/tables never break the page
        $familyNeeds = [];
        $allSeeds    = [];
        try {
            // Check if needs_restock column exists; if not, run the migration
            $hasRestockCol = !empty($db->fetchAll("SHOW COLUMNS FROM seeds LIKE 'needs_restock'"));
            if (!$hasRestockCol) {
                $db->execute("ALTER TABLE seeds ADD COLUMN needs_restock TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_enabled");
            }

            $familyNeeds = $db->fetchAll(
                "SELECT fn.*, s.name AS seed_name, s.variety AS seed_variety,
                        s.planting_months, s.spacing_cm, s.needs_restock, s.id AS sid
                 FROM family_needs fn
                 LEFT JOIN seeds s ON s.id = fn.seed_id
                 ORDER BY fn.priority ASC, fn.vegetable_name ASC"
            );
            $allSeeds = $db->fetchAll(
                "SELECT id, name, variety, planting_months, spacing_cm, row_spacing_cm, sowing_depth_mm, days_to_maturity, notes FROM seeds WHERE needs_restock = 0 ORDER BY name ASC"
            );
        } catch (\Throwable $e) {
            // family_needs table doesn't exist yet or seeds schema mismatch — suggestions will be empty
            $familyNeeds = [];
            $allSeeds    = $db->fetchAll("SELECT id, name, variety, planting_months, spacing_cm, row_spacing_cm, sowing_depth_mm, days_to_maturity, notes FROM seeds ORDER BY name ASC") ?: [];
        }

        $currentMonth = (int)date('n');
        $plantedSeedIds   = array_filter(array_column($plantings, 'seed_id'));
        $plantedCropNames = array_map('strtolower', array_filter(array_column($plantings, 'crop_name')));

        // Planting backlog: next 6 months × family needs
        $backlog = [];
        for ($offset = 0; $offset < 6; $offset++) {
            $m = (($currentMonth - 1 + $offset) % 12) + 1;
            $items = [];
            foreach ($familyNeeds as $fn) {
                $pm = !empty($fn['planting_months']) ? json_decode($fn['planting_months'], true) : [];
                if (!in_array($m, $pm)) continue;
                $alreadyInBed = in_array($fn['sid'], $plantedSeedIds)
                             || in_array(strtolower($fn['vegetable_name']), $plantedCropNames);
                $items[] = [
                    'seed_id'       => (int)($fn['sid'] ?? 0),
                    'name'          => $fn['seed_name'] ?: $fn['vegetable_name'],
                    'variety'       => $fn['seed_variety'] ?? '',
                    'priority'      => (int)$fn['priority'],
                    'already_in_bed'=> $alreadyInBed,
                    'needs_restock' => !empty($fn['needs_restock']),
                    'spacing_cm'    => $fn['spacing_cm'] ?? null,
                ];
            }
            if (!empty($items)) {
                $backlog[] = ['month' => $m, 'items' => $items];
            }
        }

        Response::render('garden/bed', [
            'title'          => $item['name'] ?? 'Garden Bed',
            'item'           => $item,
            'meta'           => $meta,
            'bedRows'        => $bedRows,
            'lengthM'        => $lengthM,
            'widthM'         => $widthM,
            'lineDir'        => $lineDir,
            'parentGarden'   => $parentGarden,
            'plantings'      => $plantings,
            'plantingMap'    => $plantingMap,
            'prepNext'       => $prepNext,
            'climateZone'    => $climateZone,
            'hasCompanionApi'=> $hasCompanionApi,
            'currentMonth'   => $currentMonth,
            'familyNeeds'    => $familyNeeds,
            'allSeeds'       => $allSeeds,
            'backlog'        => $backlog,
        ]);
    }

    public function updateConfig(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db     = DB::getInstance();
        $itemId = (int)($params['id'] ?? 0);

        $item = $db->fetchOne("SELECT id FROM items WHERE id = ? AND deleted_at IS NULL", [$itemId]);
        if (!$item) {
            Response::json(['success' => false, 'error' => 'Not found']);
            return;
        }

        $bedRows   = max(1, (int)$request->post('bed_rows', 1));
        $lineDir   = in_array($request->post('line_direction', 'NS'), ['NS', 'EW'], true)
                     ? $request->post('line_direction', 'NS') : 'NS';
        $widthM    = max(0, (float)$request->post('bed_width_m', 0));
        $lengthM   = max(0, (float)$request->post('bed_length_m', 0));

        $upsert = "INSERT INTO item_meta (item_id, meta_key, meta_value_text, value_type, created_at, updated_at)
                   VALUES (?, ?, ?, 'text', NOW(), NOW())
                   ON DUPLICATE KEY UPDATE meta_value_text = VALUES(meta_value_text), updated_at = NOW()";

        $db->execute($upsert, [$itemId, 'bed_rows',       (string)$bedRows]);
        $db->execute($upsert, [$itemId, 'line_direction', $lineDir]);
        if ($widthM > 0)  $db->execute($upsert, [$itemId, 'bed_width_m',  (string)$widthM]);
        if ($lengthM > 0) $db->execute($upsert, [$itemId, 'bed_length_m', (string)$lengthM]);

        Response::json(['success' => true]);
    }

    public function storeLine(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db     = DB::getInstance();
        $itemId = (int)($params['id'] ?? 0);

        $this->ensureTable($db);

        $lineNumber = (int)$request->post('line_number', 1);

        $cropNameRaw = trim((string)$request->post('crop_name', ''));
        $cropName    = $cropNameRaw !== '' ? mb_substr($cropNameRaw, 0, 200) : null;

        $varietyRaw = trim((string)$request->post('variety', ''));
        $variety    = $varietyRaw !== '' ? mb_substr($varietyRaw, 0, 200) : null;

        $validStatuses = ['empty', 'planned', 'growing', 'harvested'];
        $statusIn      = (string)$request->post('status', 'empty');
        $status        = in_array($statusIn, $validStatuses, true) ? $statusIn : 'empty';

        $plantedAtRaw = trim((string)$request->post('planted_at', ''));
        $plantedAt    = $plantedAtRaw !== '' ? $plantedAtRaw : null;

        $expectedHarvestAtRaw = trim((string)$request->post('expected_harvest_at', ''));
        $expectedHarvestAt    = $expectedHarvestAtRaw !== '' ? $expectedHarvestAtRaw : null;

        $notesRaw = trim((string)$request->post('notes', ''));
        $notes    = $notesRaw !== '' ? mb_substr($notesRaw, 0, 1000) : null;

        $existing = $db->fetchOne(
            "SELECT id FROM garden_plantings WHERE item_id = ? AND line_number = ?",
            [$itemId, $lineNumber]
        );

        $seedIdRaw  = (int)$request->post('seed_id', 0);
        $seedId     = $seedIdRaw > 0 ? $seedIdRaw : null;
        $plantCount = ($request->post('plant_count', '') !== '') ? max(1, (int)$request->post('plant_count')) : null;

        if ($existing) {
            $db->execute(
                "UPDATE garden_plantings
                    SET crop_name = ?, variety = ?, status = ?, planted_at = ?,
                        expected_harvest_at = ?, notes = ?, seed_id = ?, plant_count = ?
                  WHERE item_id = ? AND line_number = ?",
                [$cropName, $variety, $status, $plantedAt, $expectedHarvestAt, $notes, $seedId, $plantCount, $itemId, $lineNumber]
            );
        } else {
            $db->execute(
                "INSERT INTO garden_plantings
                    (item_id, line_number, crop_name, variety, status, planted_at, expected_harvest_at, notes, seed_id, plant_count)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$itemId, $lineNumber, $cropName, $variety, $status, $plantedAt, $expectedHarvestAt, $notes, $seedId, $plantCount]
            );
        }

        if ($request->post('_ajax') === '1') {
            Response::json(['success' => true]);
        } else {
            Response::redirect('/items/' . $itemId . '/planting');
        }
    }

    public function trashLine(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $id = (int)($params['id'] ?? 0);

        $planting = $db->fetchOne("SELECT * FROM garden_plantings WHERE id = ?", [$id]);
        if (!$planting) {
            if ($request->post('_ajax') === '1') {
                Response::json(['success' => false, 'error' => 'Planting not found']);
            } else {
                Response::redirect('/');
            }
            return;
        }

        $itemId = (int)$planting['item_id'];
        $db->execute("DELETE FROM garden_plantings WHERE id = ?", [$id]);

        if ($request->post('_ajax') === '1') {
            Response::json(['success' => true]);
        } else {
            Response::redirect('/items/' . $itemId . '/planting');
        }
    }

    public function seedSuggestions(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db     = DB::getInstance();
        $this->ensureTable($db);

        $itemId     = (int)$request->get('item_id', 0);
        $month      = (int)($request->get('month', (int)date('n')));

        // Family needs with linked seeds
        $familyNeeds = $db->fetchAll(
            "SELECT fn.*, s.name AS seed_name, s.variety AS seed_variety,
                    s.planting_months, s.spacing_cm, s.needs_restock, s.stock_qty, s.id AS sid
             FROM family_needs fn
             LEFT JOIN seeds s ON s.id = fn.seed_id
             ORDER BY fn.priority ASC, fn.vegetable_name ASC"
        );

        // Current plantings for this bed — what seed_ids and crop names are already used
        $current = $db->fetchAll("SELECT crop_name, seed_id, status FROM garden_plantings WHERE item_id = ?", [$itemId]);
        $plantedSeedIds  = array_filter(array_column($current, 'seed_id'));
        $plantedCropNames = array_map('strtolower', array_filter(array_column($current, 'crop_name')));

        $suggestions = [];
        foreach ($familyNeeds as $fn) {
            $plantingMonths = !empty($fn['planting_months']) ? json_decode($fn['planting_months'], true) : [];
            $inSeason       = in_array($month, $plantingMonths);
            $alreadyInBed   = in_array($fn['sid'], $plantedSeedIds)
                           || in_array(strtolower($fn['vegetable_name']), $plantedCropNames);

            $suggestions[] = [
                'seed_id'        => (int)($fn['sid'] ?? 0),
                'name'           => $fn['seed_name'] ?: $fn['vegetable_name'],
                'vegetable_name' => $fn['vegetable_name'],
                'variety'        => $fn['seed_variety'] ?? '',
                'reason'         => 'family_need',
                'priority'       => (int)$fn['priority'],
                'in_season'      => $inSeason,
                'planting_months'=> $plantingMonths,
                'spacing_cm'     => $fn['spacing_cm'] ?? null,
                'needs_restock'  => !empty($fn['needs_restock']),
                'already_in_bed' => $alreadyInBed,
            ];
        }

        // Sort: in-season non-planted first, then out-of-season, then already-in-bed
        usort($suggestions, function ($a, $b) {
            if ($a['already_in_bed'] !== $b['already_in_bed']) return $a['already_in_bed'] ? 1 : -1;
            if ($a['in_season']      !== $b['in_season'])      return $a['in_season']      ? -1 : 1;
            return $a['priority'] - $b['priority'];
        });

        // All seeds for datalist
        $allSeeds = $db->fetchAll(
            "SELECT id, name, variety, planting_months, spacing_cm
             FROM seeds WHERE needs_restock = 0 ORDER BY name ASC"
        );

        // Planting backlog: next 6 months × family needs
        $backlog = [];
        for ($offset = 0; $offset < 6; $offset++) {
            $m = (($month - 1 + $offset) % 12) + 1;
            $items = [];
            foreach ($familyNeeds as $fn) {
                $pm = !empty($fn['planting_months']) ? json_decode($fn['planting_months'], true) : [];
                if (!in_array($m, $pm)) continue;
                $alreadyInBed = in_array($fn['sid'], $plantedSeedIds)
                             || in_array(strtolower($fn['vegetable_name']), $plantedCropNames);
                $items[] = [
                    'seed_id'       => (int)($fn['sid'] ?? 0),
                    'name'          => $fn['seed_name'] ?: $fn['vegetable_name'],
                    'variety'       => $fn['seed_variety'] ?? '',
                    'priority'      => (int)$fn['priority'],
                    'already_in_bed'=> $alreadyInBed,
                    'needs_restock' => !empty($fn['needs_restock']),
                ];
            }
            if (!empty($items)) {
                $backlog[] = ['month' => $m, 'items' => $items];
            }
        }

        Response::json([
            'success'     => true,
            'suggestions' => array_slice($suggestions, 0, 8),
            'all_seeds'   => $allSeeds,
            'backlog'     => $backlog,
            'month'       => $month,
        ]);
    }

    public function harvestLine(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db  = DB::getInstance();
        $id  = (int)($params['id'] ?? 0);

        $planting = $db->fetchOne("SELECT * FROM garden_plantings WHERE id = ?", [$id]);
        if (!$planting) {
            Response::json(['success' => false, 'error' => 'Not found']);
            return;
        }

        $cropName = $planting['crop_name'] ?? 'Unknown crop';
        $itemId   = (int)$planting['item_id'];

        // Mark line as harvested and free it
        $db->execute(
            "UPDATE garden_plantings SET status='harvested', crop_name=NULL, variety=NULL, seed_id=NULL, plant_count=NULL WHERE id=?",
            [$id]
        );

        // Log harvest record (best-effort — silent on schema mismatch)
        $qty  = (float)$request->post('qty', 0);
        $unit = trim($request->post('unit', 'items')) ?: 'items';
        $notes = trim($request->post('notes', ''));
        if ($qty > 0) {
            try {
                $db->execute(
                    "INSERT INTO harvest_entries (item_id, harvest_type, quantity, unit, notes, recorded_at, created_at)
                     VALUES (?, ?, ?, ?, ?, CURDATE(), NOW())",
                    [$itemId, $cropName, $qty, $unit, $notes]
                );
            } catch (\Throwable $e) {
                // silent — harvest_entries schema may differ
            }
        }

        Response::json(['success' => true]);
    }

    public function adjustQty(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db    = DB::getInstance();
        $id    = (int)($params['id'] ?? 0);
        $delta = (int)$request->post('delta', 0);
        $db->execute(
            "UPDATE garden_plantings SET plant_count = GREATEST(1, COALESCE(plant_count,1) + ?) WHERE id = ?",
            [$delta, $id]
        );
        Response::json(['success' => true]);
    }

    public function companions(Request $request, array $params = []): void
    {
        $this->requireAuth();
        try {
            $crop     = trim((string)$request->get('crop', ''));
            $bedCrops = array_values(array_filter(array_map('trim', explode(',', (string)$request->get('bed_crops', '')))));

            if ($crop === '') {
                Response::json(['success' => false, 'error' => 'No crop specified']);
                return;
            }

            $db    = DB::getInstance();
            $seeds = $db->fetchAll(
                "SELECT name, companions, antagonists FROM seeds ORDER BY name ASC"
            ) ?: [];

            $data = self::rankCompanions($crop, $bedCrops, $seeds);

            if (empty($data['companions']) && empty($data['antagonists'])) {
                Response::json(['success' => false, 'error' => 'No companion data found — add companions & avoid lists to your seeds to use this feature']);
                return;
            }

            Response::json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Pure data-driven companion ranking — no AI, fully reusable.
     *
     * Scores all seeds against $crop using the companions/antagonists JSON arrays stored
     * in the seed bank. A seed earns points if:
     *   +3  — target lists it as a companion
     *   +3  — it lists the target as a companion
     *   disqualified — either side lists the other as an antagonist
     *
     * Additionally, if $bedCrops are supplied, any candidate that conflicts with an
     * existing bed plant loses 2 points and gets a warning in its reason text.
     * Bed plants that conflict with the target crop are returned as antagonists.
     *
     * @param  string   $crop      Crop name to find companions for
     * @param  string[] $bedCrops  Other crops already growing in the same bed
     * @param  array[]  $seeds     Seed rows with keys: name, companions, antagonists
     * @return array{companions: array, antagonists: array, tip: null}
     */
    public static function rankCompanions(string $crop, array $bedCrops, array $seeds): array
    {
        $cropLc = strtolower(trim($crop));

        $decode = fn($json) => array_map('strtolower', json_decode($json ?? '[]', true) ?: []);

        // Index seeds by lowercase name for quick lookup
        $byName = [];
        foreach ($seeds as $s) {
            $byName[strtolower($s['name'])] = $s;
        }

        // Find target — exact match first, then substring
        $target = $byName[$cropLc] ?? null;
        if (!$target) {
            foreach ($byName as $key => $s) {
                if (str_contains($key, $cropLc) || str_contains($cropLc, $key)) {
                    $target = $s;
                    break;
                }
            }
        }

        $tCompanions  = $target ? $decode($target['companions'])  : [];
        $tAntagonists = $target ? $decode($target['antagonists']) : [];

        // Build bed-crop antagonist sets for conflict checking
        $bedAntagonistSets = [];
        foreach ($bedCrops as $bc) {
            $bcLc = strtolower($bc);
            $bcSeed = $byName[$bcLc] ?? null;
            $bedAntagonistSets[$bcLc] = $bcSeed ? $decode($bcSeed['antagonists']) : [];
        }

        // Score every seed as a candidate companion
        $candidates = [];
        foreach ($seeds as $s) {
            $sLc = strtolower($s['name']);
            if ($sLc === $cropLc) continue;

            $sCompanions  = $decode($s['companions']);
            $sAntagonists = $decode($s['antagonists']);

            // Disqualify if mutual antagonist
            if (in_array($sLc, $tAntagonists, true))  continue;
            if (in_array($cropLc, $sAntagonists, true)) continue;

            $score   = 0;
            $reasons = [];

            if (in_array($sLc, $tCompanions, true)) {
                $score += 3;
                $reasons[] = "companion for {$crop}";
            }
            if (in_array($cropLc, $sCompanions, true)) {
                $score += 3;
                $reasons[] = "benefits from growing near {$crop}";
            }

            if ($score === 0) continue;

            // Check conflicts with existing bed crops
            $warnings = [];
            foreach ($bedCrops as $bc) {
                $bcLc = strtolower($bc);
                if ($bcLc === $sLc) continue;
                $bcAnts = $bedAntagonistSets[$bcLc] ?? [];
                if (in_array($sLc, $bcAnts, true) || in_array($bcLc, $sAntagonists, true)) {
                    $warnings[] = "conflicts with {$bc} in bed";
                    $score -= 2;
                }
            }

            $reason = ucfirst(implode('; ', $reasons));
            if ($warnings) $reason .= ' ⚠ ' . implode(', ', $warnings);

            $candidates[] = ['name' => $s['name'], 'score' => $score, 'reason' => $reason];
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        // Antagonists = bed crops that conflict with the target
        $antagonists = [];
        foreach ($bedCrops as $bc) {
            $bcLc   = strtolower($bc);
            $bcAnts = $bedAntagonistSets[$bcLc] ?? [];
            if (in_array($bcLc, $tAntagonists, true) || in_array($cropLc, $bcAnts, true)) {
                $antagonists[] = ['name' => $bc, 'reason' => "already in bed — avoid planting near {$crop}"];
            }
        }

        return [
            'companions'  => array_slice($candidates, 0, 6),
            'antagonists' => $antagonists,
            'tip'         => null,
        ];
    }
}
