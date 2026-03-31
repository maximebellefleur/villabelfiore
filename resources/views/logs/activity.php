<div class="page-header">
    <h1 class="page-title">Activity Log</h1>
</div>
<?php if (empty($logs)): ?>
<p class="text-muted">No activity recorded yet.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Date</th><th>Action</th><th>Item</th><th>Description</th></tr></thead>
    <tbody>
        <?php foreach ($logs as $a): ?>
        <tr>
            <td class="text-sm text-muted"><?= e(date('d M Y H:i', strtotime($a['performed_at']))) ?></td>
            <td><span class="badge"><?= e($a['action_label']) ?></span></td>
            <td>
                <?php if ($a['item_id']): ?>
                <a href="<?= url('/items/' . ((int)$a['item_id'])) ?>"><?= e($a['item_name'] ?? 'Item #'.$a['item_id']) ?></a>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= e($a['description']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
