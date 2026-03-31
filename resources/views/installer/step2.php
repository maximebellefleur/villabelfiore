<?php $layout = 'installer'; ?>
<div class="installer-card">
    <h2>Step 1: Database Setup</h2>
    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <form method="POST" action="/install/step/2" class="form">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">DB Host</label>
                <input type="text" name="db_host" class="form-input" value="<?= e(getFlash('old')['db_host'] ?? 'localhost') ?>" required>
            </div>
            <div class="form-group form-group--sm">
                <label class="form-label">Port</label>
                <input type="text" name="db_port" class="form-input" value="<?= e(getFlash('old')['db_port'] ?? '3306') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Database Name</label>
            <input type="text" name="db_name" class="form-input" value="<?= e(getFlash('old')['db_name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="db_user" class="form-input" value="<?= e(getFlash('old')['db_user'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="db_pass" class="form-input" autocomplete="off">
        </div>

        <button type="submit" class="btn btn-primary">Connect &amp; Import Schema &rarr;</button>
    </form>
</div>
