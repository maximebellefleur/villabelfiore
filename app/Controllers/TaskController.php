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
            list_type VARCHAR(20) NOT NULL DEFAULT 'todo',
            is_done TINYINT(1) NOT NULL DEFAULT 0,
            is_archived TINYINT(1) NOT NULL DEFAULT 0,
            is_important TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            due_date DATE,
            done_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        // Migrate existing installs
        foreach (['list_type VARCHAR(20) NOT NULL DEFAULT \'todo\'',
                  'is_important TINYINT(1) NOT NULL DEFAULT 0',
                  'sort_order INT NOT NULL DEFAULT 0'] as $col) {
            try { $db->execute("ALTER TABLE tasks ADD COLUMN $col"); } catch (\Throwable $e) {}
        }
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

        // To-Do tasks: important first, then by sort_order, then created
        $where = $showDone ? '' : 'AND is_done = 0';
        $tasks = $db->fetchAll(
            "SELECT * FROM tasks WHERE is_archived = 0 AND list_type = 'todo' {$where}
             ORDER BY is_important DESC, is_done ASC, sort_order ASC, created_at DESC"
        );

        // Achats: all active, grouped by category in PHP
        $achatRows = $db->fetchAll(
            "SELECT * FROM tasks WHERE is_archived = 0 AND list_type = 'achat'
             ORDER BY is_done ASC, category ASC, sort_order ASC, created_at DESC"
        );
        // Group achats by category
        $achats = [];
        foreach ($achatRows as $a) {
            $key = $a['category'] !== '' ? $a['category'] : '__none__';
            $achats[$key][] = $a;
        }

        $archiveCount = (int)($db->fetchOne('SELECT COUNT(*) AS c FROM tasks WHERE is_archived = 1')['c'] ?? 0);

        $reminders = $db->fetchAll(
            "SELECT r.*, i.name AS item_name FROM reminders r LEFT JOIN items i ON i.id=r.item_id WHERE r.status = 'pending' ORDER BY r.due_at ASC LIMIT 30"
        );

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
            'achats'         => $achats,
            'achatsTotal'    => count($achatRows),
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
        $listType = in_array($request->post('list_type', 'todo'), ['todo','achat'], true)
                    ? $request->post('list_type') : 'todo';

        if ($title === '') {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                Response::json(['success' => false, 'message' => 'Title required']);
            }
            flash('error', 'Task title is required.');
            Response::redirect('/tasks');
            return;
        }

        $db->execute(
            'INSERT INTO tasks (title, category, notes, due_date, list_type, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())',
            [$title, $category, $notes, $dueDate, $listType]
        );
        $newId = (int)$db->lastInsertId();

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::json(['success' => true, 'task' => [
                'id'          => $newId,
                'title'       => $title,
                'category'    => $category,
                'due_date'    => $dueDate,
                'list_type'   => $listType,
                'is_done'     => 0,
                'is_important'=> 0,
            ]]);
            return;
        }

        Response::redirect('/tasks' . ($request->post('tab') ? '?tab=' . urlencode($request->post('tab')) : ''));
    }

    // -------------------------------------------------------------------------
    // POST /tasks/{id}/toggle
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
    // POST /tasks/{id}/important  — toggle important flag
    // -------------------------------------------------------------------------
    public function toggleImportant(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        $db = DB::getInstance();

        $row = $db->fetchOne('SELECT is_important FROM tasks WHERE id = ?', [$id]);
        if (!$row) { Response::json(['success' => false]); return; }

        $imp = $row['is_important'] ? 0 : 1;
        $db->execute('UPDATE tasks SET is_important = ?, updated_at = NOW() WHERE id = ?', [$imp, $id]);

        Response::json(['success' => true, 'is_important' => $imp]);
    }

    // -------------------------------------------------------------------------
    // POST /tasks/reorder  — bulk reorder (array of IDs in new order)
    // -------------------------------------------------------------------------
    public function reorder(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $ids = $request->post('ids', '');
        if (is_string($ids)) { $ids = json_decode($ids, true) ?: []; }
        if (!is_array($ids)) { Response::json(['success' => false]); return; }

        $db = DB::getInstance();
        foreach ($ids as $order => $id) {
            $db->execute('UPDATE tasks SET sort_order = ?, updated_at = NOW() WHERE id = ?', [(int)$order, (int)$id]);
        }

        Response::json(['success' => true]);
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

        if ($request->post('ajax') || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
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

        if ($request->post('ajax') || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::json(['success' => true]);
            return;
        }

        flash('success', 'Task deleted.');
        Response::redirect(strpos($_SERVER['HTTP_REFERER'] ?? '', 'archive') !== false ? '/tasks/archive' : '/tasks');
    }
}
