<?php
// Type emoji map
$typeEmoji = [
    'olive_tree'  => '🫒',
    'tree'        => '🌳',
    'vine'        => '🍇',
    'almond_tree' => '🌰',
    'garden'      => '🌿',
    'zone'        => '🛖',
    'orchard'     => '🏕',
    'bed'         => '🌱',
    'line'        => '〰️',
    'prep_zone'   => '🟫',
    'mobile_coop' => '🐓',
    'building'    => '🏠',
    'water_point' => '💧',
];

$totalItems = 0;
foreach ($itemCounts as $c) { $totalItems += (int)$c['cnt']; }
$overdueCount  = count($overdueReminders);
$upcomingCount = count($upcomingReminders);

// Build monthly harvest totals (sum all units by month, pick the primary one)
$monthlyTotals = array_fill(1, 12, 0);
$monthlyUnit   = '';
foreach ($monthlyHarvest as $row) {
    $mo = (int)$row['mo'];
    $monthlyTotals[$mo] = ($monthlyTotals[$mo] ?? 0) + (float)$row['total'];
    if (!$monthlyUnit) $monthlyUnit = $row['unit'];
}
$maxMonthly = max($monthlyTotals) ?: 1;
$hasHarvest = max($monthlyTotals) > 0;
$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$currentYear = date('Y');
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <a href="<?= url('/items/create') ?>" class="btn btn-primary">+ Add Item</a>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- ============================================================
     QUICK ACTION STRIP
     ============================================================ -->
<div class="quick-actions-strip">
    <a href="<?= url('/dashboard/map') ?>" class="quick-action-btn">
        <span class="quick-action-icon">🗺</span>
        <span class="quick-action-label">Add to Map</span>
    </a>
    <a href="<?= url('/harvest/quick') ?>" class="quick-action-btn quick-action-btn--harvest">
        <span class="quick-action-icon">🌾</span>
        <span class="quick-action-label">Record Harvest</span>
    </a>
    <a href="<?= url('/items') ?>" class="quick-action-btn">
        <span class="quick-action-icon">📷</span>
        <span class="quick-action-label">Upload Photo</span>
    </a>
    <a href="<?= url('/items') ?>" class="quick-action-btn">
        <span class="quick-action-icon">📋</span>
        <span class="quick-action-label">Log Action</span>
    </a>
</div>

<!-- ============================================================
     STATS BAR
     ============================================================ -->
<div class="dash-stats-bar">
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= $totalItems ?></div>
        <div class="dash-stat-label">Total Items</div>
    </div>
    <div class="dash-stat-card <?= $overdueCount > 0 ? 'dash-stat-card--warning' : '' ?>">
        <div class="dash-stat-value"><?= $overdueCount ?></div>
        <div class="dash-stat-label">Overdue</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= $upcomingCount ?></div>
        <div class="dash-stat-label">Upcoming</div>
    </div>
</div>

<!-- ============================================================
     OVERDUE REMINDERS (if any)
     ============================================================ -->
<?php if (!empty($overdueReminders)): ?>
<section class="dash-section">
    <h2 class="dash-section-title">⚠️ Overdue Reminders</h2>
    <div class="dash-overdue-list">
        <?php foreach ($overdueReminders as $r): ?>
        <div class="dash-overdue-item">
            <div class="dash-overdue-info">
                <strong><?= e($r['title']) ?></strong>
                <span class="text-muted text-sm"><?= e(date('d M Y', strtotime($r['due_at']))) ?></span>
                <?php if ($r['item_id']): ?>
                <a href="<?= url('/items/' . ((int)$r['item_id'])) ?>" class="text-sm">View Item</a>
                <?php endif; ?>
            </div>
            <div class="dash-overdue-actions">
                <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/complete') ?>" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-success">✓ Done</button>
                </form>
                <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/dismiss') ?>" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-secondary">Dismiss</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     HARVEST CHART
     ============================================================ -->
<section class="dash-section">
    <h2 class="dash-section-title">🌾 <?= $currentYear ?> Harvest</h2>
    <?php if (!$hasHarvest): ?>
    <p class="text-muted" style="padding:var(--spacing-5) 0;text-align:center;font-style:italic">No harvests recorded yet this year.</p>
    <?php else: ?>
    <div class="dash-harvest-chart" aria-label="Monthly harvest chart">
        <?php for ($m = 1; $m <= 12; $m++):
            $val     = $monthlyTotals[$m];
            $pct     = $maxMonthly > 0 ? round(($val / $maxMonthly) * 100) : 0;
            $isFuture = $m > (int)date('n');
        ?>
        <div class="dash-bar-col <?= $isFuture ? 'dash-bar-col--future' : '' ?>">
            <div class="dash-bar-wrap">
                <div class="dash-bar" style="height:<?= $pct ?>%" title="<?= $monthNames[$m-1] ?>: <?= number_format($val,1) ?> <?= e($monthlyUnit) ?>">
                    <?php if ($val > 0): ?>
                    <span class="dash-bar-val"><?= number_format($val,0) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dash-bar-label"><?= $monthNames[$m-1] ?></div>
        </div>
        <?php endfor; ?>
    </div>
    <?php if ($monthlyUnit): ?>
    <p class="text-muted text-sm" style="text-align:right;margin-top:var(--spacing-2)">Unit: <?= e($monthlyUnit) ?></p>
    <?php endif; ?>
    <?php endif; ?>
