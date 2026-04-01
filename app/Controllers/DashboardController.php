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

        Response::render('dashboard/index', [
            'title'             => 'Dashboard',
            'itemCounts'        => $itemCounts,
            'recentActivity'    => $recentActivity,
            'upcomingReminders' => $upcomingReminders,
            'overdueReminders'  => $overdueReminders,
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

        Response::render('dashboard/map', [
            'title'      => 'Land Map',
            'mapEnabled' => true,
            'gpsCount'   => (int)($gpsCount['cnt'] ?? 0),
            'defaultLat' => (float)($center['lat'] ?? 41.9),
            'defaultLng' => (float)($center['lng'] ?? 12.5),
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
