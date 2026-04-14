<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class ReminderController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db       = DB::getInstance();
        $overdue  = $db->fetchAll("SELECT r.*, i.name AS item_name FROM reminders r LEFT JOIN items i ON i.id=r.item_id WHERE r.status='pending' AND r.due_at < NOW() ORDER BY r.due_at ASC");
        $upcoming = $db->fetchAll("SELECT r.*, i.name AS item_name FROM reminders r LEFT JOIN items i ON i.id=r.item_id WHERE r.status='pending' AND r.due_at >= NOW() ORDER BY r.due_at ASC");
        $items    = $db->fetchAll("SELECT id, name, type, gps_lat, gps_lng FROM items WHERE status='active' ORDER BY name ASC");

        // Silently push any pending reminders not yet synced to Google Calendar
        try {
            (new \App\Controllers\CalendarController())->syncPendingReminders($db);
        } catch (\Throwable $e) { /* non-fatal */ }

        Response::render('reminders/index', ['title' => 'Reminders', 'overdue' => $overdue, 'upcoming' => $upcoming, 'items' => $items]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $title   = trim((string) $request->post('title', ''));
        $dueAt   = trim((string) $request->post('due_at', ''));
        $itemId  = $request->post('item_id') ? (int)$request->post('item_id') : null;
        $type    = $request->post('type', 'general');

        if (empty($title) || empty($dueAt)) {
            flash('error', 'Title and due date are required.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/reminders');
        }

        $db = DB::getInstance();
        $db->execute(
            'INSERT INTO reminders (item_id, type, title, due_at, is_recurring, status, created_at, updated_at) VALUES (?,?,?,?,0,?,NOW(),NOW())',
            [$itemId, $type, $title, $dueAt, 'pending']
        );
        $newReminderId = (int) $db->lastInsertId();

        if ($newReminderId) {
            try {
                (new \App\Controllers\CalendarController())->pushReminderById($db, $newReminderId);
            } catch (\Throwable $e) { /* non-fatal */ }
        }

        flash('success', 'Reminder created.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/reminders');
    }

    public function complete(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute("UPDATE reminders SET status='completed', updated_at=NOW() WHERE id=?", [$id]);
        flash('success', 'Reminder marked as complete.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/reminders');
    }

    public function dismiss(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute("UPDATE reminders SET status='dismissed', updated_at=NOW() WHERE id=?", [$id]);
        flash('success', 'Reminder dismissed.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/reminders');
    }

    public function apiIndex(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) { Response::json(['success' => false, 'message' => 'Unauthenticated'], 401); }
        $db  = DB::getInstance();
        $due = $db->fetchAll("SELECT * FROM reminders WHERE status='pending' ORDER BY due_at ASC LIMIT 20");
        Response::json(['success' => true, 'data' => $due]);
    }
}
