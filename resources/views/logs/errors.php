<div class="page-header">
    <h1 class="page-title">Error Logs</h1>
</div>
<?php if (empty($logs)): ?>
<p class="text-muted">No errors logged.</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Date</th><th>Severity</th><th>Module</th><th>Message</th></tr></thead>
    <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td class="text-sm"><?= e(date('d M Y H:i', strtotime($log['created_at']))) ?></td>
            <td><span class="badge badge-severity-<?= e($log['severity']) ?>"><?= e(strtoupper($log['severity'])) ?></span></td>
            <td class="text-sm"><?= e($log['module']) ?></td>
            <td><a href="<?= url('/logs/errors/' . ((int)$log['id'])) ?>"><?= e(mb_strimwidth($log['message'], 0, 80, '…')) ?></a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
