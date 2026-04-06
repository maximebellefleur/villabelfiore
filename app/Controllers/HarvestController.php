<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;
use App\Support\HarvestConfig;

class HarvestController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id       = (int) ($params['id'] ?? 0);
        $db       = DB::getInstance();
        $item     = $db->fetchOne('SELECT * FROM items WHERE id=? AND deleted_at IS NULL', [$id]);
        if (!$item) { http_response_code(404); echo '<h1>Item not found</h1>'; return; }
        $harvests = $db->fetchAll('SELECT * FROM harvest_entries WHERE item_id=? ORDER BY recorded_at DESC', [$id]);
        Response::render('harvests/index', ['title' => 'Harvests', 'item' => $item, 'harvests' => $harvests]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $id         = (int) ($params['id'] ?? 0);
        $quantity   = (float) $request->post('quantity', 0);
        $unit       = trim((string) $request->post('unit', ''));
        $rawDate    = trim((string) $request->post('recorded_at', ''));
        $recordedAt = $rawDate ?: date('Y-m-d H:i:s');
        $redirect   = $request->post('_redirect', '/items/' . $id);

        if ($quantity <= 0 || empty($unit)) {
            flash('error', 'Quantity and unit are required.');
            Response::redirect($redirect);
        }

        DB::getInstance()->execute(
            'INSERT INTO harvest_entries (item_id, harvest_type, quantity, unit, quality_grade, notes, recorded_at, created_at) VALUES (?,?,?,?,?,?,?,NOW())',
            [$id, $request->post('harvest_type', 'general'), $quantity, $unit, $request->post('quality_grade'), $request->post('notes'), $recordedAt]
        );

        flash('success', 'Harvest recorded.');
        Response::redirect($redirect);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute(
            'UPDATE harvest_entries SET quantity=?, unit=?, notes=? WHERE id=?',
            [(float)$request->post('quantity', 0), $request->post('unit', ''), $request->post('notes'), $id]
        );
        flash('success', 'Harvest updated.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/reminders');
    }

    public function trash(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id       = (int) ($params['id'] ?? 0);
        $redirect = $request->post('_redirect', $_SERVER['HTTP_REFERER'] ?? '/items');
        DB::getInstance()->execute('DELETE FROM harvest_entries WHERE id=?', [$id]);
        flash('success', 'Harvest entry removed.');
        Response::redirect($redirect);
    }

    public function apiStore(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) { Response::json(['success' => false, 'message' => 'Unauthenticated'], 401); }
        $this->store($request, $params);
    }

    public function quickEntry(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();

        // Load merged harvest config (DB overrides + item_types.php defaults)
        $harvestConfig = HarvestConfig::get();

        // Harvest-enabled types + their max-per-year
        $harvestTypes  = [];
        $maxPerYearMap = [];
        foreach ($harvestConfig as $typeKey => $cfg) {
            if (!empty($cfg['enabled'])) {
                $harvestTypes[]          = $typeKey;
                $maxPerYearMap[$typeKey] = (int) $cfg['max_per_year'];
            }
        }

        if (empty($harvestTypes)) {
            $items = [];
        } else {
            $placeholders = implode(',', array_fill(0, count($harvestTypes), '?'));
            $items = $db->fetchAll(
                "SELECT id, name, type, gps_lat, gps_lng FROM items WHERE type IN ($placeholders) AND status = 'active' AND deleted_at IS NULL ORDER BY type, name",
                $harvestTypes
            );
        }

        // Year harvest counts per item
        $year       = date('Y');
        $yearCounts = []; // item_id => count
        if (!empty($items)) {
            $itemIds  = array_column($items, 'id');
            $ph       = implode(',', array_fill(0, count($itemIds), '?'));
            $rows     = $db->fetchAll(
                "SELECT item_id, COUNT(*) AS cnt FROM harvest_entries WHERE item_id IN ($ph) AND YEAR(recorded_at) = ? GROUP BY item_id",
                array_merge($itemIds, [$year])
            );
            foreach ($rows as $r) { $yearCounts[(int)$r['item_id']] = (int)$r['cnt']; }
        }

        // Year harvest entries per item (for display/delete)
        $yearHarvests = [];
        if (!empty($items)) {
            $itemIds = array_column($items, 'id');
            $ph      = implode(',', array_fill(0, count($itemIds), '?'));
            $rows    = $db->fetchAll(
                "SELECT h.*, i.name AS item_name FROM harvest_entries h JOIN items i ON i.id = h.item_id WHERE h.item_id IN ($ph) AND YEAR(h.recorded_at) = ? ORDER BY h.recorded_at DESC",
                array_merge($itemIds, [$year])
            );
            foreach ($rows as $r) {
                $yearHarvests[(int)$r['item_id']][] = $r;
            }
        }

        Response::render('harvests/quick', [
            'title'         => 'Quick Harvest',
            'items'         => $items,
            'maxPerYearMap' => $maxPerYearMap,
            'harvestConfig' => $harvestConfig,
            'yearCounts'    => $yearCounts,
            'yearHarvests'  => $yearHarvests,
            'year'          => $year,
        ]);
    }
}
