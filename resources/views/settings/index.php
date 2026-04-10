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
    </div>

</div>

<style>
/* Settings page — clean stacked layout */
.settings-wrap {
    display: block;
    width: 100%;
}

/* Horizontal scrollable tab nav — NO wrapping */
.settings-tab-nav {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 6px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding: 0 0 var(--spacing-4);
    border-bottom: 2px solid var(--color-border);
    margin-bottom: var(--spacing-5);
}
.settings-tab-nav::-webkit-scrollbar { display: none; }

.settings-tab {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    flex-shrink: 0;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--color-text-muted);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    text-decoration: none;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.settings-tab:hover {
    background: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary);
    text-decoration: none;
}
.settings-tab--active {
    background: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary);
}

/* Settings panel — full width block */
.settings-panel {
    display: block;
    width: 100%;
}

/* Form */
.settings-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-5);
}

/* Group (visual section) */
.settings-group {
    background: var(--color-surface-raised);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04);
}
.settings-group-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-text-muted);
    padding: var(--spacing-3) var(--spacing-4) var(--spacing-2);
    border-bottom: 1px solid var(--color-border);
    background: var(--color-surface);
}

/* Individual field row */
.settings-field {
    padding: var(--spacing-3) var(--spacing-4);
    border-bottom: 1px solid var(--color-border);
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.settings-field:last-child { border-bottom: none; }

.settings-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--color-text);
}
.settings-hint {
    font-size: 0.78rem;
    color: var(--color-text-muted);
    margin: 0;
    line-height: 1.4;
}
.settings-input {
    width: 100%;
    padding: 10px var(--spacing-3);
    border: 1.5px solid var(--color-border);
    border-radius: 10px;
    font-size: 0.95rem;
    font-family: inherit;
    background: var(--color-surface);
    color: var(--color-text);
    transition: border-color 0.15s, box-shadow 0.15s;
    margin-top: 4px;
}
.settings-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(45,90,39,0.12);
    background: #fff;
}
.settings-input--sm { max-width: 140px; }

.settings-save-row {
    padding: var(--spacing-2) 0 var(--spacing-6);
}
.btn-lg {
    padding: 14px 32px;
    font-size: 1rem;
    border-radius: 999px;
    font-weight: 600;
}
</style>
