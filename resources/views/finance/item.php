<div class="page-header">
    <h1 class="page-title">Finance — <?= e($item['name']) ?></h1>
    <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
<form method="POST" action="<?= url('/finance') ?>" class="form">
    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
    <div class="form-row">
        <select name="entry_type" class="form-input"><option value="cost">Cost</option><option value="revenue">Revenue</option></select>
        <input type="text" name="category" class="form-input" placeholder="Category">
        <input type="text" name="label" class="form-input" placeholder="Label" required>
        <input type="number" step="0.01" name="amount" class="form-input" placeholder="Amount" required>
        <input type="date" name="entry_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
        <button type="submit" class="btn btn-primary">Add</button>
    </div>
</form>
<?php if (empty($entries)): ?>
<p class="text-muted">No finance entries for this item.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Label</th><th>Amount</th></tr></thead>
    <tbody>
        <?php foreach ($entries as $e): ?>
        <tr>
            <td><?= e($e['entry_date']) ?></td>
            <td><span class="badge badge-<?= $e['entry_type'] === 'revenue' ? 'success' : 'warning' ?>"><?= e($e['entry_type']) ?></span></td>
            <td><?= e($e['category']) ?></td>
            <td><?= e($e['label']) ?></td>
            <td><?= number_format((float)$e['amount'], 2) ?> <?= e($e['currency']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
