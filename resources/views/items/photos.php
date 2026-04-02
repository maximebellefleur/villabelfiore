<?php
$photoSlots = [
    'identification_photo'  => ['label' => 'ID Photo',    'icon' => '🪪'],
    'yearly_refresh_north'  => ['label' => 'North',       'icon' => '⬆️'],
    'yearly_refresh_south'  => ['label' => 'South',       'icon' => '⬇️'],
    'yearly_refresh_east'   => ['label' => 'East',        'icon' => '➡️'],
    'yearly_refresh_west'   => ['label' => 'West',        'icon' => '⬅️'],
    'harvest_photo'         => ['label' => 'Harvest',     'icon' => '🌾'],
    'general_attachment'    => ['label' => 'General',     'icon' => '📎'],
];
?>
<div class="page-header">
    <h1 class="page-title">📷 Photos <span class="badge badge-type"><?= e($item['name']) ?></span></h1>
    <a href="<?= url('/items/' . (int)$item['id']) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="photo-grid">
<?php foreach ($photoSlots as $catKey => $slot): ?>
<?php $att = $byCategory[$catKey] ?? null; ?>
<div class="photo-slot">
    <div class="photo-slot-header">
        <span class="photo-slot-icon"><?= $slot['icon'] ?></span>
        <span class="photo-slot-label"><?= e($slot['label']) ?></span>
    </div>

    <?php if ($att && str_starts_with($att['mime_type'], 'image/')): ?>
    <a href="<?= url('/attachments/' . (int)$att['id'] . '/download') ?>" target="_blank" class="photo-slot-img-wrap">
        <img src="<?= url('/attachments/' . (int)$att['id'] . '/download') ?>"
             class="photo-slot-img" loading="lazy" alt="<?= e($slot['label']) ?>">
        <div class="photo-slot-overlay">🔍 View full</div>
    </a>
    <div class="photo-slot-actions">
        <form method="POST" action="<?= url('/attachments/' . (int)$att['id'] . '/trash') ?>" style="display:inline">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <button class="btn btn-sm btn-danger" onclick="return confirm('Remove this photo?')">🗑 Remove</button>
        </form>
    </div>
    <?php elseif ($att): ?>
    <div class="photo-slot-empty">
        <span>📎</span>
        <a href="<?= url('/attachments/' . (int)$att['id'] . '/download') ?>" class="attachment-name">
            <?= e($att['original_filename']) ?>
        </a>
    </div>
    <?php else: ?>
    <div class="photo-slot-empty">
        <span class="photo-slot-placeholder">No photo yet</span>
    </div>
    <?php endif; ?>

    <!-- Upload form for this slot -->
    <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/attachments') ?>"
          enctype="multipart/form-data" class="photo-slot-upload">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
        <input type="hidden" name="category" value="<?= e($catKey) ?>">
        <label class="photo-upload-label">
            <input type="file" name="file" accept="image/*" class="photo-upload-input" onchange="this.form.submit()">
            <?= $att ? '🔄 Replace' : '📷 Upload' ?>
        </label>
    </form>
</div>
<?php endforeach; ?>
</div>

<style>
.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: var(--spacing-4);
    margin-top: var(--spacing-4);
}
.photo-slot {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.photo-slot-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-3);
    background: var(--color-bg);
    border-bottom: 1px solid var(--color-border);
    font-weight: 600;
    font-size: 0.85rem;
}
.photo-slot-icon { font-size: 1.1rem; }
.photo-slot-img-wrap {
    display: block;
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}
.photo-slot-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: opacity .2s;
}
.photo-slot-img-wrap:hover .photo-slot-img { opacity: .7; }
.photo-slot-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    background: rgba(0,0,0,.3);
    color: #fff;
    font-size: .85rem;
    font-weight: 600;
    transition: opacity .2s;
}
.photo-slot-img-wrap:hover .photo-slot-overlay { opacity: 1; }
.photo-slot-empty {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--color-text-muted);
    font-size: .8rem;
    gap: var(--spacing-2);
    background: var(--color-bg);
}
.photo-slot-placeholder { font-size: .75rem; }
.photo-slot-actions {
    padding: var(--spacing-2) var(--spacing-3);
    display: flex;
    justify-content: flex-end;
    border-top: 1px solid var(--color-border);
}
.photo-slot-upload {
    padding: var(--spacing-2) var(--spacing-3);
    border-top: 1px solid var(--color-border);
}
.photo-upload-label {
    display: block;
    text-align: center;
    padding: var(--spacing-2);
    background: var(--color-primary);
    color: #fff;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: .8rem;
    font-weight: 600;
    transition: opacity .15s;
}
.photo-upload-label:hover { opacity: .85; }
.photo-upload-input { display: none; }
@media (max-width: 640px) {
    .photo-grid { grid-template-columns: repeat(2, 1fr); gap: var(--spacing-3); }
}
</style>
