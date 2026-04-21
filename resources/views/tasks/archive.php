<?php
$categoryIcons = [
    'Building'=>'🏗','Planting'=>'🌱','Pruning'=>'✂️','Harvest'=>'🌾',
    'Maintenance'=>'🔧','Watering'=>'💧','Cleaning'=>'🧹','Shopping'=>'🛒','Other'=>'📌',
];
$csrfToken = \App\Support\CSRF::getToken();
?>
<style>
.tasks-page { max-width: 680px; margin: 0 auto; }
.task-row { display:flex;align-items:flex-start;gap:10px;padding:11px 12px;background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius);margin-bottom:2px;opacity:.65; }
.task-title { font-size:.9rem;font-weight:500;color:var(--color-text);text-decoration:line-through; }
.task-category { font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--color-border);color:var(--color-text-muted); }
.task-act-btn { background:none;border:none;cursor:pointer;padding:4px 6px;border-radius:var(--radius);color:var(--color-text-muted);font-size:.8rem;line-height:1;transition:background .12s; }
.task-act-btn:hover { background:var(--color-border); }
</style>

<div class="tasks-page">
<div style="display:flex;align-items:center;gap:12px;margin-bottom:var(--spacing-4)">
    <a href="<?= url('/tasks') ?>" class="btn btn-ghost btn-sm">← Tasks</a>
    <h1 style="font-size:1.3rem;font-weight:800;margin:0">📦 Archive</h1>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (empty($archived)): ?>
<div style="text-align:center;padding:var(--spacing-6);color:var(--color-text-muted);background:var(--color-surface-raised);border:1px dashed var(--color-border);border-radius:var(--radius-lg)">
    <div style="font-size:2rem;margin-bottom:8px">📦</div>
    No archived tasks yet.
</div>
<?php else: ?>
<div style="font-size:.75rem;color:var(--color-text-muted);margin-bottom:var(--spacing-3)"><?= count($archived) ?> archived task<?= count($archived) !== 1 ? 's' : '' ?></div>
<div style="display:flex;flex-direction:column;gap:2px">
    <?php foreach ($archived as $t):
        $catIcon = $categoryIcons[$t['category']] ?? '📌';
    ?>
    <div class="task-row">
        <span style="font-size:.9rem;color:var(--color-text-muted);flex-shrink:0;margin-top:2px"><?= $t['is_done'] ? '✓' : '○' ?></span>
        <div style="flex:1;min-width:0">
            <div class="task-title"><?= e($t['title']) ?></div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px">
                <?php if (!empty($t['category'])): ?>
                <span class="task-category"><?= e($catIcon . ' ' . $t['category']) ?></span>
                <?php endif; ?>
                <span style="font-size:.65rem;color:var(--color-text-muted)">Archived <?= e(date('d M Y', strtotime($t['updated_at']))) ?></span>
            </div>
        </div>
        <div style="display:flex;gap:4px;flex-shrink:0">
            <form method="POST" action="<?= url('/tasks/' . (int)$t['id'] . '/unarchive') ?>" style="display:inline">
                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="task-act-btn" title="Restore">↩</button>
            </form>
            <form method="POST" action="<?= url('/tasks/' . (int)$t['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('Delete permanently?')">
                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="task-act-btn" title="Delete" style="color:#dc3545">✕</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</div>
