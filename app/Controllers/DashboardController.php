<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;

class DashboardController
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

        $db = DB::getInstance();

        $itemCounts = $db->fetchAll(
            'SELECT type, COUNT(*) AS cnt FROM items WHERE status = ? AND deleted_at IS NULL GROUP BY type',
            ['active']
        );

        $recentActivity = $db->fetchAll(
            'SELECT * FROM activity_log ORDER BY performed_at DESC LIMIT 10'
        );

        $upcomingReminders = $db->fetchAll(
            "SELECT * FROM reminders WHERE status = 'pending' AND due_at >= NOW() ORDER BY due_at ASC LIMIT 5"
        );

        $overdueReminders = $db->fetchAll(
            "SELECT * FROM reminders WHERE status = 'pending' AND due_at < NOW() ORDER BY due_at ASC LIMIT 5"
        );

        // Harvest totals grouped by item type (current year)
        $harvestByType = $db->fetchAll(
            "SELECT i.type, h.unit, SUM(h.quantity) AS total
             FROM harvest_entries h
             JOIN items i ON i.id = h.item_id
             WHERE YEAR(h.recorded_at) = YEAR(NOW())
             GROUP BY i.type, h.unit
             ORDER BY i.type, h.unit"
        );
        // Reindex: ['olive_tree' => ['kg' => 120.5, ...], ...]
        $harvestByTypeMap = [];
        foreach ($harvestByType as $row) {
            $harvestByTypeMap[$row['type']][$row['unit']] = (float)$row['total'];
        }

        $monthlyHarvest = $db->fetchAll(
            "SELECT MONTH(h.recorded_at) AS mo, SUM(h.quantity) AS total, h.unit
             FROM harvest_entries h
             WHERE YEAR(h.recorded_at) = YEAR(NOW())
             GROUP BY MONTH(h.recorded_at), h.unit
             ORDER BY mo",
            []
        );

        $gpsItems = $db->fetchAll(
            "SELECT i.id, i.name, i.type, i.gps_lat, i.gps_lng,
                    (SELECT a.id FROM attachments a
                     WHERE a.item_id = i.id AND a.category = 'identification_photo'
                     AND a.status = 'active' AND a.mime_type LIKE 'image/%'
                     ORDER BY a.id DESC LIMIT 1) AS photo_id
             FROM items i
             WHERE i.gps_lat IS NOT NULL AND i.gps_lng IS NOT NULL
             AND i.status = 'active' AND i.deleted_at IS NULL
             ORDER BY i.name"
        );

        Response::render('dashboard/index', [
            'title'             => 'Dashboard',
            'itemCounts'        => $itemCounts,
            'recentActivity'    => $recentActivity,
            'upcomingReminders' => $upcomingReminders,
            'overdueReminders'  => $overdueReminders,
            'harvestByTypeMap'  => $harvestByTypeMap,
            'monthlyHarvest'    => $monthlyHarvest,
            'gpsItems'          => $gpsItems,
        ]);
    }

    public function overview(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $itemCounts = $db->fetchAll(
            'SELECT type, COUNT(*) AS cnt FROM items WHERE status = ? AND deleted_at IS NULL GROUP BY type',
            ['active']
        );
        Response::render('dashboard/overview', ['title' => 'Overview', 'itemCounts' => $itemCounts]);
    }

    public function map(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $db = DB::getInstance();

        $gpsCount = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM items WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL
             AND status != 'trashed' AND deleted_at IS NULL"
        );

        $center = $db->fetchOne(
            "SELECT AVG(gps_lat) AS lat, AVG(gps_lng) AS lng FROM items
             WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL
             AND status != 'trashed' AND deleted_at IS NULL"
        );

        // Load land boundary and name from settings
        $landBoundarySetting = $db->fetchOne(
            "SELECT setting_value_text FROM settings WHERE setting_key = 'land.boundary_geojson'"
        );
        $landNameSetting = $db->fetchOne(
            "SELECT setting_value_text FROM settings WHERE setting_key = 'app.name'"
        );

        $landBoundaryJson = $landBoundarySetting['setting_value_text'] ?? null;
        $hasLandBoundary  = !empty($landBoundaryJson);

        // If we have a boundary, derive the map center from it instead of item average
        $defaultLat = (float)($center['lat'] ?? 41.9);
        $defaultLng = (float)($center['lng'] ?? 12.5);
        if ($hasLandBoundary) {
            $decoded = json_decode($landBoundaryJson, true);
            $coords  = $decoded['coordinates'][0] ?? [];
            if ($coords) {
                $lats = array_column($coords, 1);
                $lngs = array_column($coords, 0);
                $defaultLat = (array_sum($lats) / count($lats));
                $defaultLng = (array_sum($lngs) / count($lngs));
            }
        }

        $itemTypes = require BASE_PATH . '/config/item_types.php';

        Response::render('dashboard/map', [
            'title'            => 'Land Map',
            'mapEnabled'       => true,
            'gpsCount'         => (int)($gpsCount['cnt'] ?? 0),
            'defaultLat'       => $defaultLat,
            'defaultLng'       => $defaultLng,
            'hasLandBoundary'  => $hasLandBoundary,
            'landBoundaryJson' => $hasLandBoundary ? $landBoundaryJson : 'null',
            'landName'         => $landNameSetting['setting_value_text'] ?? 'My Land',
            'itemTypes'        => $itemTypes,
        ]);
    }

    public function nearby(Request $request, array $params = []): void
    {
        $this->requireAuth();
        Response::render('dashboard/nearby', ['title' => 'Nearby Items']);
    }

    public function reports(Request $request, array $params = []): void
    {
        $this->requireAuth();
        Response::render('dashboard/reports', ['title' => 'Reports — ' . date('Y')]);
    }

    public function apiSummary(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $db = DB::getInstance();

        $totalItems = $db->fetchOne('SELECT COUNT(*) AS cnt FROM items WHERE deleted_at IS NULL AND status != ?', ['trashed']);
        $overdueCount = $db->fetchOne("SELECT COUNT(*) AS cnt FROM reminders WHERE status = 'pending' AND due_at < NOW()");

        Response::json([
            'success' => true,
            'data' => [
                'total_items'    => (int) ($totalItems['cnt'] ?? 0),
                'overdue_reminders' => (int) ($overdueCount['cnt'] ?? 0),
            ],
        ]);
    }
}
