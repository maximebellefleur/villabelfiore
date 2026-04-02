<?php
$typeEmoji = [
    'olive_tree'  => '🫒', 'tree' => '🌳', 'vine' => '🍇',
    'almond_tree' => '🌰', 'garden' => '🌿', 'zone' => '🛖',
    'orchard'     => '🏕', 'bed' => '🌱', 'line' => '〰️',
    'prep_zone'   => '🟫', 'mobile_coop' => '🐓',
    'building'    => '🏠', 'water_point' => '💧',
];
$typeColor = [
    'olive_tree'  => '#2d6a4f', 'almond_tree' => '#92400e', 'vine' => '#6d28d9',
    'tree'        => '#166534', 'garden' => '#0369a1', 'bed' => '#0369a1',
    'orchard'     => '#c2410c', 'zone' => '#4338ca', 'prep_zone' => '#b45309',
    'water_point' => '#0284c7', 'mobile_coop' => '#991b1b',
    'building'    => '#374151', 'line' => '#1d4ed8',
];
?>
<div class="items-page">

<!-- Header -->
<div class="items-header">
    <div>
        <h1 class="items-title">My Items</h1>
        <p class="items-subtitle"><?= $total ?> item<?= $total !== 1 ? 's' : '' ?> on this land</p>
    </div>
    <a href="<?= url('/items/create') ?>" class="btn btn-primary btn-lg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Item
    </a>
</div>

<!-- Filter bar -->
<form method="GET" action="<?= url('/items') ?>" class="items-filter">
    <div class="items-filter-search">
        <svg class="items-filter-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="search" class="items-filter-input" placeholder="Search items…"
               value="<?= e($filters['search']) ?>">
    </div>
    <select name="type" class="items-filter-select">
        <option value="">All types</option>
        <?php foreach ($itemTypes as $typeKey => $typeCfg): ?>
        <option value="<?= e($typeKey) ?>" <?= ($filters['type'] === $typeKey) ? 'selected' : '' ?>>
            <?= ($typeEmoji[$typeKey] ?? '') . ' ' . e($typeCfg['label']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="items-filter-select">
        <option value="active"   <?= ($filters['status'] === 'active')   ? 'selected' : '' ?>>Active</option>
        <option value="archived" <?= ($filters['status'] === 'archived') ? 'selected' : '' ?>>Archived</option>
        <option value="trashed"  <?= ($filters['status'] === 'trashed')  ? 'selected' : '' ?>>Trashed</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($filters['search'] || $filters['type'] || $filters['status'] !== 'active'): ?>
    <a href="<?= url('/items') ?>" class="btn btn-ghost btn-sm">✕ Clear</a>
    <?php endif; ?>
</form>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Items grid -->
<?php if (empty($items)): ?>
<div class="items-empty">
    <div class="items-empty-icon">🌱</div>
    <h2 class="items-empty-title">Nothing here yet</h2>
    <p class="items-empty-text">Add your first item to start mapping your land.</p>
    <a href="<?= url('/items/create') ?>" class="btn btn-primary btn-lg" style="margin-top:var(--spacing-4)">
        + Add First Item
    </a>
</div>
<?php else: ?>

<div class="items-grid">
    <?php foreach ($items as $item):
        $emoji = $typeEmoji[$item['type']] ?? '📦';
        $color = $typeColor[$item['type']] ?? '#2d6a4f';
        $label = ucwords(str_replace('_', ' ', $item['type']));
        $hasGps = !empty($item['gps_lat']) && !empty($item['gps_lng']);
    ?>
    <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="item-card <?= $item['status'] !== 'active' ? 'item-card--inactive' : '' ?>">
        <!-- Color accent top strip -->
        <div class="item-card-strip" style="background:<?= $color ?>"></div>

        <!-- Emoji badge -->
        <div class="item-card-top">
            <div class="item-card-emoji" style="background:<?= $color ?>1a;color:<?= $color ?>">
                <?= $emoji ?>
            </div>
            <div class="item-card-badges">
                <?php if ($item['status'] !== 'active'): ?>
                <span class="item-card-status"><?= $item['status'] ?></span>
                <?php endif; ?>
                <?php if ($hasGps): ?>
                <span class="item-card-gps-dot" title="Has GPS location">📍</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Name + type -->
        <div class="item-card-body">
            <div class="item-card-name"><?= e($item['name']) ?></div>
            <div class="item-card-type" style="color:<?= $color ?>"><?= e($label) ?></div>
            <?php if ($hasGps): ?>
            <div class="item-card-coords">
                <?= number_format((float)$item['gps_lat'], 4) ?>, <?= number_format((float)$item['gps_lng'], 4) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer actions -->
        <div class="item-card-footer">
            <span class="item-card-action">View →</span>
            <div class="item-card-quick" onclick="event.preventDefault()">
                <a href="<?= url('/items/' . (int)$item['id'] . '/photos') ?>" class="item-card-quick-btn" title="Photos" onclick="event.stopPropagation()">📷</a>
                <a href="<?= url('/items/' . (int)$item['id'] . '/edit') ?>" class="item-card-quick-btn" title="Edit" onclick="event.stopPropagation()">✏️</a>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($lastPage > 1): ?>
<div class="pagination" style="margin-top:var(--spacing-6)">
    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
    <a href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"
       class="page-btn <?= ($p === $page) ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>

</div>

<style>
/* =========================================================
   Items Page
   ========================================================= */
.items-page { animation: fadeUp 0.3s ease-out; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }

.items-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-5);
    flex-wrap: wrap;
}
.items-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--color-text);
    margin: 0;
    letter-spacing: -0.03em;
    line-height: 1;
}
.items-subtitle {
    font-size: 0.85rem;
    color: var(--color-text-muted);
    margin: 4px 0 0;
}

