<div class="page-header">
    <h1 class="page-title">Storage Settings</h1>
    <a href="/settings" class="btn btn-secondary">&larr; Settings</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
<div class="card">
    <div class="card-body">
        <p>Configured storage targets:</p>
        <?php if (empty($targets)): ?>
        <p class="text-muted">No storage targets configured. Using local filesystem defaults.</p>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Name</th><th>Driver</th><th>Default Live</th><th>Default Backup</th></tr></thead>
            <tbody>
                <?php foreach ($targets as $t): ?>
                <tr>
                    <td><?= e($t['name']) ?></td>
                    <td><?= e($t['driver']) ?></td>
                    <td><?= $t['is_default_live'] ? 'Yes' : 'No' ?></td>
                    <td><?= $t['is_default_backup'] ? 'Yes' : 'No' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
