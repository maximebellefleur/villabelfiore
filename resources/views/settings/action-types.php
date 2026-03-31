<div class="page-header">
    <h1 class="page-title">Action Types</h1>
    <a href="/settings" class="btn btn-secondary">&larr; Settings</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
<table class="table">
    <thead><tr><th>Key</th><th>Label</th><th>Scope</th><th>System</th><th>Active</th></tr></thead>
    <tbody>
        <?php foreach ($types as $t): ?>
        <tr>
            <td><code><?= e($t['action_key']) ?></code></td>
            <td><?= e($t['action_label']) ?></td>
            <td><?= e($t['scope_type'] ?? '—') ?></td>
            <td><?= $t['is_system'] ? 'Yes' : 'No' ?></td>
            <td><?= $t['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge">Inactive</span>' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
