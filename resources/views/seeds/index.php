<?php
$typeEmoji = ['vegetable'=>'🥦','herb'=>'🌿','fruit'=>'🍓','flower'=>'🌸','other'=>'🌾'];
$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<div class="page-header">
    <h1 class="page-title">🌱 Seed Catalog</h1>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="<?= url('/seeds/family-needs') ?>" class="btn btn-secondary btn-sm">👨‍👩‍👧 Family Needs</a>
        <a href="<?= url('/seeds/create') ?>" class="btn btn-primary">+ Add Seed</a>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (!empty($lowStock)): ?>
<div class="alert alert-warning" style="margin-bottom:var(--spacing-4);padding:var(--spacing-3);background:rgba(234,179,8,.12);border:1px solid rgba(234,179,8,.4);border-radius:var(--radius);color:#92400e">
    ⚠️ <strong><?= count($lowStock) ?> seed<?= count($lowStock) > 1 ? 's' : '' ?> running low:</strong>
    <?= implode(', ', array_map(fn($s) => e($s['name']), $lowStock)) ?>
</div>
<?php endif; ?>

<!-- Filter bar -->
<form method="GET" action="<?= url('/seeds') ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:var(--spacing-4);align-items:center">
    <input type="text" name="q" class="form-input" placeholder="Search seeds…" value="<?= e($search) ?>" style="flex:1;min-width:180px;max-width:320px">
    <select name="type" class="form-input" style="min-width:140px">
        <option value="">All types</option>
        <?php foreach (['vegetable','herb','fruit','flower','other'] as $t): ?>
        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ($typeEmoji[$t] ?? '') . ' ' . ucfirst($t) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($search || $type): ?>
    <a href="<?= url('/seeds') ?>" class="btn btn-ghost btn-sm">✕ Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($seeds)): ?>
<div class="empty-state" style="text-align:center;padding:var(--spacing-8) var(--spacing-4)">
    <div style="font-size:3rem;margin-bottom:var(--spacing-3)">🌱</div>
    <h3>No seeds yet</h3>
    <p class="text-muted">Add your first seed variety to start tracking your stock.</p>
    <a href="<?= url('/seeds/create') ?>" class="btn btn-primary" style="margin-top:var(--spacing-3)">+ Add Seed</a>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:var(--spacing-3)">
<?php foreach ($seeds as $seed):
    $low = $seed['stock_enabled'] && $seed['stock_low_threshold'] !== null && (float)$seed['stock_qty'] <= (float)$seed['stock_low_threshold'];
    $plantMonths = $seed['planting_months'] ? json_decode($seed['planting_months'], true) : [];
?>
<div class="card" style="border-left:3px solid <?= $low ? '#dc3545' : 'var(--color-primary)' ?>">
    <div class="card-body" style="padding:var(--spacing-3)">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
            <div>
                <div style="font-weight:700;font-size:1rem"><?= ($typeEmoji[$seed['type']] ?? '🌾') . ' ' . e($seed['name']) ?></div>
                <?php if ($seed['variety']): ?><div class="text-muted text-sm"><?= e($seed['variety']) ?></div><?php endif; ?>
            </div>
            <?php if ($seed['stock_enabled']): ?>
            <div style="text-align:right;flex-shrink:0">
                <div style="font-weight:700;font-size:1.1rem;color:<?= $low ? '#dc3545' : 'var(--color-primary)' ?>"><?= number_format((float)$seed['stock_qty'], 1) ?></div>
                <div class="text-muted text-sm"><?= e($seed['stock_unit']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($plantMonths)): ?>
        <div style="margin-top:8px;display:flex;gap:3px;flex-wrap:wrap">
            <?php foreach (range(1,12) as $m): ?>
            <span style="font-size:0.65rem;padding:1px 4px;border-radius:3px;background:<?= in_array($m,$plantMonths) ? 'var(--color-primary)' : 'var(--color-bg)' ?>;color:<?= in_array($m,$plantMonths) ? '#fff' : 'var(--color-text-muted)' ?>;border:1px solid var(--color-border)"><?= $monthNames[$m-1] ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:8px;margin-top:var(--spacing-2);align-items:center">
            <a href="<?= url('/seeds/' . (int)$seed['id']) ?>" class="btn btn-ghost btn-sm" style="flex:1;text-align:center">View</a>
            <a href="<?= url('/seeds/' . (int)$seed['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">Edit</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
