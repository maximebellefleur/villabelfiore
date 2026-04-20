<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;

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

        Response::render('garden/index', [
            'title'           => 'Garden',
            'totalSeeds'      => $totalSeeds,
            'plantNow'        => $plantNow,
            'harvestSoon'     => $harvestSoon,
            'harvestMonths'   => $harvestMonths,
            'lowStock'        => $lowStock,
            'familyNeeds'     => $familyNeeds,
            'activeBedRows'   => $activeBedRows,
            'recentActivity'  => $recentActivity,
            'harvestReminders'=> $harvestReminders,
            'currentMonth'    => $currentMonth,
            'currentYear'     => $currentYear,
        ]);
    }
}
