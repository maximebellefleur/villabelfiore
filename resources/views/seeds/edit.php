<div class="page-header">
    <h1 class="page-title">Edit — <?= e($seed['name']) ?></h1>
    <a href="<?= url('/seeds/' . (int)$seed['id']) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/seeds/' . (int)$seed['id'] . '/update') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <?php include BASE_PATH . '/resources/views/seeds/_form.php'; ?>
            <div class="form-actions" style="margin-top:var(--spacing-4)">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?= url('/seeds/' . (int)$seed['id']) ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Delete -->
<div class="card" style="margin-top:var(--spacing-4);border-color:rgba(220,53,69,.3)">
    <div class="card-body">
        <p style="font-weight:600;margin:0 0 var(--spacing-2)">Delete this seed</p>
        <p class="text-muted text-sm" style="margin:0 0 var(--spacing-3)">All bed-row and family-need references will lose the link. This cannot be undone.</p>
        <form method="POST" action="<?= url('/seeds/' . (int)$seed['id'] . '/trash') ?>"
              onsubmit="return confirm('Delete this seed permanently?')">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <button type="submit" class="btn btn-danger">🗑 Delete Seed</button>
        </form>
    </div>
</div>
