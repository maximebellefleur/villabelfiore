<?php $seed = []; ?>
<div class="page-header">
    <h1 class="page-title">🌱 Add Seed</h1>
    <a href="<?= url('/seeds') ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/seeds') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <?php include BASE_PATH . '/resources/views/seeds/_form.php'; ?>
            <div class="form-actions" style="margin-top:var(--spacing-4)">
                <button type="submit" class="btn btn-primary">Save Seed</button>
                <a href="<?= url('/seeds') ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
