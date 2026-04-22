<div class="page-header">
    <h1 class="page-title">Attachments — <?= e($item['name']) ?></h1>
    <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
<form method="POST" action="<?= url('/items/' . ((int)$item['id']) . '/attachments') ?>" enctype="multipart/form-data" class="form">
    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
    <input type="file" name="file" class="form-input" required>
    <select name="category" class="form-input">
        <option value="general_attachment">General</option>
        <option value="identification_photo">Identification Photo</option>
        <option value="yearly_refresh_north">North</option>
        <option value="yearly_refresh_south">South</option>
        <option value="yearly_refresh_east">East</option>
        <option value="yearly_refresh_west">West</option>
    </select>
    <button type="submit" class="btn btn-primary">Upload</button>
</form>
<?php if (empty($attachments)): ?>
<p class="text-muted">No attachments.</p>
<?php else: ?>
<div class="attachment-grid">
    <?php foreach ($attachments as $att): ?>
    <div class="attachment-card">
        <a href="<?= att_url((int)$att['id']) ?>"><?= e($att['original_filename']) ?></a>
        <span class="text-muted text-sm"><?= e($att['category']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
