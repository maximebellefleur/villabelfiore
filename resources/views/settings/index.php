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

        <!-- Logo uploads -->
        <div class="settings-group" style="margin-top:var(--spacing-6)">
            <div class="settings-group-title">Logos</div>
            <p class="settings-hint" style="margin-bottom:var(--spacing-4)">
                Four logo slots: <strong>icon</strong> (square, used in the nav and mobile) and <strong>horizontal</strong> (wide text logo, used on desktop nav).
                Each in a <strong>light</strong> version (for light backgrounds) and a <strong>dark</strong> version (for dark/coloured backgrounds).
                Accepted formats: PNG, JPG, WebP, SVG. Upload each slot separately.
            </p>
            <?php
            $_logoSlots = [
                'icon-light'       => ['label' => 'Icon — Light',       'hint' => 'Square logo for light backgrounds. Shown in the nav bar.',          'bg' => '#ffffff', 'size' => '48px'],
                'icon-dark'        => ['label' => 'Icon — Dark',        'hint' => 'Square logo for dark/coloured backgrounds.',                          'bg' => '#1e1e1e', 'size' => '48px'],
                'horizontal-light' => ['label' => 'Horizontal — Light', 'hint' => 'Wide text logo for light backgrounds. Shown on desktop nav.',         'bg' => '#ffffff', 'size' => '36px'],
                'horizontal-dark'  => ['label' => 'Horizontal — Dark',  'hint' => 'Wide text logo for dark/coloured backgrounds.',                       'bg' => '#1e1e1e', 'size' => '36px'],
            ];
            foreach ($_logoSlots as $_slotKey => $_slotCfg):
                $_slotFile = null;
                foreach (['svg','png','webp','jpg'] as $_sle) {
                    $_sf = PUBLIC_PATH . '/assets/images/logo-' . $_slotKey . '.' . $_sle;
                    if (file_exists($_sf)) { $_slotFile = url('/assets/images/logo-'.$_slotKey.'.'.$_sle).'?v='.filemtime($_sf); break; }
                }
            ?>
            <div style="display:flex;align-items:flex-start;gap:var(--spacing-4);padding:var(--spacing-3) 0;border-bottom:1px solid var(--color-border);flex-wrap:wrap">
                <!-- Preview box -->
                <div style="flex-shrink:0;width:100px;height:60px;background:<?= $_slotCfg['bg'] ?>;border:1px solid var(--color-border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden">
                    <?php if ($_slotFile): ?>
                    <img src="<?= $_slotFile ?>" alt="<?= e($_slotCfg['label']) ?>" style="max-width:90px;max-height:52px;object-fit:contain">
                    <?php else: ?>
                    <span style="font-size:0.7rem;color:#aaa;text-align:center;padding:4px">No logo</span>
                    <?php endif; ?>
                </div>
                <!-- Upload form -->
                <div style="flex:1;min-width:200px">
                    <div style="font-weight:600;font-size:0.875rem;margin-bottom:2px"><?= e($_slotCfg['label']) ?></div>
                    <p class="settings-hint" style="margin-bottom:var(--spacing-2)"><?= e($_slotCfg['hint']) ?></p>
                    <form method="POST" action="<?= url('/settings/logo') ?>" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                        <input type="hidden" name="logo_slot" value="<?= $_slotKey ?>">
                        <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml" required style="font-size:0.85rem;flex:1;min-width:160px">
                        <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                        <?php if ($_slotFile): ?>
                        <form method="POST" action="<?= url('/settings/logo/delete') ?>" style="display:inline" onsubmit="return confirm('Remove this logo?')">
                            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                            <input type="hidden" name="logo_slot" value="<?= $_slotKey ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:#dc3545">✕ Remove</button>
                        </form>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- AI / Ollama settings -->
        <div class="settings-group" style="margin-top:var(--spacing-6)">
            <div class="settings-group-title">🤖 Local AI (Ollama)</div>
            <p class="settings-hint" style="margin-bottom:var(--spacing-3)">Used for photo-based seed identification. Requires <a href="https://ollama.com" target="_blank" rel="noopener">Ollama</a> running locally with a vision model.<br>
            Install a vision model: <code>ollama pull llava</code> or <code>ollama pull moondream</code></p>
            <form method="POST" action="<?= url('/settings/update') ?>" class="settings-form">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <div class="settings-field">
                    <label class="settings-label">Ollama Endpoint</label>
                    <p class="settings-hint">Base URL of your Ollama instance.</p>
                    <input type="url" name="ai_endpoint" class="settings-input"
                           value="<?= e($settings['ai.endpoint'] ?? 'http://localhost:11434') ?>"
                           placeholder="http://localhost:11434">
                </div>
                <div class="settings-field">
                    <label class="settings-label">Vision Model</label>
                    <p class="settings-hint">Must support image input (llava, moondream, bakllava, etc.).</p>
                    <input type="text" name="ai_vision_model" class="settings-input"
                           value="<?= e($settings['ai.vision_model'] ?? 'llava') ?>"
                           placeholder="llava">
                </div>
                <div class="settings-save-row">
                    <button type="submit" class="btn btn-primary">Save AI Settings</button>
                </div>
            </form>
        </div>
    </div>

</div>

