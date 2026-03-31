<div class="page-header">
    <h1 class="page-title">Land Overview</h1>
    <div class="page-actions">
        <a href="<?= url('/items/create') ?>" class="btn btn-primary">+ Add Item</a>
    </div>
</div>

<?php
$countsByType = [];
foreach (($itemCounts ?? []) as $row) {
    $countsByType[$row['type']] = (int) $row['cnt'];
}
$itemTypes  = require BASE_PATH . '/config/item_types.php';
$totalItems = array_sum($countsByType);

$db = \App\Support\DB::getInstance();
$gpsRow   = $db->fetchOne('SELECT COUNT(*) AS cnt FROM items WHERE gps_lat IS NOT NULL AND deleted_at IS NULL AND status = ?', ['active']);
$noGpsRow = $db->fetchOne('SELECT COUNT(*) AS cnt FROM items WHERE gps_lat IS NULL AND deleted_at IS NULL AND status = ?', ['active']);
$withGps    = (int)($gpsRow['cnt'] ?? 0);
$withoutGps = (int)($noGpsRow['cnt'] ?? 0);
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__value" data-stat="total_items"><?= $totalItems ?></div>
        <div class="stat-card__label">Total Items</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value"><?= $countsByType['olive_tree'] ?? 0 ?></div>
        <div class="stat-card__label">Olive Trees</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value"><?= $countsByType['almond_tree'] ?? 0 ?></div>
        <div class="stat-card__label">Almond Trees</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value"><?= ($countsByType['garden'] ?? 0) + ($countsByType['bed'] ?? 0) ?></div>
        <div class="stat-card__label">Gardens &amp; Beds</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value"><?= $countsByType['orchard'] ?? 0 ?></div>
        <div class="stat-card__label">Orchards</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value"><?= $countsByType['prep_zone'] ?? 0 ?></div>
        <div class="stat-card__label">Prep Zones</div>
    </div>
</div>

<div class="content-grid">
    <div>
        <div class="widget">
            <div class="widget__header">Items by Type</div>
            <div class="widget__body">
                <?php if (empty($countsByType)): ?>
                    <p class="meta-empty">No items yet. <a href="<?= url('/items/create') ?>">Add your first item</a>.</p>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Type</th><th style="text-align:right">Count</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemTypes as $typeKey => $typeDef):
                            $cnt = $countsByType[$typeKey] ?? 0;
                            if ($cnt === 0) continue; ?>
                        <tr>
                            <td><span class="type-badge type-<?= e($typeKey) ?>"><?= e($typeDef['label']) ?></span></td>
                            <td style="text-align:right;font-weight:600"><?= $cnt ?></td>
                            <td><a href="<?= url('/items?type=' . (urlencode($typeKey))) ?>" class="btn btn-xs btn-secondary">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div>
        <div class="widget">
            <div class="widget__header">Quick Actions</div>
            <div class="widget__body">
                <div style="display:flex;flex-direction:column;gap:8px">
                    <a href="<?= url('/items/create') ?>"  class="btn btn-primary"   style="text-align:center">+ Add Item</a>
                    <a href="<?= url('/reminders') ?>"     class="btn btn-secondary" style="text-align:center">Reminders</a>
                    <a href="<?= url('/finance') ?>"       class="btn btn-secondary" style="text-align:center">Finance</a>
                    <a href="<?= url('/activity-log') ?>"  class="btn btn-secondary" style="text-align:center">Activity Log</a>
                </div>
            </div>
        </div>

        <div class="widget">
            <div class="widget__header">GPS Coverage</div>
            <div class="widget__body">
                <div class="meta-grid" style="grid-template-columns:1fr 1fr">
                    <div class="meta-item">
                        <div class="meta-item__label">Located</div>
                        <div class="meta-item__value" style="color:var(--color-success)"><?= $withGps ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-item__label">No GPS</div>
                        <div class="meta-item__value" style="color:var(--color-text-muted)"><?= $withoutGps ?></div>
                    </div>
                </div>
                <?php if ($withoutGps > 0): ?>
                <p class="gps-accuracy-note" style="margin-top:8px"><?= $withoutGps ?> active item(s) have no GPS coordinates. <a href="<?= url('/items?status=active') ?>">Review</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
