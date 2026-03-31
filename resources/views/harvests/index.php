<div class="page-header">
    <h1 class="page-title">Harvests — <?= e($item['name']) ?></h1>
    <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
<form method="POST" action="<?= url('/items/' . ((int)$item['id']) . '/harvests') ?>" class="form-inline">
    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
    <input type="number" step="0.001" name="quantity" class="form-input form-input--sm" placeholder="Quantity" required>
    <input type="text" name="unit" class="form-input form-input--sm" placeholder="Unit" required>
    <input type="datetime-local" name="recorded_at" class="form-input form-input--sm" required>
    <input type="text" name="quality_grade" class="form-input form-input--sm" placeholder="Grade (optional)">
    <button type="submit" class="btn btn-primary">Record Harvest</button>
</form>
<?php if (empty($harvests)): ?>
<p class="text-muted">No harvests recorded.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Date</th><th>Quantity</th><th>Unit</th><th>Grade</th><th>Notes</th></tr></thead>
    <tbody>
        <?php foreach ($harvests as $h): ?>
        <tr>
            <td><?= e(date('d M Y', strtotime($h['recorded_at']))) ?></td>
            <td><?= e($h['quantity']) ?></td>
            <td><?= e($h['unit']) ?></td>
            <td><?= e($h['quality_grade'] ?? '—') ?></td>
            <td><?= e($h['notes'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