/* Filter */
.items-filter {
    display: flex;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-5);
    flex-wrap: wrap;
    align-items: center;
}
.items-filter-search {
    position: relative;
    flex: 1;
    min-width: 180px;
}
.items-filter-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-text-muted);
    pointer-events: none;
}
.items-filter-input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-pill);
    font-size: 0.9rem;
    font-family: inherit;
    background: var(--color-surface-raised);
    transition: border-color 0.15s, box-shadow 0.15s;
}
.items-filter-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(45,106,79,0.12);
}
.items-filter-select {
    padding: 10px 14px;
    border: 1.5px solid var(--color-border);
    border-radius: var(--radius-pill);
    font-size: 0.85rem;
    font-family: inherit;
    background: var(--color-surface-raised);
    color: var(--color-text);
    cursor: pointer;
    transition: border-color 0.15s;
}
.items-filter-select:focus { outline: none; border-color: var(--color-primary); }

/* Empty state */
.items-empty {
    text-align: center;
    padding: var(--spacing-10) var(--spacing-5);
}
.items-empty-icon  { font-size: 4rem; margin-bottom: var(--spacing-4); }
.items-empty-title { font-size: 1.4rem; font-weight: 800; margin-bottom: var(--spacing-2); }
.items-empty-text  { color: var(--color-text-muted); }

/* Items grid — 2 cols on mobile, 3-4 on desktop */
.items-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-3);
}
@media (min-width: 500px)  { .items-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 720px)  { .items-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1000px) { .items-grid { grid-template-columns: repeat(4, 1fr); } }

/* Item Card */
.item-card {
    background: var(--color-surface-raised);
    border-radius: 18px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07), 0 1px 3px rgba(0,0,0,0.05);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    position: relative;
}
.item-card:hover {
    transform: translateY(-4px) scale(1.015);
    box-shadow: 0 8px 32px rgba(0,0,0,0.13), 0 2px 8px rgba(0,0,0,0.07);
    text-decoration: none;
    color: inherit;
}
.item-card:active { transform: scale(0.98); }
.item-card--inactive { opacity: 0.65; }

/* Top colored strip */
.item-card-strip {
    height: 4px;
    flex-shrink: 0;
}

/* Card top row */
.item-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: var(--spacing-3) var(--spacing-3) 0;
}
.item-card-emoji {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    font-size: 1.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.item-card-badges {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}
.item-card-status {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(0,0,0,0.06);
    padding: 2px 7px;
    border-radius: var(--radius-pill);
    color: var(--color-text-muted);
}
.item-card-gps-dot { font-size: 0.75rem; opacity: 0.7; }

/* Card body */
.item-card-body {
    padding: var(--spacing-2) var(--spacing-3);
    flex: 1;
}
.item-card-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 2px;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.item-card-type {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
}
.item-card-coords {
    font-size: 0.65rem;
    color: var(--color-text-subtle);
    font-variant-numeric: tabular-nums;
}

/* Card footer */
.item-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--spacing-2) var(--spacing-3);
    border-top: 1px solid var(--color-border);
    background: var(--color-surface);
}
.item-card-action {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--color-primary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.item-card-quick {
    display: flex;
    gap: 4px;
}
.item-card-quick-btn {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: var(--color-surface-raised);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    text-decoration: none;
    transition: background 0.15s;
}
.item-card-quick-btn:hover {
    background: var(--color-primary-soft);
    text-decoration: none;
}
</style>
