<div class="page-header">
    <h1 class="page-title">Sync Conflicts</h1>
    <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">&larr; Dashboard</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
<?php if (empty($conflicts)): ?>
<p class="text-muted">No conflicts to resolve.</p>
<?php else: ?>
<?php foreach ($conflicts as $c): ?>
<div class="card card--warning">
    <div class="card-body">
        <p><strong><?= e($c['entity_type']) ?></strong> — <?= e($c['operation_type']) ?></p>
        <pre class="code-block"><?= e(json_encode(json_decode($c['payload_json'], true), JSON_PRETTY_PRINT)) ?></pre>
    </div>
    <div class="card-actions">
        <form method="POST" action="<?= url('/sync/conflicts/' . ((int)$c['id']) . '/resolve') ?>">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <button class="btn btn-primary btn-sm">Mark Resolved</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
