<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class FinanceController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db      = DB::getInstance();
        $entries = $db->fetchAll('SELECT f.*, i.name AS item_name FROM finance_entries f LEFT JOIN items i ON i.id=f.item_id ORDER BY f.entry_date DESC LIMIT 50');
        $totals  = $db->fetchOne("SELECT SUM(CASE WHEN entry_type='revenue' THEN amount ELSE 0 END) AS total_revenue, SUM(CASE WHEN entry_type='cost' THEN amount ELSE 0 END) AS total_cost FROM finance_entries");
        Response::render('finance/index', ['title' => 'Finance', 'entries' => $entries, 'totals' => $totals]);
    }

    public function forItem(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id      = (int) ($params['id'] ?? 0);
        $db      = DB::getInstance();
        $item    = $db->fetchOne('SELECT * FROM items WHERE id=? AND deleted_at IS NULL', [$id]);
        if (!$item) { http_response_code(404); echo '<h1>Item not found</h1>'; return; }
        $entries = $db->fetchAll('SELECT * FROM finance_entries WHERE item_id=? ORDER BY entry_date DESC', [$id]);
        Response::render('finance/item', ['title' => 'Finance — ' . e($item['name']), 'item' => $item, 'entries' => $entries]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $data = [
            'item_id'    => $request->post('item_id') ? (int)$request->post('item_id') : null,
            'entry_type' => $request->post('entry_type', 'cost'),
            'category'   => trim((string) $request->post('category', 'general')),
            'label'      => trim((string) $request->post('label', '')),
            'amount'     => (float) $request->post('amount', 0),
            'currency'   => $request->post('currency', 'EUR'),
            'notes'      => $request->post('notes', ''),
            'entry_date' => $request->post('entry_date', date('Y-m-d')),
        ];

        if (empty($data['label']) || $data['amount'] <= 0) {
            flash('error', 'Label and amount are required.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/finance');
        }

        DB::getInstance()->execute(
            'INSERT INTO finance_entries (item_id, entry_type, category, label, amount, currency, notes, entry_date, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())',
            array_values($data)
        );

        flash('success', 'Finance entry added.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/finance');
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute(
            'UPDATE finance_entries SET label=?, amount=?, notes=?, entry_date=? WHERE id=?',
            [$request->post('label', ''), (float)$request->post('amount', 0), $request->post('notes'), $request->post('entry_date', date('Y-m-d')), $id]
        );
        flash('success', 'Entry updated.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/finance');
    }

    public function trash(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int) ($params['id'] ?? 0);
        DB::getInstance()->execute('DELETE FROM finance_entries WHERE id=?', [$id]);
        flash('success', 'Entry removed.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/finance');
    }
}
