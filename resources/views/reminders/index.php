<div class="page-header">
    <h1 class="page-title">Reminders</h1>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (!empty($overdue)): ?>
<section class="section">
    <h2 class="section-title text-danger">Overdue (<?= count($overdue) ?>)</h2>
    <?php foreach ($overdue as $r): ?>
    <div class="card card--warning">
        <div class="card-body">
            <strong><?= e($r['title']) ?></strong>
            <span class="text-muted text-sm"><?= e(date('d M Y H:i', strtotime($r['due_at']))) ?></span>
            <?php if ($r['item_name']): ?><a href="<?= url('/items/' . ((int)$r['item_id'])) ?>" class="link-small"><?= e($r['item_name']) ?></a><?php endif; ?>
        </div>
        <div class="card-actions">
            <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/complete') ?>" style="display:inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button class="btn btn-sm btn-success">Done</button>
            </form>
            <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/dismiss') ?>" style="display:inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button class="btn btn-sm btn-secondary">Dismiss</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="section">
    <h2 class="section-title">Upcoming</h2>
    <?php if (empty($upcoming)): ?>
    <p class="text-muted">No upcoming reminders.</p>
    <?php else: ?>
    <?php foreach ($upcoming as $r): ?>
    <div class="card">
        <div class="card-body">
            <strong><?= e($r['title']) ?></strong>
            <span class="text-muted text-sm"><?= e(date('d M Y H:i', strtotime($r['due_at']))) ?></span>
            <?php if ($r['item_name']): ?><a href="<?= url('/items/' . ((int)$r['item_id'])) ?>" class="link-small"><?= e($r['item_name']) ?></a><?php endif; ?>
        </div>
        <div class="card-actions">
            <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/complete') ?>" style="display:inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button class="btn btn-sm btn-success">Done</button>
            </form>
            <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/dismiss') ?>" style="display:inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button class="btn btn-sm btn-secondary">Dismiss</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</section>

<div class="card">
    <div class="card-body">
        <h3>New Reminder</h3>
        <form method="POST" action="<?= url('/reminders') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Due At</label>
                <input type="datetime-local" name="due_at" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Reminder</button>
        </form>
    </div>
</div>
