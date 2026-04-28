<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;
use App\Support\GardenSchema;
use App\Support\GardenHelpers;

class GardenBedController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    private function ensureTable(DB $db): void
    {
        GardenSchema::ensure($db);
    }

    /**
     * Load a bed and assemble all data needed for the redesigned views (merged,
     * plan-inline, plan-timeline). Returns null if bed not found.
     *
     * Output shape:
     *   [
     *     'item' => items row,
     *     'meta' => item_meta map,
     *     'parentGarden' => items row|null,
     *     'numLines', 'lengthM', 'widthM', 'lineDir',
     *     'bed' => [
     *        'id','name','garden','lengthM','widthM','last_watered_at',
     *        'lines' => [ /* see below *\/ ]
     *     ],
     *     'catalog' => [crop array,...],
     *     'cropsById' => map id => crop,
     *   ]
     *
     * Each line:
     *   id (line_number), lineNumber, lengthCm, status, sown_at, empty_since,
     *   last_watered_at, succession (null|{cropId,startsOn}),
     *   succession_starts_on, rotation_history,
     *   plantings => [{id, cropId, plants, sown_at, name, color, ...},...]
     */
    private function loadBed(DB $db, int $id): ?array
    {
        $item = $db->fetchOne("SELECT * FROM items WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$item) return null;

        $metaRows = $db->fetchAll("SELECT meta_key, meta_value_text FROM item_meta WHERE item_id = ?", [$id]);
        $meta = [];
        foreach ($metaRows as $row) {
            $meta[$row['meta_key']] = $row['meta_value_text'];
        }

        $bedRows = max(1, (int)($meta['bed_rows'] ?? 1));
        $lengthM = (float)($meta['bed_length_m'] ?? 0);
        $widthM  = (float)($meta['bed_width_m'] ?? 0);
        $lineDir = $meta['line_direction'] ?? 'NS';
        $lengthCm = (int)round($lengthM * 100);
        if ($lengthCm <= 0) $lengthCm = 400;

        $parentGarden = null;
        if (!empty($item['parent_id'])) {
            $parentGarden = $db->fetchOne("SELECT * FROM items WHERE id = ?", [(int)$item['parent_id']]);
        }

        // Load seed catalog with new fields
        $catalog  = $this->loadCatalog($db);
        $cropsById = [];
        foreach ($catalog as $c) { $cropsById[(int)$c['id']] = $c; }

        // Hydrate garden_bed_lines for each line_number 1..bedRows (idempotent)
        $existingLines = $db->fetchAll(
            "SELECT * FROM garden_bed_lines WHERE item_id = ? ORDER BY line_number ASC",
            [$id]
        );
        $linesByNum = [];
        foreach ($existingLines as $l) { $linesByNum[(int)$l['line_number']] = $l; }
        for ($n = 1; $n <= $bedRows; $n++) {
            if (!isset($linesByNum[$n])) {
                $db->execute(
                    "INSERT INTO garden_bed_lines (item_id, line_number, length_cm, rotation_history)
                     VALUES (?, ?, ?, '[]')",
                    [$id, $n, $lengthCm]
                );
                $linesByNum[$n] = [
                    'id' => null,
                    'item_id' => $id,
                    'line_number' => $n,
                    'length_cm' => $lengthCm,
                    'sown_at' => null,
                    'empty_since' => null,
                    'last_watered_at' => null,
                    'succession_crop_id' => null,
                    'succession_starts_on' => null,
                    'rotation_history' => '[]',
                ];
            }
        }

        // Plantings indexed by line_number; sort_order first (drag-reorder), then id
        $plantings = $db->fetchAll(
            "SELECT * FROM garden_plantings WHERE item_id = ? ORDER BY line_number ASC, sort_order ASC, id ASC",
            [$id]
        );
        $plantingMap = [];
        foreach ($plantings as $p) {
            $plantingMap[(int)$p['line_number']][] = $p;
        }

        // Build rich line objects
        $lines = [];
        for ($n = 1; $n <= $bedRows; $n++) {
            $lstate = $linesByNum[$n] ?? null;
            $rawPlantings = $plantingMap[$n] ?? [];
            $linePlantings = [];
            $hasGrowing = false;
            foreach ($rawPlantings as $rp) {
                $cid = (int)($rp['seed_id'] ?? 0);
                if ($cid > 0 && !isset($cropsById[$cid])) continue;
                $cnt = max(0, (int)($rp['plant_count'] ?? 1));
                if ($cnt <= 0) continue;
                $sownAt = $rp['sown_at'] ?? $rp['planted_at'] ?? null;
                $linePlantings[] = [
                    'id'         => (int)$rp['id'],
                    'cropId'     => $cid,
                    'plants'     => $cnt,
                    'sown_at'    => $sownAt,
                    'crop_name'  => $rp['crop_name'] ?? ($cropsById[$cid]['name'] ?? ''),
                    'status'     => $rp['status'] ?? 'growing',
                ];
                if (($rp['status'] ?? '') === 'growing') $hasGrowing = true;
            }

            $hist = [];
            if (!empty($lstate['rotation_history'])) {
                $hist = json_decode($lstate['rotation_history'], true) ?: [];
            }

            $status = 'empty';
            if (!empty($linePlantings) && $hasGrowing) $status = 'growing';
            elseif (!empty($linePlantings))            $status = 'growing';
            elseif (!empty($lstate['succession_crop_id'])) $status = 'planned';
            elseif (!empty($lstate['empty_since']))    $status = 'harvested';

            $succession = null;
            if (!empty($lstate['succession_crop_id'])) {
                $succession = [
                    'cropId'   => (int)$lstate['succession_crop_id'],
                    'startsOn' => $lstate['succession_starts_on'],
                ];
            }

            $lines[] = [
                'id'                   => $n,
                'lineNumber'           => $n,
                'lengthCm'             => (int)($lstate['length_cm'] ?? $lengthCm),
                'status'               => $status,
                'sown_at'              => $lstate['sown_at'] ?? null,
                'empty_since'          => $lstate['empty_since'] ?? null,
                'last_watered_at'      => $lstate['last_watered_at'] ?? null,
                'succession'           => $succession,
                'succession_starts_on' => $lstate['succession_starts_on'] ?? null,
                'rotation_history'     => $hist,
                'plantings'            => $linePlantings,
            ];
        }

        $bed = [
            'id'              => (int)$item['id'],
            'name'            => $item['name'] ?? 'Bed',
            'garden'          => $parentGarden['name'] ?? '',
            'lengthM'         => $lengthM,
            'widthM'          => $widthM,
            'lineDir'         => $lineDir,
            'last_watered_at' => null,
            'lines'           => $lines,
        ];

        return [
            'item'         => $item,
            'meta'         => $meta,
            'parentGarden' => $parentGarden,
            'numLines'     => $bedRows,
            'lengthM'      => $lengthM,
            'widthM'       => $widthM,
            'lineDir'      => $lineDir,
            'bed'          => $bed,
            'catalog'      => $catalog,
            'cropsById'    => $cropsById,
        ];
    }

    /** Load the seed catalog with the redesign fields (family/season/emoji/color). */
    private function loadCatalog(DB $db): array
    {
        try {
            $rows = $db->fetchAll(
                "SELECT id, name, variety, days_to_maturity, spacing_cm, family, season, emoji, color
                 FROM seeds
                 WHERE COALESCE(stock_enabled,1) = 1
                 ORDER BY name ASC, variety ASC"
            ) ?: [];
        } catch (\Throwable $e) {
            $rows = $db->fetchAll(
                "SELECT id, name, variety, days_to_maturity, spacing_cm
                 FROM seeds ORDER BY name ASC"
            ) ?: [];
        }
        $out = [];
        foreach ($rows as $r) {
            $r['id']                = (int)$r['id'];
            $r['days_to_maturity']  = (int)($r['days_to_maturity'] ?? 60) ?: 60;
            $r['spacing_cm']        = max(1, (int)($r['spacing_cm'] ?? 5));
            $r['family']            = $r['family'] ?? 'other';
            $r['season']            = $r['season'] ?? 'any';
            $r['emoji']             = $r['emoji'] ?: GardenHelpers::cropEmoji($r);
            $r['color']             = GardenHelpers::cropColor($r);
            $out[] = $r;
        }
        return $out;
    }

    public function show(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $id = (int)($params['id'] ?? 0);

        $this->ensureTable($db);

        $data = $this->loadBed($db, $id);
        if (!$data) {
            http_response_code(404);
            Response::render('errors/404', ['title' => 'Not Found']);
            return;
        }

        $today = GardenHelpers::todayIso();
        Response::render('garden/bed', [
            'title'        => $data['item']['name'] ?? 'Garden Bed',
            'mode'         => 'merged',
            'today'        => $today,
            'item'         => $data['item'],
            'meta'         => $data['meta'],
            'parentGarden' => $data['parentGarden'],
            'numLines'     => $data['numLines'],
            'lengthM'      => $data['lengthM'],
            'widthM'       => $data['widthM'],
            'lineDir'      => $data['lineDir'],
            'bed'          => $data['bed'],
            'catalog'      => $data['catalog'],
            'cropsById'    => $data['cropsById'],
        ]);
    }

    public function showPlanInline(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $id = (int)($params['id'] ?? 0);
        $this->ensureTable($db);
        $data = $this->loadBed($db, $id);
        if (!$data) { http_response_code(404); Response::render('errors/404', ['title' => 'Not Found']); return; }

        Response::render('garden/plan_inline', [
            'title'        => ($data['item']['name'] ?? 'Garden Bed') . ' — Plan',
            'mode'         => 'inline',
            'today'        => GardenHelpers::todayIso(),
            'item'         => $data['item'],
            'meta'         => $data['meta'],
            'parentGarden' => $data['parentGarden'],
            'numLines'     => $data['numLines'],
            'lengthM'      => $data['lengthM'],
            'widthM'       => $data['widthM'],
            'lineDir'      => $data['lineDir'],
            'bed'          => $data['bed'],
            'catalog'      => $data['catalog'],
            'cropsById'    => $data['cropsById'],
        ]);
    }

    public function showPlanTimeline(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $id = (int)($params['id'] ?? 0);
        $this->ensureTable($db);
        $data = $this->loadBed($db, $id);
        if (!$data) { http_response_code(404); Response::render('errors/404', ['title' => 'Not Found']); return; }

        Response::render('garden/plan_timeline', [
            'title'        => ($data['item']['name'] ?? 'Garden Bed') . ' — Timeline',
            'mode'         => 'timeline',
            'today'        => GardenHelpers::todayIso(),
            'item'         => $data['item'],
            'meta'         => $data['meta'],
            'parentGarden' => $data['parentGarden'],
            'numLines'     => $data['numLines'],
            'lengthM'      => $data['lengthM'],
            'widthM'       => $data['widthM'],
            'lineDir'      => $data['lineDir'],
            'bed'          => $data['bed'],
            'catalog'      => $data['catalog'],
            'cropsById'    => $data['cropsById'],
        ]);
    }

    /** AJAX: tap-to-plant. Adds 1 plant of cropId to (item, line). Creates planting row if missing. */
    public function tapPlant(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTable($db);

        $itemId  = (int)($params['id'] ?? 0);
        $lineNum = max(1, (int)$request->post('line_number', 1));
        $cropId  = (int)$request->post('crop_id', 0);
        $count   = max(1, (int)$request->post('count', 1));

        if ($cropId <= 0) { Response::json(['success' => false, 'error' => 'crop_id required']); return; }

        try {
            $crop = $db->fetchOne("SELECT id, name, days_to_maturity, spacing_cm FROM seeds WHERE id = ?", [$cropId]);
            if (!$crop) { Response::json(['success' => false, 'error' => 'Crop not found']); return; }

            $today = GardenHelpers::todayIso();

            $existing = $db->fetchOne(
                "SELECT id, plant_count FROM garden_plantings WHERE item_id = ? AND line_number = ? AND seed_id = ? AND status IN ('growing','planned','sown') ORDER BY id DESC LIMIT 1",
                [$itemId, $lineNum, $cropId]
            );
            if ($existing) {
                $newCount = (int)$existing['plant_count'] + $count;
                $db->execute(
                    "UPDATE garden_plantings SET plant_count = ? WHERE id = ?",
                    [$newCount, (int)$existing['id']]
                );
                $plantingId = (int)$existing['id'];
            } else {
                $expected = null;
                if (!empty($crop['days_to_maturity'])) {
                    $expected = GardenHelpers::addDays($today, (int)$crop['days_to_maturity']);
                }
                $db->execute(
                    "INSERT INTO garden_plantings (item_id, line_number, crop_name, status, planted_at, expected_harvest_at, seed_id, plant_count)
                     VALUES (?, ?, ?, 'growing', ?, ?, ?, ?)",
                    [$itemId, $lineNum, $crop['name'], $today, $expected, $cropId, $count]
                );
                $plantingId = (int)$db->lastInsertId();
            }

            // ensure a garden_bed_lines row, set sown_at if blank, clear empty_since
            $existingLine = $db->fetchOne(
                "SELECT id FROM garden_bed_lines WHERE item_id = ? AND line_number = ?",
                [$itemId, $lineNum]
            );
            if ($existingLine) {
                $db->execute(
                    "UPDATE garden_bed_lines
                        SET sown_at = COALESCE(sown_at, ?), empty_since = NULL
                      WHERE id = ?",
                    [$today, (int)$existingLine['id']]
                );
            } else {
                $db->execute(
                    "INSERT INTO garden_bed_lines (item_id, line_number, sown_at, rotation_history) VALUES (?, ?, ?, '[]')",
                    [$itemId, $lineNum, $today]
                );
            }
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        Response::json(['success' => true, 'planting_id' => $plantingId]);
    }

    /** AJAX: clear all plantings on a single line. Sets status=harvested with empty_since=today. */
    public function clearLine(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTable($db);

        $itemId  = (int)($params['id'] ?? 0);
        $lineNum = max(1, (int)$request->post('line_number', 1));
        $today   = GardenHelpers::todayIso();

        $db->execute(
            "DELETE FROM garden_plantings WHERE item_id = ? AND line_number = ?",
            [$itemId, $lineNum]
        );
        $existingLine = $db->fetchOne(
            "SELECT id FROM garden_bed_lines WHERE item_id = ? AND line_number = ?",
            [$itemId, $lineNum]
        );
        if ($existingLine) {
            $db->execute(
                "UPDATE garden_bed_lines SET empty_since = ?, sown_at = NULL WHERE id = ?",
                [$today, (int)$existingLine['id']]
            );
        } else {
            $db->execute(
                "INSERT INTO garden_bed_lines (item_id, line_number, empty_since, rotation_history) VALUES (?, ?, ?, '[]')",
                [$itemId, $lineNum, $today]
            );
        }

        Response::json(['success' => true]);
    }

    /** AJAX: set succession (cropId + startsOn) for a line. */
    public function setSuccession(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTable($db);

        $itemId   = (int)($params['id'] ?? 0);
        $lineNum  = max(1, (int)$request->post('line_number', 1));
        $cropId   = (int)$request->post('crop_id', 0);
        $startsOn = trim((string)$request->post('starts_on', ''));

        if ($cropId <= 0) { Response::json(['success' => false, 'error' => 'crop_id required']); return; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startsOn)) {
            $startsOn = GardenHelpers::addDays(GardenHelpers::todayIso(), 7);
        }

        $existingLine = $db->fetchOne(
            "SELECT id FROM garden_bed_lines WHERE item_id = ? AND line_number = ?",
            [$itemId, $lineNum]
        );
        if ($existingLine) {
            $db->execute(
                "UPDATE garden_bed_lines SET succession_crop_id = ?, succession_starts_on = ? WHERE id = ?",
                [$cropId, $startsOn, (int)$existingLine['id']]
            );
        } else {
            $db->execute(
                "INSERT INTO garden_bed_lines (item_id, line_number, succession_crop_id, succession_starts_on, rotation_history) VALUES (?, ?, ?, ?, '[]')",
                [$itemId, $lineNum, $cropId, $startsOn]
            );
        }

        Response::json(['success' => true, 'starts_on' => $startsOn]);
    }

    /** AJAX: clear succession for a line. */
    public function clearSuccession(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTable($db);

        $itemId  = (int)($params['id'] ?? 0);
        $lineNum = max(1, (int)$request->post('line_number', 1));

        $db->execute(
            "UPDATE garden_bed_lines SET succession_crop_id = NULL, succession_starts_on = NULL WHERE item_id = ? AND line_number = ?",
            [$itemId, $lineNum]
        );
        Response::json(['success' => true]);
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

        $plantingId = (int)$request->post('planting_id', 0);
        $seedIdRaw  = (int)$request->post('seed_id', 0);
        $seedId     = $seedIdRaw > 0 ? $seedIdRaw : null;
        $plantCount = ($request->post('plant_count', '') !== '') ? max(1, (int)$request->post('plant_count')) : null;

        if ($plantingId > 0) {
            // Update a specific existing planting row
            $db->execute(
                "UPDATE garden_plantings
                    SET crop_name = ?, variety = ?, status = ?, planted_at = ?,
                        expected_harvest_at = ?, notes = ?, seed_id = ?, plant_count = ?
                  WHERE id = ? AND item_id = ?",
                [$cropName, $variety, $status, $plantedAt, $expectedHarvestAt, $notes, $seedId, $plantCount, $plantingId, $itemId]
            );
        } else {
            // Insert new planting — multiple per line_number are allowed
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
        $this->ensureTable($db);

        $planting = $db->fetchOne("SELECT * FROM garden_plantings WHERE id = ?", [$id]);
        if (!$planting) {
            Response::json(['success' => false, 'error' => 'Not found']);
            return;
        }

        $cropName = $planting['crop_name'] ?? 'Unknown crop';
        $itemId   = (int)$planting['item_id'];
        $lineNum  = (int)$planting['line_number'];
        $seedId   = (int)($planting['seed_id'] ?? 0);

        // Append to line's rotation_history before clearing
        $year = (int)date('Y');
        $season = GardenHelpers::seasonOfMonth((int)date('n'));
        if ($seedId > 0) {
            try {
                $row = $db->fetchOne(
                    "SELECT id, rotation_history FROM garden_bed_lines WHERE item_id = ? AND line_number = ?",
                    [$itemId, $lineNum]
                );
                $hist = [];
                if ($row && !empty($row['rotation_history'])) {
                    $hist = json_decode($row['rotation_history'], true) ?: [];
                }
                $hist[] = ['year' => $year, 'season' => $season, 'cropId' => $seedId];
                $today = GardenHelpers::todayIso();
                if ($row) {
                    $db->execute(
                        "UPDATE garden_bed_lines SET rotation_history = ?, empty_since = ?, sown_at = NULL WHERE id = ?",
                        [json_encode(array_values($hist)), $today, (int)$row['id']]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO garden_bed_lines (item_id, line_number, empty_since, rotation_history) VALUES (?, ?, ?, ?)",
                        [$itemId, $lineNum, $today, json_encode($hist)]
                    );
                }
            } catch (\Throwable $e) {
                // silent — line bookkeeping is best-effort
            }
        }

        // Mark planting as harvested
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

    /** AJAX: clear all plantings on a single line via line_number (used by Harvest blackout 'clear all crops'). */
    public function harvestClearLine(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTable($db);

        $itemId  = (int)($params['id'] ?? 0);
        $lineNum = max(1, (int)$request->post('line_number', 1));
        $today   = GardenHelpers::todayIso();
        $year    = (int)date('Y');
        $season  = GardenHelpers::seasonOfMonth((int)date('n'));

        // Append everything currently planted to rotation_history
        try {
            $rows = $db->fetchAll(
                "SELECT seed_id FROM garden_plantings WHERE item_id = ? AND line_number = ? AND seed_id IS NOT NULL",
                [$itemId, $lineNum]
            );
            $row = $db->fetchOne(
                "SELECT id, rotation_history FROM garden_bed_lines WHERE item_id = ? AND line_number = ?",
                [$itemId, $lineNum]
            );
            $hist = [];
            if ($row && !empty($row['rotation_history'])) {
                $hist = json_decode($row['rotation_history'], true) ?: [];
            }
            foreach ($rows as $r) {
                $hist[] = ['year' => $year, 'season' => $season, 'cropId' => (int)$r['seed_id']];
            }
            if ($row) {
                $db->execute(
                    "UPDATE garden_bed_lines SET rotation_history = ?, empty_since = ?, sown_at = NULL WHERE id = ?",
                    [json_encode(array_values($hist)), $today, (int)$row['id']]
                );
            } else {
                $db->execute(
                    "INSERT INTO garden_bed_lines (item_id, line_number, empty_since, rotation_history) VALUES (?, ?, ?, ?)",
                    [$itemId, $lineNum, $today, json_encode($hist)]
                );
            }
        } catch (\Throwable $e) {
            // silent
        }

        $qty   = (float)$request->post('qty', 0);
        $unit  = trim($request->post('unit', 'items')) ?: 'items';
        $notes = trim($request->post('notes', ''));
        if ($qty > 0) {
            try {
                $names = $db->fetchAll(
                    "SELECT crop_name FROM garden_plantings WHERE item_id = ? AND line_number = ?",
                    [$itemId, $lineNum]
                );
                $name = $names[0]['crop_name'] ?? 'Mixed crop';
                $db->execute(
                    "INSERT INTO harvest_entries (item_id, harvest_type, quantity, unit, notes, recorded_at, created_at)
                     VALUES (?, ?, ?, ?, ?, CURDATE(), NOW())",
                    [$itemId, $name, $qty, $unit, $notes]
                );
            } catch (\Throwable $e) { /* silent */ }
        }

        $db->execute(
            "DELETE FROM garden_plantings WHERE item_id = ? AND line_number = ?",
            [$itemId, $lineNum]
        );

        Response::json(['success' => true]);
    }

    public function adjustQty(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $id = (int)($params['id'] ?? 0);

        // Absolute set takes priority over delta when provided
        if ($request->post('count', null) !== null) {
            $count = max(1, (int)$request->post('count', 1));
            $db->execute("UPDATE garden_plantings SET plant_count = ? WHERE id = ?", [$count, $id]);
        } else {
            $delta = (int)$request->post('delta', 0);
            $db->execute(
                "UPDATE garden_plantings SET plant_count = GREATEST(1, COALESCE(plant_count,1) + ?) WHERE id = ?",
                [$delta, $id]
            );
        }
        Response::json(['success' => true]);
    }

    /** AJAX: remove a single planting row entirely. */
    public function removePlanting(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $id = (int)($params['id'] ?? 0);
        $db->execute("DELETE FROM garden_plantings WHERE id = ?", [$id]);
        Response::json(['success' => true]);
    }

    /**
     * AJAX: set the sown date for one line.
     * POST: line_number, sown_at (YYYY-MM-DD).
     * Returns: success, sown_at_label, harvest_at, harvest_at_label, days_to_harvest.
     */
    public function setLineSown(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTable($db);

        $itemId  = (int)($params['id'] ?? 0);
        $lineNum = max(1, (int)$request->post('line_number', 0));
        $sownRaw = trim((string)$request->post('sown_at', ''));

        $ts = strtotime($sownRaw);
        if ($ts === false) { Response::json(['success' => false, 'error' => 'Invalid date']); return; }
        $sownIso = date('Y-m-d', $ts);

        try {
            $existing = $db->fetchOne("SELECT id FROM garden_bed_lines WHERE item_id = ? AND line_number = ?", [$itemId, $lineNum]);
            if ($existing) {
                $db->execute("UPDATE garden_bed_lines SET sown_at = ? WHERE id = ?", [$sownIso, (int)$existing['id']]);
            } else {
                $db->execute("INSERT INTO garden_bed_lines (item_id, line_number, sown_at, rotation_history) VALUES (?, ?, ?, '[]')", [$itemId, $lineNum, $sownIso]);
            }
            // Mirror onto active plantings so maturity computations pick it up immediately
            $db->execute("UPDATE garden_plantings SET planted_at = ? WHERE item_id = ? AND line_number = ? AND status IN ('growing','planned','sown')", [$sownIso, $itemId, $lineNum]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        // Compute new harvest date based on the longest days_to_maturity among
        // active plantings on this line; fall back to 60 if unknown.
        $rows = $db->fetchAll(
            "SELECT s.days_to_maturity FROM garden_plantings p
             LEFT JOIN seeds s ON s.id = p.seed_id
             WHERE p.item_id = ? AND p.line_number = ? AND p.status IN ('growing','planned','sown')",
            [$itemId, $lineNum]
        );
        $dth = 60;
        foreach ($rows as $r) {
            $d = (int)($r['days_to_maturity'] ?? 0);
            if ($d > $dth) $dth = $d;
        }
        $harvestIso = date('Y-m-d', $ts + $dth * 86400);
        $today      = strtotime(\App\Support\GardenHelpers::todayIso());
        $daysOut    = (int)round(($ts + $dth * 86400 - $today) / 86400);

        Response::json([
            'success'           => true,
            'sown_at'           => $sownIso,
            'sown_at_label'     => \App\Support\GardenHelpers::fmtDate($sownIso),
            'harvest_at'        => $harvestIso,
            'harvest_at_label'  => \App\Support\GardenHelpers::fmtDate($harvestIso),
            'days_to_harvest'   => $daysOut,
        ]);
    }

    /**
     * AJAX: reorder plantings within a single line.
     * POST: planting_ids[] in the new order. Writes incremental sort_order.
     */
    public function reorderPlantings(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTable($db);

        $ids = $request->post('planting_ids', []);
        if (!is_array($ids) || empty($ids)) { Response::json(['success' => false, 'error' => 'No ids']); return; }

        try {
            foreach ($ids as $idx => $pid) {
                $db->execute("UPDATE garden_plantings SET sort_order = ? WHERE id = ?", [$idx + 1, (int)$pid]);
            }
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
            return;
        }
        Response::json(['success' => true]);
    }

    public function companions(Request $request, array $params = []): void
    {
        $this->requireAuth();
        try {
            $crop          = trim((string)$request->get('crop', ''));
            $bedCrops      = array_values(array_filter(array_map('trim', explode(',', (string)$request->get('bed_crops', '')))));
            $currentSeedId = (int)$request->get('seed_id', 0);

            if ($crop === '') {
                Response::json(['success' => false, 'error' => 'No crop specified']);
                return;
            }

            $db    = DB::getInstance();
            $seeds = $db->fetchAll(
                "SELECT id, name, variety, companions, antagonists FROM seeds ORDER BY name ASC"
            ) ?: [];

            $data = self::rankCompanions($crop, $bedCrops, $seeds);

            if (empty($data['companions']) && empty($data['antagonists'])) {
                Response::json(['success' => false, 'error' => 'No companion data found — add companions & avoid lists to your seeds to use this feature']);
                return;
            }

            // Build variantsByName map: lowercase name -> [{id,name,variety}]
            $variantsByName = [];
            foreach ($seeds as $s) {
                $key = strtolower($s['name']);
                $variantsByName[$key][] = [
                    'id'      => (int)$s['id'],
                    'name'    => $s['name'],
                    'variety' => (string)($s['variety'] ?? ''),
                ];
            }

            // Attach variants to each companion result
            foreach ($data['companions'] as &$c) {
                $cKey = strtolower($c['name']);
                $c['variants'] = $variantsByName[$cKey] ?? [['id' => $c['id'], 'name' => $c['name'], 'variety' => $c['variety']]];
            }
            unset($c);

            // Build antagonist names list (lowercase)
            $decode = fn($json) => array_map('strtolower', json_decode($json ?? '[]', true) ?: []);
            $cropLc = strtolower($crop);
            $byName = [];
            foreach ($seeds as $s) { $byName[strtolower($s['name'])] = $s; }
            $target = $byName[$cropLc] ?? null;
            $tAntagonists = $target ? $decode($target['antagonists']) : [];
            $antagonistNames = $tAntagonists;
            // Also add antagonists from each seed's list for the target crop
            foreach ($seeds as $s) {
                $sAnts = $decode($s['antagonists']);
                if (in_array($cropLc, $sAnts, true)) {
                    $antagonistNames[] = strtolower($s['name']);
                }
            }
            $antagonistNames = array_values(array_unique($antagonistNames));
            $data['antagonist_names'] = $antagonistNames;

            // Similar seeds: same name as crop but different seed_id
            $similar = [];
            if (isset($variantsByName[$cropLc])) {
                foreach ($variantsByName[$cropLc] as $v) {
                    if ($currentSeedId > 0 && $v['id'] === $currentSeedId) continue;
                    $similar[] = $v;
                }
            }
            $data['similar'] = $similar;

            // All seeds as compact list for dropdown
            $allSeedsCompact = [];
            foreach ($seeds as $s) {
                $allSeedsCompact[] = [
                    'id'      => (int)$s['id'],
                    'name'    => $s['name'],
                    'variety' => (string)($s['variety'] ?? ''),
                ];
            }
            $data['all_seeds'] = $allSeedsCompact;

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
        // Keyed by lowercase name to deduplicate varieties — keep highest score per plant
        $seen = [];
        foreach ($seeds as $s) {
            $sLc = strtolower($s['name']);
            if ($sLc === $cropLc) continue;

            $sCompanions  = $decode($s['companions']);
            $sAntagonists = $decode($s['antagonists']);

            // Disqualify if mutual antagonist
            if (in_array($sLc, $tAntagonists, true))   continue;
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

            // Deduplicate: one entry per plant name, keep highest score
            if (!isset($seen[$sLc]) || $score > $seen[$sLc]['score']) {
                $seen[$sLc] = ['id' => (int)($s['id'] ?? 0), 'name' => $s['name'], 'variety' => (string)($s['variety'] ?? ''), 'score' => $score, 'reason' => $reason];
            }
        }

        $candidates = array_values($seen);
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
