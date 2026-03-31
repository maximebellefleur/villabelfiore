<div class="page-header">
    <h1 class="page-title">Settings</h1>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="tabs">
    <nav class="tab-nav">
        <button class="tab-btn tab-btn--active" data-tab="general">General</button>
        <button class="tab-btn" data-tab="storage"><a href="/settings/storage">Storage</a></button>
        <button class="tab-btn" data-tab="actions"><a href="/settings/action-types">Action Types</a></button>
        <button class="tab-btn" data-tab="logs"><a href="/logs/errors">Error Logs</a></button>
    </nav>

    <div class="tab-panel tab-panel--active" id="tab-general">
        <form method="POST" action="/settings/update" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

            <div class="form-group">
                <label class="form-label">Land Name</label>
                <input type="text" name="app_name" class="form-input" value="<?= e($settings['app.name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Currency</label>
                <select name="app_currency" class="form-input">
                    <?php foreach (['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'AUD'] as $cur): ?>
                    <option value="<?= e($cur) ?>" <?= ($settings['app.currency'] ?? 'EUR') === $cur ? 'selected' : '' ?>><?= e($cur) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Timezone</label>
                <select name="app_timezone" class="form-input">
                    <?php foreach (\DateTimeZone::listIdentifiers() as $tz): ?>
                    <option value="<?= e($tz) ?>" <?= ($settings['app.timezone'] ?? 'Europe/Rome') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">GPS Accuracy Threshold (m)</label>
                <input type="number" name="gps_accuracy_threshold" class="form-input" value="<?= e($settings['gps.accuracy_threshold'] ?? '20') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Image Refresh Interval (days)</label>
                <input type="number" name="image_refresh_interval_days" class="form-input" value="<?= e($settings['image.refresh_interval_days'] ?? '365') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Reminder Default Lead (days)</label>
                <input type="number" name="reminder_default_lead_days" class="form-input" value="<?= e($settings['reminder.default_lead_days'] ?? '7') ?>">
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>
