<?php
$typeEmoji = [
    'olive_tree' => '🫒', 'almond_tree' => '🌰', 'vine' => '🍇',
    'tree' => '🌳', 'garden' => '🌿', 'bed' => '🌱',
];
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-4)">
    <div>
        <h1 style="font-size:1.5rem;font-weight:900;margin:0">💧 Irrigation Plans</h1>
        <p style="font-size:.82rem;color:var(--color-text-muted);margin:4px 0 0"><?= count($plans) ?> active plan<?= count($plans) !== 1 ? 's' : '' ?></p>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if ($plans): ?>
<section style="margin-bottom:var(--spacing-5)">
    <h2 style="font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted);margin-bottom:var(--spacing-3)">Active Plans</h2>
    <div style="display:flex;flex-direction:column;gap:var(--spacing-2)">
        <?php foreach ($plans as $plan): ?>
        <?php $em = $typeEmoji[$plan['item_type']] ?? '🌿'; ?>
        <a href="<?= url('/items/' . (int)$plan['item_id']) ?>" class="card" style="text-decoration:none;display:block;padding:var(--spacing-3) var(--spacing-4)">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--spacing-3)">
                <div style="display:flex;align-items:center;gap:10px;min-width:0">
                    <span style="font-size:1.4rem;flex-shrink:0"><?= $em ?></span>
                    <div style="min-width:0">
                        <div style="font-weight:700;font-size:.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($plan['item_name']) ?></div>
                        <div style="font-size:.76rem;color:var(--color-text-muted);margin-top:2px">
                            <?= e(\App\Controllers\IrrigationController::intervalLabel($plan['interval_type'])) ?>
                            · <?= (int)$plan['duration_months'] ?> months
                            · starts <?= date('d M Y', strtotime($plan['start_date'])) ?>
                        </div>
                    </div>
                </div>
                <span style="font-size:1rem;color:var(--color-text-muted);flex-shrink:0">›</span>
            </div>
            <?php if ($plan['notes']): ?>
            <p style="font-size:.78rem;color:var(--color-text-muted);margin:6px 0 0;padding-left:34px"><?= e($plan['notes']) ?></p>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($withoutPlan): ?>
<section>
    <h2 style="font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted);margin-bottom:var(--spacing-3)">No Plan Yet</h2>
    <div style="display:flex;flex-direction:column;gap:2px">
        <?php foreach ($withoutPlan as $item): ?>
        <?php $em = $typeEmoji[$item['type']] ?? '🌿'; ?>
        <a href="<?= url('/items/' . (int)$item['id']) ?>" style="display:flex;align-items:center;gap:10px;padding:10px var(--spacing-3);text-decoration:none;color:var(--color-text);border-radius:var(--radius-lg);transition:background .12s">
            <span style="font-size:1.1rem"><?= $em ?></span>
            <span style="font-size:.88rem"><?= e($item['name']) ?></span>
            <span style="margin-left:auto;font-size:.76rem;color:var(--color-primary);font-weight:600">+ Add plan</span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!$plans && !$withoutPlan): ?>
<div style="text-align:center;padding:var(--spacing-6) 0;color:var(--color-text-muted)">
    <div style="font-size:3rem;margin-bottom:var(--spacing-3)">💧</div>
    <p>No irrigatable items found. Add trees, vines, gardens, or beds to get started.</p>
</div>
<?php endif; ?>
