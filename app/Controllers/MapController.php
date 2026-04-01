<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class MapController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
    }

    /**
     * GET /dashboard/map — full-page interactive map
     */
    public function index(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::redirect('/login');
        }

        $db = DB::getInstance();

        // Count items that have GPS coordinates
        $gpsCount = $db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM items WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL
             AND status != ? AND deleted_at IS NULL',
            ['trashed']
        );

        // Default map center: average of all GPS points, or a default lat/lng
        $center = $db->fetchOne(
            'SELECT AVG(gps_lat) AS lat, AVG(gps_lng) AS lng FROM items
             WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL
             AND status != ? AND deleted_at IS NULL',
            ['trashed']
        );

        $defaultLat = $center['lat'] ?? 41.9;
        $defaultLng = $center['lng'] ?? 12.5;

        Response::render('dashboard/map', [
            'title'      => 'Land Map',
            'gpsCount'   => (int)($gpsCount['cnt'] ?? 0),
            'defaultLat' => (float)$defaultLat,
            'defaultLng' => (float)$defaultLng,
        ]);
    }

    /**
     * GET /api/map/items — returns all items with GPS + boundaries for Leaflet
     */
    public function apiItems(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $db = DB::getInstance();

        // All items with GPS
        $items = $db->fetchAll(
            'SELECT id, name, type, status, gps_lat, gps_lng, gps_accuracy, gps_source, parent_id
             FROM items
             WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL
               AND status != ? AND deleted_at IS NULL
             ORDER BY type, name',
            ['trashed']
        );

        // Load boundaries from item_meta for all relevant items
        $ids = array_column($items, 'id');
        $boundaries = [];
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rows = $db->fetchAll(
                "SELECT item_id, meta_value FROM item_meta
                 WHERE meta_key = 'boundary_geojson' AND item_id IN ($placeholders)",
                $ids
            );
            foreach ($rows as $row) {
                $boundaries[$row['item_id']] = $row['meta_value'];
            }
        }

        // Also load items that have a boundary but no GPS (zones drawn on map directly)
        $zonesWithBoundary = $db->fetchAll(
            "SELECT i.id, i.name, i.type, i.status, i.gps_lat, i.gps_lng, m.meta_value AS boundary_geojson
             FROM items i
             JOIN item_meta m ON m.item_id = i.id AND m.meta_key = 'boundary_geojson'
             WHERE (i.gps_lat IS NULL OR i.gps_lng IS NULL)
               AND i.status != ? AND i.deleted_at IS NULL",
            ['trashed']
        );

        $features = [];

        foreach ($items as $item) {
            $feature = [
                'id'           => (int)$item['id'],
                'name'         => $item['name'],
                'type'         => $item['type'],
                'status'       => $item['status'],
                'lat'          => (float)$item['gps_lat'],
                'lng'          => (float)$item['gps_lng'],
                'gps_accuracy' => $item['gps_accuracy'] ? (float)$item['gps_accuracy'] : null,
                'gps_source'   => $item['gps_source'],
                'parent_id'    => $item['parent_id'] ? (int)$item['parent_id'] : null,
                'boundary'     => isset($boundaries[$item['id']])
                                  ? json_decode($boundaries[$item['id']], true)
                                  : null,
            ];
            $features[] = $feature;
        }

        foreach ($zonesWithBoundary as $zone) {
            $features[] = [
                'id'           => (int)$zone['id'],
                'name'         => $zone['name'],
                'type'         => $zone['type'],
                'status'       => $zone['status'],
                'lat'          => null,
                'lng'          => null,
                'gps_accuracy' => null,
                'gps_source'   => null,
                'parent_id'    => null,
                'boundary'     => json_decode($zone['boundary_geojson'], true),
            ];
        }

        Response::json(['success' => true, 'data' => $features]);
    }

    /**
     * POST /api/map/boundary/{id} — save a GeoJSON boundary polygon for an item
     */
    public function saveBoundary(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $id       = (int)($params['id'] ?? 0);
        $geojson  = $request->post('geojson', '');

        if (!$id) {
            Response::json(['success' => false, 'message' => 'Invalid item ID'], 400);
        }

        // Validate it's actually JSON
        $decoded = json_decode($geojson, true);
        if ($decoded === null) {
            Response::json(['success' => false, 'message' => 'Invalid GeoJSON'], 400);
        }

        $db = DB::getInstance();

        // Verify item exists
        $item = $db->fetchOne('SELECT id, name FROM items WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$item) {
            Response::json(['success' => false, 'message' => 'Item not found'], 404);
        }

        // Upsert the boundary in item_meta
        $db->execute(
            "INSERT INTO item_meta (item_id, meta_key, meta_value, created_at, updated_at)
             VALUES (?, 'boundary_geojson', ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = NOW()",
            [$id, json_encode($decoded)]
        );

        Response::json(['success' => true, 'message' => 'Boundary saved for ' . $item['name']]);
    }

    /**
     * DELETE /api/map/boundary/{id} — remove boundary
     */
    public function deleteBoundary(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $id = (int)($params['id'] ?? 0);
        if (!$id) {
            Response::json(['success' => false, 'message' => 'Invalid item ID'], 400);
        }

        $db = DB::getInstance();
        $db->execute(
            "DELETE FROM item_meta WHERE item_id = ? AND meta_key = 'boundary_geojson'",
            [$id]
        );

        Response::json(['success' => true, 'message' => 'Boundary removed']);
    }
}
