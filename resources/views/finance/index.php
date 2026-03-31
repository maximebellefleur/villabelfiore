<div class="page-header">
    <h1 class="page-title">Finance</h1>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="grid grid-3">
    <div class="stat-card stat-card--success">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value"><?= number_format((float)($totals['total_revenue'] ?? 0), 2) ?> EUR</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-label">Total Costs</div>
        <div class="stat-value"><?= number_format((float)($totals['total_cost'] ?? 0), 2) ?> EUR</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Net</div>
        <div class="stat-value"><?= number_format((float)($totals['total_revenue'] ?? 0) - (float)($totals['total_cost'] ?? 0), 2) ?> EUR</div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3>Add Entry</h3>
        <form method="POST" action="<?= url('/finance') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="entry_type" class="form-input">
                        <option value="cost">Cost</option>
                        <option value="revenue">Revenue</option>
                        <option value="market_reference">Market Reference</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-input" placeholder="e.g. pruning, harvest">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-input" required>
                </div>
                <div class="form-group form-group--sm">
                    <label class="form-label">Amount (EUR)</label>
                    <input type="number" step="0.01" name="amount" class="form-input" required>
                </div>
                <div class="form-group form-group--sm">
                    <label class="form-label">Date</label>
                    <input type="date" name="entry_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Add Entry</button>
        </form>
    </div>
</div>

<h2 class="section-title">Recent Entries</h2>
<?php if (empty($entries)): ?>
<p class="text-muted">No finance entries yet.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Date</th><th>Type</th><th>Item</th><th>Category</th><th>Label</th><th>Amount</th></tr></thead>
    <tbody>
        <?php foreach ($entries as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['entry_date']) ?></td>
            <td><span class="badge badge-<?= $e['entry_type'] === 'revenue' ? 'success' : 'warning' ?>"><?= htmlspecialchars($e['entry_type']) ?></span></td>
            <td><?= $e['item_name'] ? htmlspecialchars($e['item_name']) : '—' ?></td>
            <td><?= htmlspecialchars($e['category']) ?></td>
            <td><?= htmlspecialchars($e['label']) ?></td>
            <td><?= number_format((float)$e['amount'], 2) ?> <?= htmlspecialchars($e['currency']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
