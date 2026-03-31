<div class="page-header">
    <h1 class="page-title">Action Log</h1>
    <a href="<?= url('/items/' . ((int)$item_id)) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php if (empty($log)): ?>
<p class="text-muted">No actions logged yet.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Date</th><th>Action</th><th>Description</th></tr></thead>
    <tbody>
        <?php foreach ($log as $entry): ?>
        <tr>
            <td class="text-sm"><?= e(date('d M Y H:i', strtotime($entry['performed_at']))) ?></td>
            <td><span class="badge"><?= e($entry['action_label']) ?></span></td>
            <td><?= e($entry['description']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