</section>

<!-- ============================================================
     ITEMS BY TYPE
     ============================================================ -->
<?php if (!empty($itemCounts)): ?>
<section class="dash-section">
    <h2 class="dash-section-title">🌿 Items by Type</h2>
    <div class="dash-type-grid">
        <?php foreach ($itemCounts as $c):
            $emoji   = $typeEmoji[$c['type']] ?? '📦';
            $label   = ucwords(str_replace('_', ' ', $c['type']));
            $typeKey = e($c['type']);
        ?>
        <a href="<?= url('/items?type=' . $typeKey) ?>" class="dash-type-card dash-type-card--<?= $typeKey ?>">
            <span class="dash-type-emoji"><?= $emoji ?></span>
            <span class="dash-type-count"><?= (int)$c['cnt'] ?></span>
            <span class="dash-type-name"><?= e($label) ?></span>
            <?php if (!empty($harvestByTypeMap[$c['type']])): ?>
            <span class="dash-type-harvest text-muted">
                <?php
                $parts = [];
                foreach ($harvestByTypeMap[$c['type']] as $unit => $qty) {
                    $parts[] = number_format($qty, 0) . ' ' . e($unit);
                }
                echo implode(', ', $parts) . ' this year';
                ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     TWO-COLUMN: REMINDERS + ACTIVITY
     ============================================================ -->
<div class="dash-two-col">
    <section class="dash-widget">
        <div class="dash-widget-header">
            <span>📅 Upcoming Reminders</span>
            <a href="<?= url('/reminders') ?>" class="dash-widget-link">All &rarr;</a>
        </div>
        <div class="dash-widget-body">
            <?php if (empty($upcomingReminders)): ?>
            <p class="text-muted dash-widget-empty">No upcoming reminders.</p>
            <?php else: ?>
            <ul class="dash-reminder-list">
                <?php foreach ($upcomingReminders as $r): ?>
                <li class="dash-reminder-item">
                    <span class="dash-reminder-dot"></span>
                    <div class="dash-reminder-body">
                        <span class="dash-reminder-title"><?= e($r['title']) ?></span>
                        <span class="text-muted text-sm"><?= e(date('d M', strtotime($r['due_at']))) ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </section>

    <section class="dash-widget">
        <div class="dash-widget-header">
            <span>⚡ Recent Activity</span>
            <a href="<?= url('/activity-log') ?>" class="dash-widget-link">Full log &rarr;</a>
        </div>
        <div class="dash-widget-body">
            <?php if (empty($recentActivity)): ?>
            <p class="text-muted dash-widget-empty">No recent activity.</p>
            <?php else: ?>
            <ul class="dash-activity-list">
                <?php foreach ($recentActivity as $a): ?>
                <li class="dash-activity-item">
                    <span class="dash-activity-badge"><?= e($a['action_label']) ?></span>
                    <span class="dash-activity-desc text-muted"><?= e(mb_strimwidth($a['description'], 0, 55, '…')) ?></span>
                    <span class="dash-activity-time text-muted text-sm"><?= e(date('d M H:i', strtotime($a['performed_at']))) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
/* -----------------------------------------------
   Quick Action Strip
   ----------------------------------------------- */
.quick-actions-strip {
    display: flex;
    gap: var(--spacing-3);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: var(--spacing-2);
    margin-bottom: var(--spacing-5);
}
.quick-actions-strip::-webkit-scrollbar { display: none; }

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    min-width: 90px;
    padding: var(--spacing-4) var(--spacing-3);
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    text-decoration: none;
    color: var(--color-text);
    font-size: 0.8rem;
    font-weight: 600;
    transition: box-shadow .15s, transform .1s;
    flex-shrink: 0;
    box-shadow: var(--shadow-sm);
    cursor: pointer;
}
.quick-action-btn:hover {
    text-decoration: none;
    box-shadow: var(--shadow);
    transform: translateY(-2px);
    border-color: var(--color-primary);
}
.quick-action-btn--harvest {
    background: linear-gradient(135deg, #f9f7f4, #e8f5e1);
    border-color: rgba(45,90,39,.25);
}
.quick-action-icon { font-size: 1.75rem; line-height: 1; }
.quick-action-label { text-align: center; line-height: 1.2; white-space: nowrap; }

@media (min-width: 600px) {
    .quick-action-btn { min-width: 110px; padding: var(--spacing-4); }
}

/* -----------------------------------------------
   Stats Bar
   ----------------------------------------------- */
.dash-stats-bar {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-5);
}
.dash-stat-card {
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    text-align: center;
    box-shadow: var(--shadow-sm);
}
.dash-stat-card--warning {
    background: #fff8f0;
    border-color: #f0a040;
}
.dash-stat-value {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--color-primary);
    line-height: 1;
    margin-bottom: var(--spacing-1);
}
.dash-stat-card--warning .dash-stat-value { color: var(--color-warning); }
.dash-stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--color-text-muted);
}

