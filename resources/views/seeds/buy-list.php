<?php
$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$currentMonth = (int)date('n');
$typeEmoji = ['vegetable'=>'🥦','herb'=>'🌿','fruit'=>'🍓','flower'=>'🌸','other'=>'🌾'];
?>
<div class="page-header">
    <h1 class="page-title">🛒 Buy List</h1>
    <a href="<?= url('/seeds') ?>" class="btn btn-ghost">&larr; Catalog</a>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (empty($seeds)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:var(--spacing-6)">
        <p style="font-size:1.5rem;margin-bottom:var(--spacing-2)">✅</p>
        <p class="text-muted">Nothing to buy — all seeds are in stock.</p>
        <a href="<?= url('/seeds') ?>" class="btn btn-primary" style="margin-top:var(--spacing-3)">View Catalog</a>
    </div>
</div>
<?php else: ?>
<p class="text-muted" style="margin-bottom:var(--spacing-3)">
    <?= count($seeds) ?> seed<?= count($seeds) !== 1 ? 's' : '' ?> marked as out of stock.
    Click <strong>Bought</strong> when you've restocked.
</p>
<div style="display:flex;flex-direction:column;gap:var(--spacing-3)">
<?php foreach ($seeds as $s):
    $plantMonths = !empty($s['planting_months']) ? json_decode($s['planting_months'], true) : [];
    // Urgency: sowing window includes current or next 2 months
    $urgentMonths = array_map(fn($d) => (($currentMonth - 1 + $d) % 12) + 1, [0, 1, 2]);
    $urgent = !empty(array_intersect($plantMonths, $urgentMonths));
?>
<div class="card" style="border-left:4px solid <?= $urgent ? '#dc3545' : 'var(--color-border)' ?>">
    <div class="card-body" style="display:flex;align-items:flex-start;gap:var(--spacing-3);flex-wrap:wrap">
        <div style="flex:1;min-width:180px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <span style="font-size:1.1rem"><?= $typeEmoji[$s['type']] ?? '🌾' ?></span>
                <a href="<?= url('/seeds/' . (int)$s['id']) ?>" style="font-weight:600;font-size:1rem;color:var(--color-text);text-decoration:none"><?= e($s['name']) ?></a>
                <?php if ($s['variety']): ?>
                <span class="text-muted text-sm"><?= e($s['variety']) ?></span>
                <?php endif; ?>
                <?php if ($urgent): ?>
                <span style="background:#dc3545;color:#fff;font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:10px">SOW NOW</span>
                <?php endif; ?>
            </div>

            <?php if ($plantMonths): ?>
            <div style="display:flex;gap:3px;flex-wrap:wrap;margin-top:4px">
                <?php foreach (range(1,12) as $m):
                    $isPlant   = in_array($m, $plantMonths);
                    $isCurrent = ($m === $currentMonth);
                    $bg = $isPlant ? ($isCurrent ? '#dc3545' : 'var(--color-primary)') : 'var(--color-bg)';
                ?>
                <div style="padding:2px 5px;background:<?= $bg ?>;color:<?= $isPlant ? '#fff' : 'var(--color-text-muted)' ?>;border-radius:3px;font-size:0.62rem;font-weight:600;border:1px solid var(--color-border)"><?= $monthNames[$m-1] ?></div>
                <?php endforeach; ?>
            </div>
            <p class="text-muted text-sm" style="margin:4px 0 0">Sow months</p>
            <?php else: ?>
            <p class="text-muted text-sm" style="margin-top:4px">No planting calendar set</p>
            <?php endif; ?>
        </div>

        <div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
            <form method="POST" action="<?= url('/seeds/' . (int)$s['id'] . '/mark-bought') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="btn btn-primary btn-sm">✓ Bought</button>
            </form>
            <form method="POST" action="<?= url('/seeds/' . (int)$s['id'] . '/toggle-restock') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="btn btn-ghost btn-sm" onclick="return confirm('Remove from buy list?')">Remove</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
