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
                "SELECT id, name, variety, planting_months, spacing_cm FROM seeds WHERE needs_restock = 0 ORDER BY name ASC"
            );
        } catch (\Throwable $e) {
            // family_needs table doesn't exist yet or seeds schema mismatch — suggestions will be empty
            $familyNeeds = [];
            $allSeeds    = $db->fetchAll("SELECT id, name, variety, planting_months, spacing_cm FROM seeds ORDER BY name ASC") ?: [];
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

        if ($request->isAjax()) {
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
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Planting not found']);
            } else {
                Response::redirect('/');
            }
            return;
        }

        $itemId = (int)$planting['item_id'];
        $db->execute("DELETE FROM garden_plantings WHERE id = ?", [$id]);

        if ($request->isAjax()) {
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

    public function companions(Request $request, array $params = []): void
    {
        $this->requireAuth();

        try {
            $crop  = trim((string)$request->get('crop', ''));
            $month = (int)($request->get('month', date('n')));

            $db = DB::getInstance();

            $getSetting = function (string $key, string $default = '') use ($db): string {
                $row = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key = ? LIMIT 1", [$key]);
                return $row ? (string)($row['setting_value_text'] ?? $default) : $default;
            };

            $apiKey      = $getSetting('companion_api_key');
            $provider    = $getSetting('companion_api_provider', 'openai');
            $model       = $getSetting('companion_api_model', 'gpt-4o-mini');
            $customUrl   = $getSetting('companion_api_url');
            $climateZone = $getSetting('garden.climate_zone', 'mediterranean_sicily');

            if ($apiKey === '' || $crop === '') {
                Response::json(['success' => false, 'error' => 'API not configured']);
                return;
            }

            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March',    4 => 'April',
                5 => 'May',     6 => 'June',      7 => 'July',     8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ];
            $monthName = $monthNames[$month] ?? 'the current month';

            $prompt = "You are a companion planting expert. The crop \"{$crop}\" is being grown in {$monthName} in a {$climateZone} climate zone. Provide:\n1. Best companion plants (3-5)\n2. Plants to avoid/antagonists (2-3)\n3. A brief succession planting tip for this line after this crop\n\nRespond ONLY with valid JSON in this exact format:\n{\"companions\":[{\"name\":\"Plant\",\"reason\":\"why\"}],\"antagonists\":[{\"name\":\"Plant\",\"reason\":\"why\"}],\"tip\":\"succession tip\"}";

            if (strtolower($provider) === 'anthropic') {
                $url     = 'https://api.anthropic.com/v1/messages';
                $payload = json_encode([
                    'model'      => $model,
                    'max_tokens' => 512,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
                $headers = [
                    'Content-Type: application/json',
                    'x-api-key: ' . $apiKey,
                    'anthropic-version: 2023-06-01',
                ];
            } else {
                $url     = ($customUrl !== '') ? $customUrl : 'https://api.openai.com/v1/chat/completions';
                $payload = json_encode([
                    'model'      => $model,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 512,
                ]);
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ];
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
            ]);
            $raw      = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 400) {
                Response::json(['success' => false, 'error' => 'API error ' . $httpCode]);
                return;
            }

            $responseData = json_decode($raw, true);

            if (strtolower($provider) === 'anthropic') {
                $text = $responseData['content'][0]['text'] ?? '';
            } else {
                $text = $responseData['choices'][0]['message']['content'] ?? '';
            }

            $text = trim((string)$text);
            $data = null;
            if (preg_match('/\{.*\}/s', $text, $m)) {
                $data = json_decode($m[0], true);
            }

            Response::json(['success' => true, 'data' => $data]);

        } catch (\Throwable $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