/* -----------------------------------------------
   Section
   ----------------------------------------------- */
.dash-section {
    margin-bottom: var(--spacing-6);
}
.dash-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: var(--spacing-4);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

/* -----------------------------------------------
   Overdue Reminders
   ----------------------------------------------- */
.dash-overdue-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}
.dash-overdue-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-3);
    padding: var(--spacing-3) var(--spacing-4);
    background: #fff8f0;
    border: 1px solid #f0a040;
    border-left: 4px solid var(--color-warning);
    border-radius: var(--radius);
    flex-wrap: wrap;
}
.dash-overdue-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}
.dash-overdue-actions {
    display: flex;
    gap: var(--spacing-2);
    flex-shrink: 0;
}

/* -----------------------------------------------
   Harvest Bar Chart
   ----------------------------------------------- */
.dash-harvest-chart {
    display: flex;
    align-items: flex-end;
    gap: 4px;
    height: 120px;
    padding: var(--spacing-2) 0;
    border-bottom: 2px solid var(--color-border);
    overflow-x: auto;
}
.dash-bar-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    min-width: 28px;
    height: 100%;
}
.dash-bar-col--future { opacity: .35; }
.dash-bar-wrap {
    flex: 1;
    width: 100%;
    display: flex;
    align-items: flex-end;
}
.dash-bar {
    width: 100%;
    background: linear-gradient(to top, var(--color-primary), var(--color-primary-light));
    border-radius: 3px 3px 0 0;
    min-height: 3px;
    position: relative;
    transition: opacity .15s;
    cursor: default;
}
.dash-bar:hover { opacity: .8; }
.dash-bar-val {
    position: absolute;
    top: -18px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.6rem;
    font-weight: 700;
    color: var(--color-primary);
    white-space: nowrap;
}
.dash-bar-label {
    font-size: 0.65rem;
    color: var(--color-text-muted);
    margin-top: 4px;
    text-align: center;
}

/* -----------------------------------------------
   Items by Type Grid
   ----------------------------------------------- */
.dash-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: var(--spacing-3);
}
.dash-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-1);
    padding: var(--spacing-4) var(--spacing-3);
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-left: 4px solid var(--color-border);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--color-text);
    transition: box-shadow .15s, transform .1s;
    box-shadow: var(--shadow-sm);
}
.dash-type-card:hover {
    text-decoration: none;
    box-shadow: var(--shadow);
    transform: translateY(-2px);
}
.dash-type-emoji  { font-size: 1.8rem; line-height: 1; }
.dash-type-count  { font-size: 1.5rem; font-weight: 800; color: var(--color-primary); line-height: 1.1; }
.dash-type-name   { font-size: 0.72rem; font-weight: 600; text-align: center; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .04em; }
.dash-type-harvest{ font-size: 0.65rem; text-align: center; margin-top: 2px; }

/* Type-colored left borders */
.dash-type-card--olive_tree  { border-left-color: #4a7c43; }
.dash-type-card--tree        { border-left-color: #2d5a27; }
.dash-type-card--vine        { border-left-color: #8b3d9e; }
.dash-type-card--almond_tree { border-left-color: #8b5e3c; }
.dash-type-card--garden      { border-left-color: #1a5fa6; }
.dash-type-card--bed         { border-left-color: #1a5fa6; }
.dash-type-card--orchard     { border-left-color: #3d7a3d; }
.dash-type-card--zone        { border-left-color: #888; }
.dash-type-card--line        { border-left-color: #2c5faa; }

/* -----------------------------------------------
   Two-Column Layout
   ----------------------------------------------- */
.dash-two-col {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-5);
}
@media (min-width: 720px) {
    .dash-two-col { grid-template-columns: 1fr 1fr; }
}

.dash-widget {
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.dash-widget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: .05em;
}
.dash-widget-link {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0;
    color: var(--color-primary);
}
.dash-widget-body { padding: var(--spacing-3) var(--spacing-4); }
.dash-widget-empty { padding: var(--spacing-4) 0; text-align: center; font-style: italic; }

/* Reminder List */
.dash-reminder-list { list-style: none; padding: 0; margin: 0; }
.dash-reminder-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-2) 0;
    border-bottom: 1px solid var(--color-border);
}
.dash-reminder-item:last-child { border-bottom: none; }
.dash-reminder-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--color-primary);
    flex-shrink: 0;
}
.dash-reminder-body { display: flex; flex-direction: column; gap: 1px; flex: 1; min-width: 0; }
.dash-reminder-title { font-size: 0.875rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Activity List */
.dash-activity-list { list-style: none; padding: 0; margin: 0; }
.dash-activity-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: var(--spacing-2) 0;
    border-bottom: 1px solid var(--color-border);
}
.dash-activity-item:last-child { border-bottom: none; }
.dash-activity-badge {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 999px;
    background: rgba(45,90,39,.1);
    color: var(--color-primary);
    text-transform: uppercase;
    letter-spacing: .04em;
}
.dash-activity-desc { font-size: 0.83rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-activity-time { font-size: 0.72rem; }
</style>
