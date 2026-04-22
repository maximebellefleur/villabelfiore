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

    private function ensureColumns(DB $db): void
    {
        // Add item_type column if missing
        try { $db->execute("ALTER TABLE finance_entries ADD COLUMN item_type VARCHAR(60) DEFAULT '' AFTER item_id"); } catch (\Throwable $e) {}
        // Add scope column (general|item_type|item)
        try { $db->execute("ALTER TABLE finance_entries ADD COLUMN scope VARCHAR(20) DEFAULT 'general' AFTER item_type"); } catch (\Throwable $e) {}
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $this->ensureColumns($db);

        $year = (int)($request->get('year', date('Y')));

        $entries = $db->fetchAll(
            'SELECT f.*, i.name AS item_name FROM finance_entries f
             LEFT JOIN items i ON i.id = f.item_id
             WHERE YEAR(f.entry_date) = ?
             ORDER BY f.entry_date DESC',
            [$year]
        );

        $totals = $db->fetchOne(
            "SELECT
                SUM(CASE WHEN entry_type='revenue' THEN amount ELSE 0 END) AS total_revenue,
                SUM(CASE WHEN entry_type='cost' THEN amount ELSE 0 END) AS total_cost
             FROM finance_entries WHERE YEAR(entry_date) = ?",
            [$year]
        );

        $allTotals = $db->fetchOne(
            "SELECT
                SUM(CASE WHEN entry_type='revenue' THEN amount ELSE 0 END) AS total_revenue,
                SUM(CASE WHEN entry_type='cost' THEN amount ELSE 0 END) AS total_cost
             FROM finance_entries"
        );

        // Get all years that have entries
        $years = $db->fetchAll('SELECT DISTINCT YEAR(entry_date) AS yr FROM finance_entries ORDER BY yr DESC');
        $yearList = array_column($years, 'yr');
        if (!in_array($year, $yearList)) $yearList[] = $year;
        rsort($yearList);

        // Item types for the form
        $itemTypes = require BASE_PATH . '/config/item_types.php';

        Response::render('finance/index', [
            'title'     => 'Finance',
            'entries'   => $entries,
            'totals'    => $totals,
            'allTotals' => $allTotals,
            'year'      => $year,
            'yearList'  => $yearList,
            'itemTypes' => $itemTypes,
        ]);
    }

    public function forItem(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $id   = (int)($params['id'] ?? 0);
        $db   = DB::getInstance();
        $item = $db->fetchOne('SELECT * FROM items WHERE id=? AND deleted_at IS NULL', [$id]);
        if (!$item) { http_response_code(404); echo '<h1>Not found</h1>'; return; }
        $entries = $db->fetchAll('SELECT * FROM finance_entries WHERE item_id=? ORDER BY entry_date DESC', [$id]);
        Response::render('finance/item', ['title' => 'Finance — ' . e($item['name']), 'item' => $item, 'entries' => $entries]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $db = DB::getInstance();
        $this->ensureColumns($db);

        $scope    = $request->post('scope', 'general');
        $itemType = $scope === 'item_type' ? trim($request->post('item_type', '')) : '';
        $itemId   = $scope === 'item' ? ((int)$request->post('item_id') ?: null) : null;

        $data = [
            'item_id'    => $itemId,
            'item_type'  => $itemType,
            'scope'      => $scope,
            'entry_type' => $request->post('entry_type', 'cost'),
            'category'   => trim($request->post('category', 'general')),
            'label'      => trim($request->post('label', '')),
            'amount'     => (float)$request->post('amount', 0),
            'currency'   => $request->post('currency', 'EUR'),
            'notes'      => trim($request->post('notes', '')),
            'entry_date' => $request->post('entry_date', date('Y-m-d')),
        ];

        if (empty($data['label']) || $data['amount'] <= 0) {
            flash('error', 'Label and amount are required.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/finance');
            return;
        }

        $db->execute(
            'INSERT INTO finance_entries (item_id, item_type, scope, entry_type, category, label, amount, currency, notes, entry_date, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,NOW())',
            [$data['item_id'], $data['item_type'], $data['scope'], $data['entry_type'], $data['category'], $data['label'], $data['amount'], $data['currency'], $data['notes'], $data['entry_date']]
        );

        flash('success', 'Entry added.');
        Response::redirect('/finance?year=' . date('Y', strtotime($data['entry_date'])));
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        $db = DB::getInstance();
        $this->ensureColumns($db);

        $scope    = $request->post('scope', 'general');
        $itemType = $scope === 'item_type' ? trim($request->post('item_type', '')) : '';
        $itemId   = $scope === 'item' ? ((int)$request->post('item_id') ?: null) : null;

        $db->execute(
            'UPDATE finance_entries SET label=?, amount=?, notes=?, entry_date=?, entry_type=?, category=?, item_type=?, scope=?, item_id=? WHERE id=?',
            [
                trim($request->post('label', '')),
                (float)$request->post('amount', 0),
                trim($request->post('notes', '')),
                $request->post('entry_date', date('Y-m-d')),
                $request->post('entry_type', 'cost'),
                trim($request->post('category', '')),
                $itemType,
                $scope,
                $itemId,
                $id,
            ]
        );

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::json(['success' => true]);
            return;
        }
        flash('success', 'Entry updated.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/finance');
    }

    public function trash(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $id = (int)($params['id'] ?? 0);
        DB::getInstance()->execute('DELETE FROM finance_entries WHERE id=?', [$id]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::json(['success' => true]);
            return;
        }
        flash('success', 'Entry removed.');
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/finance');
    }

    public function export(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db   = DB::getInstance();
        $year = $request->get('year', 'all');

        if ($year === 'all') {
            $entries = $db->fetchAll(
                'SELECT f.*, i.name AS item_name FROM finance_entries f LEFT JOIN items i ON i.id=f.item_id ORDER BY f.entry_date DESC'
            );
            $filename = 'finance-all-years.csv';
        } else {
            $yr = (int)$year;
            $entries = $db->fetchAll(
                'SELECT f.*, i.name AS item_name FROM finance_entries f LEFT JOIN items i ON i.id=f.item_id WHERE YEAR(f.entry_date)=? ORDER BY f.entry_date DESC',
                [$yr]
            );
            $filename = 'finance-' . $yr . '.csv';
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM so Excel opens it correctly
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Date', 'Type', 'Scope', 'Item Type', 'Item', 'Category', 'Label', 'Amount', 'Currency', 'Notes']);
        foreach ($entries as $e) {
            fputcsv($out, [
                $e['entry_date'],
                $e['entry_type'],
                $e['scope'] ?? 'general',
                $e['item_type'] ?? '',
                $e['item_name'] ?? '',
                $e['category'],
                $e['label'],
                number_format((float)$e['amount'], 2, '.', ''),
                $e['currency'],
                $e['notes'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
