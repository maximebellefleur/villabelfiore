<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class TaskController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) Response::redirect('/login');
    }

    private function ensureTable(DB $db): void
    {
        $db->execute("CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            category VARCHAR(100) DEFAULT '',
            notes TEXT,
            is_done TINYINT(1) NOT NULL DEFAULT 0,
            is_archived TINYINT(1) NOT NULL DEFAULT 0,
            due_date DATE,
            done_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    }

    // -------------------------------------------------------------------------
    // GET /tasks
    // -------------------------------------------------------------------------
    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $this->ensureTable($db);

        $tab      = $request->get('tab', 'todos');
        $showDone = (bool)($request->get('done', '0'));

        // Active tasks
        $where = $showDone ? '' : 'AND is_done = 0';
        $tasks = $db->fetchAll(
            "SELECT * FROM tasks WHERE is_archived = 0 {$where} ORDER BY is_done ASC, due_date ASC, created_at DESC"
        );

        // Archive count
        $archiveCount = (int)($db->fetchOne('SELECT COUNT(*) AS c FROM tasks WHERE is_archived = 1')['c'] ?? 0);

        // Reminders (for the Reminders tab)
        $reminders = $db->fetchAll(
            "SELECT r.*, i.name AS item_name FROM reminders r LEFT JOIN items i ON i.id=r.item_id WHERE r.status = 'pending' ORDER BY r.due_at ASC LIMIT 30"
        );

        // Irrigation plans (for the Irrigation tab)
        $irrigationPlans = [];
        try {
            $irrigationPlans = $db->fetchAll(
                "SELECT ip.*, i.name AS item_name, i.type AS item_type
                 FROM irrigation_plans ip
                 JOIN items i ON i.id = ip.item_id
                 WHERE i.deleted_at IS NULL AND i.status != 'trashed'
                 ORDER BY ip.start_date ASC, i.name ASC"
            );
        } catch (\Throwable $e) {}

        Response::render('tasks/index', [
            'title'          => 'Tasks',
            'tasks'          => $tasks,
            'archiveCount'   => $archiveCount,
            'tab'            => $tab,
            'showDone'       => $showDone,
            'reminders'      => $reminders,
            'irrigationPlans'=> $irrigationPlans,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /tasks/archive
    // -------------------------------------------------------------------------
    public function archive(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $this->ensureTable($db);

        $archived = $db->fetchAll(
            "SELECT * FROM tasks WHERE is_archived = 1 ORDER BY updated_at DESC"
        );

        Response::render('tasks/archive', [
            'title'    => 'Task Archive',
            'archived' => $archived,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /tasks  — create
    // -------------------------------------------------------------------------
    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureTable($db);

        $title    = trim($request->post('title', ''));
        $category = trim($request->post('category', ''));
        $notes    = trim($request->post('notes', ''));
        $dueDate  = trim($request->post('due_date', '')) ?: null;

        if ($title === '') {
            flash('error', 'Task title is required.');
            Response::redirect('/tasks');
            return;
        }

        $db->execute(
            'INSERT INTO tasks (title, category, notes, due_date, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())',
            [$title, $category, $notes, $dueDate]
        );

        Response::redirect('/tasks' . ($request->post('tab') ? '?tab=' . urlencode($request->post('tab')) : ''));
    }

    // -------------------------------------------------------------------------
    // POST /tasks/{id}/toggle  — AJAX done/undone
    // -------------------------------------------------------------------------
    public function toggle(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        $db = DB::getInstance();

        $row = $db->fetchOne('SELECT * FROM tasks WHERE id = ?', [$id]);
        if (!$row) { Response::json(['success' => false]); return; }

        $done = $row['is_done'] ? 0 : 1;
        $db->execute(
            'UPDATE tasks SET is_done = ?, done_at = ?, updated_at = NOW() WHERE id = ?',
            [$done, $done ? date('Y-m-d H:i:s') : null, $id]
        );

        Response::json(['success' => true, 'is_done' => $done]);
    }

    // -------------------------------------------------------------------------
    // POST /tasks/{id}/archive
    // -------------------------------------------------------------------------
    public function archiveTask(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        $db = DB::getInstance();

        $db->execute('UPDATE tasks SET is_archived = 1, updated_at = NOW() WHERE id = ?', [$id]);

        if ($request->post('ajax')) {
            Response::json(['success' => true]);
            return;
        }

        flash('success', 'Task archived.');
        Response::redirect('/tasks');
    }

    // -------------------------------------------------------------------------
    // POST /tasks/{id}/unarchive
    // -------------------------------------------------------------------------
    public function unarchive(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        $db = DB::getInstance();

        $db->execute('UPDATE tasks SET is_archived = 0, updated_at = NOW() WHERE id = ?', [$id]);

        flash('success', 'Task restored.');
        Response::redirect('/tasks/archive');
    }

    // -------------------------------------------------------------------------
    // POST /tasks/{id}/delete
    // -------------------------------------------------------------------------
    public function destroy(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        $db = DB::getInstance();

        $db->execute('DELETE FROM tasks WHERE id = ?', [$id]);

        if ($request->post('ajax')) {
            Response::json(['success' => true]);
            return;
        }

        flash('success', 'Task deleted.');
        Response::redirect(strpos($_SERVER['HTTP_REFERER'] ?? '', 'archive') !== false ? '/tasks/archive' : '/tasks');
    }
}
