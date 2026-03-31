<?php $layout = 'installer'; ?>
<div class="installer-card">
    <h2>Step 2: Land Identity</h2>
    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <form method="POST" action="/install/step/3" class="form">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

        <div class="form-group">
            <label class="form-label">Land Name</label>
            <input type="text" name="land_name" class="form-input" value="<?= e(getFlash('old')['land_name'] ?? '') ?>" required placeholder="My Farm">
        </div>

        <div class="form-group">
            <label class="form-label">Timezone</label>
            <select name="timezone" class="form-input">
                <?php foreach (\DateTimeZone::listIdentifiers() as $tz): ?>
                <option value="<?= e($tz) ?>" <?= ($tz === 'Europe/Rome') ? 'selected' : '' ?>><?= e($tz) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Language</label>
                <select name="language" class="form-input">
                    <option value="en" selected>English</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-input">
                    <option value="EUR" selected>EUR</option>
                    <option value="USD">USD</option>
                    <option value="GBP">GBP</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Continue &rarr;</button>
    </form>
</div>
