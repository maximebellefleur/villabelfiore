<div class="page-header">
    <h1 class="page-title">Settings</h1>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="settings-wrap">

    <!-- Tab nav — horizontal scrollable pill strip -->
    <nav class="settings-tab-nav" role="tablist">
        <a href="<?= url('/settings') ?>"               class="settings-tab settings-tab--active" role="tab">General</a>
        <a href="<?= url('/settings/harvest') ?>"      class="settings-tab" role="tab">🌾 Harvest</a>
        <a href="<?= url('/settings/storage') ?>"      class="settings-tab" role="tab">Storage</a>
        <a href="<?= url('/settings/action-types') ?>" class="settings-tab" role="tab">Action Types</a>
        <a href="<?= url('/settings/weather') ?>"      class="settings-tab" role="tab">🌤️ Weather</a>
        <a href="<?= url('/settings/calendar') ?>"     class="settings-tab" role="tab">📅 Calendar</a>
        <a href="<?= url('/settings/pwa') ?>"          class="settings-tab" role="tab">📱 PWA</a>
        <a href="<?= url('/logs/errors') ?>"           class="settings-tab" role="tab">Error Logs</a>
        <a href="<?= url('/settings/upcoming') ?>"     class="settings-tab" role="tab">🗺 Roadmap</a>
        <a href="<?= url('/settings/upgrade') ?>"      class="settings-tab" role="tab">⬆️ Upgrade</a>
    </nav>

    <!-- General settings panel -->
    <div class="settings-panel">
        <form method="POST" action="<?= url('/settings/update') ?>" class="settings-form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

            <div class="settings-group">
                <div class="settings-group-title">Land</div>

                <div class="settings-field">
                    <label class="settings-label">Land Name</label>
                    <input type="text" name="app_name" class="settings-input"
                           value="<?= e($settings['app.name'] ?? '') ?>"
                           placeholder="e.g. Villa Belfiore">
                </div>

                <div class="settings-field">
                    <label class="settings-label">Your Name</label>
                    <p class="settings-hint">Shown in the dashboard welcome greeting — "Ciao, [Name]!"</p>
                    <input type="text" name="app_owner_name" class="settings-input"
                           value="<?= e($settings['app.owner_name'] ?? '') ?>"
                           placeholder="e.g. Max">
                </div>

                <div class="settings-field">
                    <label class="settings-label">Currency</label>
                    <select name="app_currency" class="settings-input">
                        <?php foreach (['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'AUD'] as $cur): ?>
                        <option value="<?= e($cur) ?>" <?= ($settings['app.currency'] ?? 'EUR') === $cur ? 'selected' : '' ?>><?= e($cur) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-field">
                    <label class="settings-label">Timezone</label>
                    <select name="app_timezone" class="settings-input">
                        <?php foreach (\DateTimeZone::listIdentifiers() as $tz): ?>
                        <option value="<?= e($tz) ?>" <?= ($settings['app.timezone'] ?? 'Europe/Rome') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="settings-group">
                <div class="settings-group-title">GPS & Photos</div>

                <div class="settings-field">
                    <label class="settings-label">GPS Accuracy Threshold (m)</label>
                    <p class="settings-hint">Minimum accuracy in metres before a GPS reading is accepted.</p>
                    <input type="number" name="gps_accuracy_threshold" class="settings-input settings-input--sm"
                           value="<?= e($settings['gps.accuracy_threshold'] ?? '20') ?>" min="1" max="500">
                </div>

                <div class="settings-field">
                    <label class="settings-label">Image Refresh Interval (days)</label>
                    <p class="settings-hint">How often to remind you to update directional photos.</p>
                    <input type="number" name="image_refresh_interval_days" class="settings-input settings-input--sm"
                           value="<?= e($settings['image.refresh_interval_days'] ?? '365') ?>" min="1">
                </div>
            </div>

            <div class="settings-group">
                <div class="settings-group-title">Reminders</div>

                <div class="settings-field">
                    <label class="settings-label">Default Reminder Lead (days)</label>
                    <p class="settings-hint">How many days before the due date to show a reminder.</p>
                    <input type="number" name="reminder_default_lead_days" class="settings-input settings-input--sm"
                           value="<?= e($settings['reminder.default_lead_days'] ?? '7') ?>" min="0">
                </div>
            </div>

            <div class="settings-group">
                <div class="settings-group-title">Dashboard Quote</div>

                <div class="settings-field">
                    <label class="settings-label">Quote API URL</label>
                    <p class="settings-hint">URL returning JSON with today's inspirational quote. Default: ZenQuotes (free, no key required). Response must be an array with <code>q</code> (text) and <code>a</code> (author) keys.</p>
                    <input type="url" name="quote_api_url" class="settings-input"
                           value="<?= e($settings['quote.api_url'] ?? '') ?>"
                           placeholder="https://zenquotes.io/api/today">
                </div>
            </div>

            <div class="settings-save-row">
                <button type="submit" class="btn btn-primary btn-lg">Save Settings</button>
            </div>
        </form>

        <!-- Logo upload -->
        <div class="settings-group" style="margin-top:var(--spacing-6)">
            <div class="settings-group-title">Nav Logo</div>
            <?php
            $_logoPreview = null;
            foreach (['png','jpg','webp','svg'] as $_le) {
                $_lf = PUBLIC_PATH . '/assets/images/logo-nav.' . $_le;
                if (file_exists($_lf)) { $_logoPreview = url('/assets/images/logo-nav.'.$_le).'?v='.filemtime($_lf); break; }
            }
            ?>
            <?php if ($_logoPreview): ?>
            <div style="margin-bottom:var(--spacing-3)">
                <p class="settings-hint" style="margin-bottom:var(--spacing-2)">Current logo:</p>
                <img src="<?= $_logoPreview ?>" alt="Nav logo" style="height:40px;max-width:160px;object-fit:contain;border:1px solid var(--color-border);border-radius:6px;padding:6px;background:#fff;">
            </div>
            <?php endif; ?>
            <form method="POST" action="<?= url('/settings/logo') ?>" enctype="multipart/form-data" class="settings-form">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <div class="settings-field">
                    <label class="settings-label">Upload Logo (PNG, JPG, WebP, or SVG)</label>
                    <p class="settings-hint">Replaces the "🌿 Rooted" text in the top nav. Recommended height: 32–40 px. Max width displayed: 120 px.</p>
                    <input type="file" name="logo_file" class="settings-input" accept="image/png,image/jpeg,image/webp,image/svg+xml" required>
                </div>
                <div class="settings-save-row">
                    <button type="submit" class="btn btn-primary">Upload Logo</button>
                </div>
            </form>
        </div>
    </div>

</div>

