<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\BiodynamicCalendar;
use App\Support\GardenSchema;
use App\Support\GardenHelpers;

class GardenController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();

        // Ensure seed tables exist
        $db->execute("CREATE TABLE IF NOT EXISTS seeds (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            variety VARCHAR(120) DEFAULT '',
            botanical_family VARCHAR(120) DEFAULT '',
            type ENUM('vegetable','herb','fruit','flower','other') NOT NULL DEFAULT 'vegetable',
            sowing_type ENUM('direct','nursery','both') NOT NULL DEFAULT 'direct',
            days_to_germinate SMALLINT UNSIGNED DEFAULT NULL,
            days_to_maturity SMALLINT UNSIGNED DEFAULT NULL,
            spacing_cm SMALLINT UNSIGNED DEFAULT NULL,
            row_spacing_cm SMALLINT UNSIGNED DEFAULT NULL,
            sowing_depth_mm SMALLINT UNSIGNED DEFAULT NULL,
            sun_exposure VARCHAR(60) DEFAULT '',
            soil_notes TEXT DEFAULT NULL,
            planting_months JSON DEFAULT NULL,
            harvest_months JSON DEFAULT NULL,
            frost_hardy TINYINT(1) NOT NULL DEFAULT 0,
            companions JSON DEFAULT NULL,
            antagonists JSON DEFAULT NULL,
            yield_per_plant_kg DECIMAL(8,3) DEFAULT NULL,
            stock_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
            stock_unit ENUM('seeds','grams','packets') NOT NULL DEFAULT 'seeds',
            stock_low_threshold DECIMAL(12,3) DEFAULT NULL,
            stock_enabled TINYINT(1) NOT NULL DEFAULT 1,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS family_needs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            vegetable_name VARCHAR(120) NOT NULL,
            seed_id INT UNSIGNED DEFAULT NULL,
            yearly_qty DECIMAL(10,3) DEFAULT NULL,
            yearly_unit VARCHAR(30) NOT NULL DEFAULT 'kg',
            priority TINYINT UNSIGNED NOT NULL DEFAULT 5,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS bed_rows (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id INT UNSIGNED NOT NULL,
            season_year SMALLINT UNSIGNED NOT NULL,
            row_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            seed_id INT UNSIGNED DEFAULT NULL,
            plant_count SMALLINT UNSIGNED DEFAULT NULL,
            spacing_used_cm SMALLINT UNSIGNED DEFAULT NULL,
            sowing_date DATE DEFAULT NULL,
            transplant_date DATE DEFAULT NULL,
            sowing_type ENUM('direct','nursery','both') DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            status ENUM('planned','sown','growing','harvested') NOT NULL DEFAULT 'planned',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $currentMonth = (int) date('n');
        $currentYear  = (int) date('Y');

        // All seeds
        $allSeeds = $db->fetchAll('SELECT * FROM seeds ORDER BY name ASC');
        $totalSeeds = count($allSeeds);

        // Seeds to plant this month
        $plantNow = array_values(array_filter($allSeeds, function($s) use ($currentMonth) {
            if (empty($s['planting_months'])) return false;
            $months = json_decode($s['planting_months'], true);
            return is_array($months) && in_array($currentMonth, $months);
        }));

        // Seeds to harvest this month or next 2 months
        $harvestMonths = [];
        for ($i = 0; $i < 3; $i++) {
            $mo = (($currentMonth - 1 + $i) % 12) + 1;
            $harvestMonths[] = $mo;
        }
        $harvestSoon = array_values(array_filter($allSeeds, function($s) use ($harvestMonths) {
            if (empty($s['harvest_months'])) return false;
            $months = json_decode($s['harvest_months'], true);
            return is_array($months) && !empty(array_intersect($months, $harvestMonths));
        }));

        // Low stock seeds
        $lowStock = array_values(array_filter($allSeeds, function($s) {
            return $s['stock_enabled'] && $s['stock_low_threshold'] !== null
                && (float)$s['stock_qty'] <= (float)$s['stock_low_threshold'];
        }));

        // Family needs with seed info
        $familyNeeds = $db->fetchAll(
            'SELECT fn.*, s.name AS seed_name, s.stock_qty, s.stock_unit
             FROM family_needs fn
             LEFT JOIN seeds s ON s.id = fn.seed_id
             ORDER BY fn.priority ASC, fn.vegetable_name ASC'
        );

        // Active bed rows this season (not harvested)
        $activeBedRows = $db->fetchAll(
            "SELECT br.*, s.name AS seed_name, i.name AS bed_name
             FROM bed_rows br
             LEFT JOIN seeds s ON s.id = br.seed_id
             LEFT JOIN items i ON i.id = br.item_id
             WHERE br.season_year = ? AND br.status IN ('sown','growing','planned')
             ORDER BY br.status DESC, br.sowing_date ASC
             LIMIT 20",
            [$currentYear]
        );

        // Recent garden/bed activity
        $recentActivity = $db->fetchAll(
            "SELECT al.*, i.name AS item_name, i.type AS item_type
             FROM activity_log al
             JOIN items i ON i.id = al.item_id
             WHERE i.type IN ('bed','garden','zone')
             ORDER BY al.performed_at DESC
             LIMIT 8"
        );

        // Upcoming harvest reminders
        $harvestReminders = $db->fetchAll(
            "SELECT * FROM reminders
             WHERE status = 'pending' AND LOWER(title) LIKE '%harvest%'
             ORDER BY due_at ASC LIMIT 5"
        );

        // Climate-based planting suggestions
        $climateRow  = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key = 'garden.climate_zone'");
        $climateZone = $climateRow['setting_value_text'] ?? 'mediterranean_sicily';
        $climateSuggestions = $this->getClimateSuggestions($climateZone, $currentMonth);

        // Garden bed schematic — beds with dimensions, grouped by parent garden
        $schematicBeds = [];
        $schematicGardens = [];
        try {
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

            $rawBeds = $db->fetchAll(
                "SELECT i.id, i.name, i.parent_id, i.gps_lat, i.gps_lng,
                        MAX(CASE WHEN m.meta_key='bed_length_m'    THEN m.meta_value_text END) AS length_m,
                        MAX(CASE WHEN m.meta_key='bed_width_m'     THEN m.meta_value_text END) AS width_m,
                        MAX(CASE WHEN m.meta_key='bed_rows'        THEN m.meta_value_text END) AS bed_rows,
                        MAX(CASE WHEN m.meta_key='line_direction'  THEN m.meta_value_text END) AS line_dir
                 FROM items i
                 LEFT JOIN item_meta m ON m.item_id = i.id
                   AND m.meta_key IN ('bed_length_m','bed_width_m','bed_rows','line_direction')
                 WHERE i.type = 'bed' AND i.deleted_at IS NULL AND i.status = 'active'
                 GROUP BY i.id ORDER BY i.parent_id, i.name"
            );

            $bedIds = array_column($rawBeds, 'id');
            $plantingsByBed = [];
            if ($bedIds) {
                $ph = implode(',', array_fill(0, count($bedIds), '?'));
                $prows = $db->fetchAll(
                    "SELECT item_id, line_number, crop_name, status FROM garden_plantings WHERE item_id IN ($ph) ORDER BY line_number",
                    $bedIds
                );
                foreach ($prows as $pr) {
                    $plantingsByBed[$pr['item_id']][$pr['line_number']] = $pr;
                }
            }

            foreach ($rawBeds as $bed) {
                $schematicBeds[$bed['id']] = array_merge($bed, [
                    'plantings' => $plantingsByBed[$bed['id']] ?? [],
                ]);
            }

            // Gardens for grouping headers
            $gardenRows = $db->fetchAll(
                "SELECT id, name FROM items WHERE type='garden' AND deleted_at IS NULL AND status='active' ORDER BY name"
            );
            foreach ($gardenRows as $g) $schematicGardens[$g['id']] = $g['name'];
        } catch (\Throwable $e) { /* non-fatal */ }

        // Biodynamic overview for garden page (7-day snapshot)
        $tzStr   = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key='app.timezone'")['setting_value_text'] ?? 'Europe/Rome';
        $tz      = new \DateTimeZone($tzStr ?: 'Europe/Rome');
        $bioNow  = BiodynamicCalendar::computePoint(new \DateTime('now', $tz));
        $bioWeek = [];
        for ($i = 0; $i < 7; $i++) {
            $dt = new \DateTime('now', $tz);
            $dt->modify("+{$i} days")->setTime(12, 0);
            $bioWeek[$i] = BiodynamicCalendar::computePoint($dt);
        }

        // ---- Action-first hub data (Garden Redesign v3) -----------------
        $hub = $this->buildHub($db);

        Response::render('garden/index', [
            'title'              => 'Garden',
            'hub'                => $hub,
            'totalSeeds'         => $totalSeeds,
            'plantNow'           => $plantNow,
            'harvestSoon'        => $harvestSoon,
            'harvestMonths'      => $harvestMonths,
            'lowStock'           => $lowStock,
            'familyNeeds'        => $familyNeeds,
            'activeBedRows'      => $activeBedRows,
            'recentActivity'     => $recentActivity,
            'harvestReminders'   => $harvestReminders,
            'currentMonth'       => $currentMonth,
            'currentYear'        => $currentYear,
            'climateSuggestions' => $climateSuggestions,
            'climateZone'        => $climateZone,
            'bioNow'             => $bioNow,
            'bioWeek'            => $bioWeek,
            'schematicBeds'      => $schematicBeds,
            'schematicGardens'   => $schematicGardens,
        ]);
    }

    /**
     * Build the property-level "action-first" hub.
     * Returns ['gardens'=>[...], 'beds_by_garden'=>[gid=>[...]], 'summary'=>[...], 'today'=>str].
     */
    private function buildHub(DB $db): array
    {
        GardenSchema::ensure($db);
        $today = GardenHelpers::todayIso();

        // Catalog with redesign fields
        try {
            $rows = $db->fetchAll(
                "SELECT id, name, days_to_maturity, spacing_cm, family, season, emoji, color
                 FROM seeds ORDER BY name ASC"
            ) ?: [];
        } catch (\Throwable $e) {
            $rows = $db->fetchAll("SELECT id, name, days_to_maturity, spacing_cm FROM seeds ORDER BY name ASC") ?: [];
        }
        $cropsById = [];
        foreach ($rows as $r) {
            $r['id']                = (int)$r['id'];
            $r['days_to_maturity']  = (int)($r['days_to_maturity'] ?? 60) ?: 60;
            $r['spacing_cm']        = max(1, (int)($r['spacing_cm'] ?? 5));
            $r['family']            = $r['family'] ?? 'other';
            $r['season']            = $r['season'] ?? 'any';
            $r['emoji']             = $r['emoji'] ?: GardenHelpers::cropEmoji($r);
            $r['color']             = GardenHelpers::cropColor($r);
            $cropsById[$r['id']]    = $r;
        }

        // Gardens (parents)
        $gardens = $db->fetchAll(
            "SELECT id, name, gps_lat, gps_lng FROM items
             WHERE type='garden' AND deleted_at IS NULL AND status='active'
             ORDER BY name ASC"
        ) ?: [];

        // Beds with their meta (length/width/rows)
        $beds = $db->fetchAll(
            "SELECT i.id, i.name, i.parent_id, i.gps_lat, i.gps_lng,
                    MAX(CASE WHEN m.meta_key='bed_length_m' THEN m.meta_value_text END) AS length_m,
                    MAX(CASE WHEN m.meta_key='bed_width_m'  THEN m.meta_value_text END) AS width_m,
                    MAX(CASE WHEN m.meta_key='bed_rows'     THEN m.meta_value_text END) AS bed_rows
             FROM items i
             LEFT JOIN item_meta m ON m.item_id = i.id
                AND m.meta_key IN ('bed_length_m','bed_width_m','bed_rows')
             WHERE i.type='bed' AND i.deleted_at IS NULL AND i.status='active'
             GROUP BY i.id ORDER BY i.parent_id, i.name"
        ) ?: [];

        // Plantings & line state — load all in one go, group by bed
        $bedIds = array_column($beds, 'id');
        $plantingsByBed = [];
        $linesByBed = [];
        if (!empty($bedIds)) {
            $ph = implode(',', array_fill(0, count($bedIds), '?'));
            try {
                $prows = $db->fetchAll(
                    "SELECT * FROM garden_plantings WHERE item_id IN ($ph)",
                    $bedIds
                );
                foreach ($prows as $pr) {
                    $plantingsByBed[(int)$pr['item_id']][(int)$pr['line_number']][] = $pr;
                }
            } catch (\Throwable $e) {}
            try {
                $lrows = $db->fetchAll(
                    "SELECT * FROM garden_bed_lines WHERE item_id IN ($ph)",
                    $bedIds
                );
                foreach ($lrows as $lr) {
                    $linesByBed[(int)$lr['item_id']][(int)$lr['line_number']] = $lr;
                }
            } catch (\Throwable $e) {}
        }

        // Build rich bed objects with computed lines + actions
        $bedsByGarden = [];
        foreach ($beds as $bed) {
            $bedId = (int)$bed['id'];
            $gid   = (int)($bed['parent_id'] ?? 0);
            $bedRows = max(1, (int)($bed['bed_rows'] ?? 1));
            $lengthM = (float)($bed['length_m'] ?? 0);
            $widthM  = (float)($bed['width_m'] ?? 0);
            $lengthCm = (int)round($lengthM * 100); if ($lengthCm <= 0) $lengthCm = 400;

            // Build line array
            $lines = [];
            $cropsSeen = [];
            for ($n = 1; $n <= $bedRows; $n++) {
                $rawPlantings = $plantingsByBed[$bedId][$n] ?? [];
                $linePlantings = [];
                foreach ($rawPlantings as $rp) {
                    $cid = (int)($rp['seed_id'] ?? 0);
                    $cnt = max(0, (int)($rp['plant_count'] ?? 1));
                    if ($cid <= 0 || $cnt <= 0) continue;
                    $linePlantings[] = [
                        'cropId'  => $cid,
                        'plants'  => $cnt,
                        'sown_at' => $rp['sown_at'] ?? $rp['planted_at'] ?? null,
                        'status'  => $rp['status'] ?? 'growing',
                    ];
                    $cropsSeen[$cid] = true;
                }
                $lstate = $linesByBed[$bedId][$n] ?? null;
                $hist = [];
                if ($lstate && !empty($lstate['rotation_history'])) {
                    $hist = json_decode($lstate['rotation_history'], true) ?: [];
                }
                $status = 'empty';
                if (!empty($linePlantings)) $status = 'growing';
                elseif ($lstate && !empty($lstate['succession_crop_id'])) $status = 'planned';
                elseif ($lstate && !empty($lstate['empty_since'])) $status = 'harvested';

                $lines[] = [
                    'id' => $n, 'lineNumber' => $n,
                    'lengthCm' => (int)($lstate['length_cm'] ?? $lengthCm),
                    'status' => $status,
                    'sown_at' => $lstate['sown_at'] ?? null,
                    'empty_since' => $lstate['empty_since'] ?? null,
                    'last_watered_at' => $lstate['last_watered_at'] ?? null,
                    'succession_crop_id' => $lstate['succession_crop_id'] ?? null,
                    'succession_starts_on' => $lstate['succession_starts_on'] ?? null,
                    'rotation_history' => $hist,
                    'plantings' => $linePlantings,
                ];
            }

            // Bed-level status: growing if any line growing, etc.
            $bedStatus = 'empty';
            $any = function($lines, $st) { foreach ($lines as $l) { if ($l['status'] === $st) return true; } return false; };
            if ($any($lines, 'growing')) $bedStatus = 'growing';
            elseif ($any($lines, 'planned')) $bedStatus = 'planned';
            elseif ($any($lines, 'harvested')) $bedStatus = 'harvested';

            $bedObj = [
                'id'        => $bedId,
                'name'      => $bed['name'],
                'gardenId'  => $gid,
                'gps_lat'   => $bed['gps_lat'],
                'gps_lng'   => $bed['gps_lng'],
                'lengthM'   => $lengthM,
                'widthM'    => $widthM,
                'numLines'  => $bedRows,
                'status'    => $bedStatus,
                'lines'     => $lines,
                'cropChips' => array_values(array_map(fn($cid) => $cropsById[$cid] ?? null, array_keys($cropsSeen))),
            ];
            $bedObj['actions'] = GardenHelpers::bedActions($bedObj, $cropsById, $today);
            $bedsByGarden[$gid][] = $bedObj;
        }

        // Property-level summary
        $allBeds = [];
        foreach ($bedsByGarden as $arr) { foreach ($arr as $b) $allBeds[] = $b; }
        $summary = GardenHelpers::propertySummary($allBeds, $cropsById, $today);

        return [
            'today'         => $today,
            'gardens'       => $gardens,
            'beds_by_garden'=> $bedsByGarden,
            'summary'       => $summary,
            'cropsById'     => $cropsById,
        ];
    }

    /**
     * Built-in seasonal planting calendar keyed by [climate_zone][month].
     * Returns array of ['name'=>string, 'type'=>string, 'tip'=>string]
     */
    private function getClimateSuggestions(string $zone, int $month): array
    {
        // Base Mediterranean Sicily calendar (hot dry summer, mild rainy winter)
        // Other zones override specific months
        $med_sicily = [
            1  => [['name'=>'Broad Beans','type'=>'vegetable','tip'=>'Sow direct, frost-hardy'],['name'=>'Peas','type'=>'vegetable','tip'=>'Best month to sow'],['name'=>'Lettuce','type'=>'vegetable','tip'=>'Transplant under cover'],['name'=>'Spinach','type'=>'vegetable','tip'=>'Cold-season leafy green'],['name'=>'Onion sets','type'=>'vegetable','tip'=>'Plant bulbs now']],
            2  => [['name'=>'Tomatoes','type'=>'vegetable','tip'=>'Start indoors for April transplant'],['name'=>'Peppers','type'=>'vegetable','tip'=>'Sow in heated propagator'],['name'=>'Aubergine','type'=>'vegetable','tip'=>'Start indoors'],['name'=>'Early Potatoes','type'=>'vegetable','tip'=>'Chit now for March planting'],['name'=>'Chard','type'=>'vegetable','tip'=>'Sow direct']],
            3  => [['name'=>'Tomatoes','type'=>'vegetable','tip'=>'Transplant seedlings if frosts done'],['name'=>'Zucchini','type'=>'vegetable','tip'=>'Start indoors'],['name'=>'Basil','type'=>'herb','tip'=>'Sow indoors — loves warmth'],['name'=>'Carrots','type'=>'vegetable','tip'=>'Direct sow in prepared bed'],['name'=>'Beets','type'=>'vegetable','tip'=>'Direct sow']],
            4  => [['name'=>'Tomatoes','type'=>'vegetable','tip'=>'Transplant outdoors after last frost'],['name'=>'Peppers','type'=>'vegetable','tip'=>'Transplant with protection'],['name'=>'Cucumber','type'=>'vegetable','tip'=>'Sow indoors or transplant'],['name'=>'Green Beans','type'=>'vegetable','tip'=>'Direct sow warm soil'],['name'=>'Basil','type'=>'herb','tip'=>'Transplant in warm spot']],
            5  => [['name'=>'Zucchini','type'=>'vegetable','tip'=>'Direct sow — fastest growing'],['name'=>'Pumpkin','type'=>'vegetable','tip'=>'Direct sow with space'],['name'=>'Sweet Corn','type'=>'vegetable','tip'=>'Direct sow in blocks'],['name'=>'Watermelon','type'=>'fruit','tip'=>'Transplant seedlings'],['name'=>'Okra','type'=>'vegetable','tip'=>'Loves heat']],
            6  => [['name'=>'Autumn Tomatoes','type'=>'vegetable','tip'=>'Second sowing for autumn harvest'],['name'=>'Basil','type'=>'herb','tip'=>'Last chance to sow'],['name'=>'Fennel','type'=>'vegetable','tip'=>'Sow for autumn'],['name'=>'Melon','type'=>'fruit','tip'=>'Best month — peak heat']],
            7  => [['name'=>'Autumn Carrots','type'=>'vegetable','tip'=>'Sow for autumn harvest'],['name'=>'Lettuce','type'=>'vegetable','tip'=>'Heat-tolerant varieties only'],['name'=>'Beans','type'=>'vegetable','tip'=>'Last summer sowing']],
            8  => [['name'=>'Autumn Brassicas','type'=>'vegetable','tip'=>'Sow broccoli/cauliflower'],['name'=>'Autumn Lettuce','type'=>'vegetable','tip'=>'Transplant now for cool season'],['name'=>'Radish','type'=>'vegetable','tip'=>'Quick crop before summer ends'],['name'=>'Turnips','type'=>'vegetable','tip'=>'Direct sow for autumn']],
            9  => [['name'=>'Garlic','type'=>'vegetable','tip'=>'Plant cloves for summer harvest'],['name'=>'Onions','type'=>'vegetable','tip'=>'Sets or seed sowing'],['name'=>'Spinach','type'=>'vegetable','tip'=>'Perfect cool season timing'],['name'=>'Peas','type'=>'vegetable','tip'=>'Autumn/winter crop'],['name'=>'Broad Beans','type'=>'vegetable','tip'=>'Sow for spring harvest']],
            10 => [['name'=>'Garlic','type'=>'vegetable','tip'=>'Main garlic planting month'],['name'=>'Fava Beans','type'=>'vegetable','tip'=>'Direct sow — overwinters well'],['name'=>'Winter Lettuce','type'=>'vegetable','tip'=>'Under cover or sheltered'],['name'=>'Chicory','type'=>'vegetable','tip'=>'Direct sow'],['name'=>'Parsley','type'=>'herb','tip'=>'Sow for winter use']],
            11 => [['name'=>'Garlic','type'=>'vegetable','tip'=>'Last chance to plant'],['name'=>'Onion seeds','type'=>'vegetable','tip'=>'Overwinter for early harvest'],['name'=>'Broad Beans','type'=>'vegetable','tip'=>'Sow early varieties'],['name'=>'Cover crops','type'=>'other','tip'=>'Green manure for bed improvement']],
            12 => [['name'=>'Cover crops','type'=>'other','tip'=>'Legume mix for nitrogen fixing'],['name'=>'Planning','type'=>'other','tip'=>'Order seeds, plan rotations'],['name'=>'Fruit trees','type'=>'fruit','tip'=>'Dormant pruning time'],['name'=>'Garlic maintenance','type'=>'vegetable','tip'=>'Weed around established beds']],
        ];

        // Temperate oceanic adjustments (UK / N France)
        $temperate_oceanic = $med_sicily;
        $temperate_oceanic[3]  = [['name'=>'Onions','type'=>'vegetable','tip'=>'Sow indoors'],['name'=>'Brassicas','type'=>'vegetable','tip'=>'Cabbage/broccoli indoors'],['name'=>'Lettuce','type'=>'vegetable','tip'=>'Under glass only'],['name'=>'Peas','type'=>'vegetable','tip'=>'Early varieties direct']];
        $temperate_oceanic[4]  = [['name'=>'Tomatoes','type'=>'vegetable','tip'=>'Sow indoors heated'],['name'=>'Courgette','type'=>'vegetable','tip'=>'Sow indoors'],['name'=>'Leeks','type'=>'vegetable','tip'=>'Sow indoors'],['name'=>'Carrots','type'=>'vegetable','tip'=>'Direct sow outside']];
        $temperate_oceanic[5]  = [['name'=>'Runner Beans','type'=>'vegetable','tip'=>'Sow indoors'],['name'=>'French Beans','type'=>'vegetable','tip'=>'Sow indoors'],['name'=>'Sweetcorn','type'=>'vegetable','tip'=>'Sow indoors in modules'],['name'=>'Cucumber','type'=>'vegetable','tip'=>'Sow indoors']];
        $temperate_oceanic[6]  = [['name'=>'Runner Beans','type'=>'vegetable','tip'=>'Transplant outside'],['name'=>'Tomatoes','type'=>'vegetable','tip'=>'Plant out after last frost'],['name'=>'Courgette','type'=>'vegetable','tip'=>'Plant out — risk of frost done'],['name'=>'Basil','type'=>'herb','tip'=>'Plant in greenhouse']];

        $zones = [
            'mediterranean_sicily'    => $med_sicily,
            'mediterranean_general'   => $med_sicily,
            'continental_north_italy' => array_merge($med_sicily, [
                3 => [['name'=>'Tomatoes','type'=>'vegetable','tip'=>'Start indoors only — late frosts'],['name'=>'Leeks','type'=>'vegetable','tip'=>'Sow indoors'],['name'=>'Onions','type'=>'vegetable','tip'=>'Sow indoors']],
                4 => [['name'=>'Potatoes','type'=>'vegetable','tip'=>'Plant after Easter frost risk'],['name'=>'Lettuce','type'=>'vegetable','tip'=>'Transplant outside now'],['name'=>'Carrots','type'=>'vegetable','tip'=>'Direct sow — soil warming']],
                5 => [['name'=>'Tomatoes','type'=>'vegetable','tip'=>'Transplant post–last frost (mid May)'],['name'=>'Zucchini','type'=>'vegetable','tip'=>'Direct sow or transplant'],['name'=>'Beans','type'=>'vegetable','tip'=>'Direct sow warm soil']],
            ]),
            'temperate_oceanic'       => $temperate_oceanic,
            'continental_central_eu'  => $temperate_oceanic,
            'subtropical_humid'       => $med_sicily,
            'tropical'                => array_fill(1, 12, [['name'=>'Year-round planting','type'=>'other','tip'=>'Manage rainfall and heat — see local calendar']]),
            'arid_desert'             => $med_sicily,
            'semi_arid'               => $med_sicily,
            'alpine'                  => $temperate_oceanic,
        ];

        $calendar = $zones[$zone] ?? $med_sicily;
        return $calendar[$month] ?? [];
    }
}
