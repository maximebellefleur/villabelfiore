<div class="page-header">
    <h1 class="page-title">Reports</h1>
</div>

<?php
$db = \App\Support\DB::getInstance();

// Harvest totals by item type this year
$harvestTotals = $db->fetchAll(
    'SELECT i.type, SUM(h.quantity) AS total_qty, h.unit
     FROM harvest_entries h
     JOIN items i ON i.id = h.item_id
     WHERE YEAR(h.recorded_at) = YEAR(NOW())
     GROUP BY i.type, h.unit
     ORDER BY total_qty DESC'
);

// Finance summary this year
$financeSummary = $db->fetchAll(
    "SELECT entry_type, category, SUM(amount) AS total, currency
     FROM finance_entries
     WHERE YEAR(entry_date) = YEAR(NOW())
     GROUP BY entry_type, category, currency
     ORDER BY entry_type, total DESC"
);

$totalRevenue = 0;
$totalCost    = 0;
foreach ($financeSummary as $row) {
    if ($row['entry_type'] === 'revenue') $totalRevenue += $row['total'];
    if ($row['entry_type'] === 'cost')    $totalCost    += $row['total'];
}

// Recent activity count by type
$activityBreakdown = $db->fetchAll(
    'SELECT action_type, COUNT(*) AS cnt
     FROM activity_log
     WHERE performed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY action_type
     ORDER BY cnt DESC
     LIMIT 10'
);
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__value" style="color:var(--color-success)">
            <?= number_format($totalRevenue, 2) ?>
        </div>
        <div class="stat-card__label">Revenue <?= date('Y') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:var(--color-danger)">
            <?= number_format($totalCost, 2) ?>
        </div>
        <div class="stat-card__label">Costs <?= date('Y') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value" style="color:<?= ($totalRevenue - $totalCost) >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>">
            <?= number_format($totalRevenue - $totalCost, 2) ?>
        </div>
        <div class="stat-card__label">Net <?= date('Y') ?></div>
    </div>
</div>

<div class="content-grid">
    <div>
        <!-- Harvest summary -->
        <div class="widget">
            <div class="widget__header">Harvest Summary — <?= date('Y') ?></div>
            <div class="widget__body">
                <?php if (empty($harvestTotals)): ?>
                    <p class="meta-empty">No harvests recorded this year.</p>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Item Type</th><th style="text-align:right">Quantity</th><th>Unit</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($harvestTotals as $row): ?>
                        <tr>
                            <td><span class="type-badge type-<?= e($row['type']) ?>"><?= e(ucfirst(str_replace('_', ' ', $row['type']))) ?></span></td>
                            <td style="text-align:right;font-weight:600"><?= number_format((float)$row['total_qty'], 2) ?></td>
                            <td><?= e($row['unit']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Finance breakdown -->
        <div class="widget">
            <div class="widget__header">Finance Breakdown — <?= date('Y') ?></div>
            <div class="widget__body">
                <?php if (empty($financeSummary)): ?>
                    <p class="meta-empty">No finance entries this year.</p>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Type</th><th>Category</th><th style="text-align:right">Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($financeSummary as $row): ?>
                        <tr>
                            <td>
                                <span class="badge <?= $row['entry_type'] === 'revenue' ? 'badge-success' : 'badge-error' ?>">
                                    <?= e(ucfirst($row['entry_type'])) ?>
                                </span>
                            </td>
                            <td><?= e($row['category']) ?></td>
                            <td style="text-align:right;font-weight:600">
                                <?= e($row['currency']) ?> <?= number_format((float)$row['total'], 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div>
        <!-- Activity in last 30 days -->
        <div class="widget">
            <div class="widget__header">Activity — Last 30 Days</div>
            <div class="widget__body">
                <?php if (empty($activityBreakdown)): ?>
                    <p class="meta-empty">No activity recorded.</p>
                <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($activityBreakdown as $row): ?>
                    <li class="activity-item">
                        <div class="activity-item__body">
                            <div class="activity-item__label"><?= e(ucfirst(str_replace('_', ' ', $row['action_type']))) ?></div>
                        </div>
                        <div class="activity-item__time"><?= $row['cnt'] ?>×</div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="widget">
            <div class="widget__header">Links</div>
            <div class="widget__body">
                <div style="display:flex;flex-direction:column;gap:8px">
                    <a href="<?= url('/activity-log') ?>" class="btn btn-secondary" style="text-align:center">Full Activity Log</a>
                    <a href="<?= url('/finance') ?>"      class="btn btn-secondary" style="text-align:center">All Finance Entries</a>
                    <a href="<?= url('/reminders') ?>"    class="btn btn-secondary" style="text-align:center">Reminders</a>
                </div>
            </div>
        </div>
    </div>
</div>
