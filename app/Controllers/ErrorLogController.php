<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;

class ErrorLogController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db   = DB::getInstance();
        $logs = $db->fetchAll('SELECT * FROM error_logs ORDER BY created_at DESC LIMIT 100');
        Response::render('logs/errors', ['title' => 'Error Logs', 'logs' => $logs]);
    }

    public function show(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id  = (int) ($params['id'] ?? 0);
        $log = DB::getInstance()->fetchOne('SELECT * FROM error_logs WHERE id=?', [$id]);
        if (!$log) { http_response_code(404); echo '<h1>Not found</h1>'; return; }
        Response::render('logs/error-detail', ['title' => 'Error Detail', 'log' => $log]);
    }

    public function activity(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db   = DB::getInstance();
        $logs = $db->fetchAll('SELECT a.*, i.name AS item_name FROM activity_log a LEFT JOIN items i ON i.id=a.item_id ORDER BY a.performed_at DESC LIMIT 200');
        Response::render('logs/activity', ['title' => 'Activity Log', 'logs' => $logs]);
    }
}
