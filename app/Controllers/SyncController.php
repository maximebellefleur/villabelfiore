<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class SyncController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    public function status(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db    = DB::getInstance();
        $count = $db->fetchOne("SELECT COUNT(*) AS cnt FROM sync_queue WHERE sync_status='queued'");
        Response::json(['success' => true, 'data' => ['pending' => (int)($count['cnt'] ?? 0)]]);
    }

    public function push(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $payload = $request->post('payload', '');
        if (empty($payload)) {
            flash('error', 'No payload provided.');
            Response::redirect('/dashboard');
        }

        $db = DB::getInstance();
        $db->execute(
            'INSERT INTO sync_queue (entity_type, operation_type, payload_json, sync_status, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())',
            ['generic', 'create', $payload, 'queued']
        );

        flash('success', 'Data queued for sync.');
        Response::redirect('/dashboard');
    }

    public function conflicts(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db        = DB::getInstance();
        $conflicts = $db->fetchAll("SELECT * FROM sync_queue WHERE conflict_state='pending_review' ORDER BY created_at ASC");
        Response::render('sync/conflicts', ['title' => 'Sync Conflicts', 'conflicts' => $conflicts]);
    }

    public function resolve(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute("UPDATE sync_queue SET conflict_state='resolved', sync_status='synced', updated_at=NOW() WHERE id=?", [$id]);
        flash('success', 'Conflict resolved.');
        Response::redirect('/sync/conflicts');
    }

    public function apiPush(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) { Response::json(['success' => false, 'message' => 'Unauthenticated'], 401); }
        $this->push($request, $params);
    }
}
