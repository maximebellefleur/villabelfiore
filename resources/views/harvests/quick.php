<div class="page-header">
    <h1 class="page-title">🌾 Quick Harvest</h1>
    <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">&larr; Dashboard</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (empty($items)): ?>
<div class="alert alert-info">No harvestable items found. Add some trees or vines first.</div>
<?php else: ?>

<p class="text-muted" style="margin-bottom:var(--spacing-4)">
    Select an item and record a harvest directly. Quick, no navigation required.
</p>

<div class="quick-harvest-grid">
<?php
$typeEmoji = [
    'olive_tree' => '🫒', 'tree' => '🌳', 'vine' => '🍇',
    'almond_tree' => '🌰', 'fig_tree' => '🍑',
];
$currentType = null;
foreach ($items as $item):
    if ($currentType !== $item['type']):
        if ($currentType !== null) echo '</div>'; // close prev group
        $currentType = $item['type'];
        $emoji = $typeEmoji[$currentType] ?? '🌿';
        $typeLabel = ucwords(str_replace('_', ' ', $currentType));
?>
<div class="qh-type-group">
    <h3 class="qh-type-title"><?= $emoji ?> <?= e($typeLabel) ?></h3>
<?php endif; ?>

<div class="qh-item-card">
    <div class="qh-item-name"><?= e($item['name']) ?></div>
    <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/harvests') ?>" class="qh-form">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
        <input type="hidden" name="harvest_type" value="general">
        <div class="qh-fields">
            <input type="number" step="0.1" min="0.001" name="quantity"
                   class="form-input form-input--touch qh-qty"
                   placeholder="Qty" required>
            <select name="unit" class="form-input form-input--touch qh-unit">
                <option value="kg">kg</option>
                <option value="L">L</option>
                <option value="pcs">pcs</option>
                <option value="box">box</option>
                <option value="t">t (tonne)</option>
            </select>
        </div>
        <input type="datetime-local" name="recorded_at"
               class="form-input form-input--touch qh-date"
               value="<?= date('Y-m-d\TH:i') ?>" required>
        <button type="submit" class="btn btn-primary qh-submit">✓ Record</button>
    </form>
</div>

<?php endforeach; ?>
<?php if ($currentType !== null) echo '</div>'; // close last group ?>
</div>

<?php endif; ?>

<?php if (!empty($recentHarvests)): ?>
<section class="section" style="margin-top:var(--spacing-6)">
    <h2 class="section-title">Today's Entries</h2>
    <div class="card-list">
    <?php foreach ($recentHarvests as $h): ?>
    <div class="card card-body" style="display:flex;align-items:center;justify-content:space-between;gap:var(--spacing-3)">
        <div>
            <strong><?= e($h['item_name']) ?></strong>
            <span class="text-muted" style="font-size:.85rem"> — <?= number_format((float)$h['quantity'],2) ?> <?= e($h['unit']) ?></span>
        </div>
        <span class="text-muted" style="font-size:.8rem"><?= e(date('H:i', strtotime($h['recorded_at']))) ?></span>
    </div>
    <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<style>
.quick-harvest-grid { display: flex; flex-direction: column; gap: var(--spacing-5); }
.qh-type-group {}
.qh-type-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-primary);
    margin: 0 0 var(--spacing-3);
    padding-bottom: var(--spacing-2);
    border-bottom: 2px solid var(--color-primary);
    display: inline-block;
}
.qh-item-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--spacing-3) var(--spacing-4);
    margin-bottom: var(--spacing-3);
    box-shadow: var(--shadow-card);
}
.qh-item-name {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: var(--spacing-2);
}
.qh-form { display: flex; flex-direction: column; gap: var(--spacing-2); }
.qh-fields { display: flex; gap: var(--spacing-2); }
.qh-qty  { flex: 1; min-width: 80px; }
.qh-unit { width: 90px; flex-shrink: 0; }
.qh-date { width: 100%; }
.qh-submit { width: 100%; font-size: 1rem; padding: var(--spacing-3); }
</style>
