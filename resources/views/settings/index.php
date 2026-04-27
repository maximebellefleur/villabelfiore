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
                <div class="settings-group-title">Garden &amp; Climate</div>

                <div class="settings-field">
                    <label class="settings-label">Climate Zone</label>
                    <p class="settings-hint">Used for seasonal planting suggestions in the Garden hub.</p>
                    <select name="garden_climate_zone" class="settings-input">
                        <?php
                        $climateZones = [
                            ''                       => '— Select your climate —',
                            'mediterranean_sicily'   => '🌿 Mediterranean — Sicily / S. Italy (Hot dry summer, mild winter)',
                            'mediterranean_general'  => '🌿 Mediterranean — General (Spain, Greece, Maghreb)',
                            'continental_north_italy'=> '🌱 Continental — N. Italy / Po Valley (Cold winter, hot summer)',
                            'temperate_oceanic'      => '🌧 Temperate Oceanic — N. Europe (UK, France, Benelux)',
                            'continental_central_eu' => '❄️ Continental — Central Europe (Germany, Austria, Poland)',
                            'subtropical_humid'      => '🌴 Subtropical Humid — SE USA, SE Asia',
                            'tropical'               => '🌺 Tropical (Year-round warm)',
                            'arid_desert'            => '🏜 Arid / Desert',
                            'semi_arid'              => '🌵 Semi-Arid',
                            'alpine'                 => '⛰ Alpine / Mountain',
                        ];
                        $currentZone = $settings['garden.climate_zone'] ?? 'mediterranean_sicily';
                        foreach ($climateZones as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $currentZone === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="settings-group">
                <div class="settings-group-title">Companion Planting AI</div>
                <p class="settings-hint" style="margin:0 0 12px">Powers the ☘ Companions button on bed planting pages. Supports OpenAI, Anthropic (Claude), or any OpenAI-compatible endpoint.</p>

                <div class="settings-field">
                    <label class="settings-label">Provider</label>
                    <select name="companion_api_provider" class="settings-input">
                        <?php $companionProvider = $settings['companion_api_provider'] ?? 'openai'; ?>
                        <option value="openai"     <?= $companionProvider === 'openai'     ? 'selected' : '' ?>>OpenAI (GPT-4o, GPT-4o-mini…)</option>
                        <option value="anthropic"  <?= $companionProvider === 'anthropic'  ? 'selected' : '' ?>>Anthropic (Claude)</option>
                        <option value="custom"     <?= $companionProvider === 'custom'     ? 'selected' : '' ?>>Custom / OpenAI-compatible</option>
                    </select>
                </div>

                <div class="settings-field">
                    <label class="settings-label">API Key</label>
                    <input type="password" name="companion_api_key" class="settings-input"
                           value="<?= e($settings['companion_api_key'] ?? '') ?>"
                           placeholder="sk-… or your API key" autocomplete="new-password">
                </div>

                <div class="settings-field">
                    <label class="settings-label">Model</label>
                    <input type="text" name="companion_api_model" class="settings-input settings-input--sm"
                           value="<?= e($settings['companion_api_model'] ?? '') ?>"
                           placeholder="gpt-4o-mini  or  claude-haiku-4-5-20251001">
                </div>

                <div class="settings-field">
                    <label class="settings-label">Custom endpoint URL</label>
                    <p class="settings-hint">Leave blank for OpenAI or Anthropic. Set for local models (Ollama, LM Studio, etc.).</p>
                    <input type="text" name="companion_api_url" class="settings-input"
                           value="<?= e($settings['companion_api_url'] ?? '') ?>"
                           placeholder="https://your-server/v1/chat/completions">
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
                    <label class="settings-label">GPS Detect Map Zoom Level</label>
                    <p class="settings-hint">Map zoom level after tapping "Locate Me" on item forms. Higher = more zoomed in. Max usable is 19–20 (satellite tiles). Default: 18.</p>
                    <input type="number" name="gps_detect_zoom_level" class="settings-input settings-input--sm"
                           value="<?= e($settings['gps.detect_zoom_level'] ?? '18') ?>" min="10" max="21">
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

        <!-- Favicon upload -->
        <div class="settings-group" style="margin-top:var(--spacing-6)" id="favicon">
            <div class="settings-group-title">Favicon</div>
            <p class="settings-hint" style="margin-bottom:var(--spacing-4)">
                The small icon shown in browser tabs and bookmarks.
                Upload an <strong>.ico</strong>, <strong>.png</strong>, or <strong>.svg</strong> file.
                Recommended sizes: <strong>32×32 px</strong> minimum (ICO/PNG), or a square SVG.
                For best cross-browser support use a <strong>multi-size .ico</strong> containing 16×16, 32×32, and 48×48 variants.
            </p>
            <?php
            $_faviconCurrent = null; $_faviconExt = null;
            foreach (['ico','png','svg'] as $_fext) {
                $_ff = PUBLIC_PATH . '/assets/images/favicon.' . $_fext;
                if (file_exists($_ff)) {
                    $_faviconCurrent = url('/assets/images/favicon.' . $_fext) . '?v=' . filemtime($_ff);
                    $_faviconExt = $_fext;
                    break;
                }
            }
            ?>
            <div style="display:flex;align-items:flex-start;gap:var(--spacing-4);flex-wrap:wrap">
                <!-- Preview -->
                <div style="flex-shrink:0">
                    <div style="display:flex;gap:10px;align-items:flex-end;margin-bottom:8px">
                        <!-- 16×16 sim -->
                        <div style="text-align:center">
                            <div style="width:32px;height:32px;background:#f1f5f0;border:1px solid var(--color-border);border-radius:4px;display:flex;align-items:center;justify-content:center;overflow:hidden">
                                <?php if ($_faviconCurrent && $_faviconExt !== 'ico'): ?>
                                <img src="<?= $_faviconCurrent ?>" style="width:16px;height:16px;object-fit:contain">
                                <?php elseif ($_faviconCurrent): ?>
                                <img src="<?= $_faviconCurrent ?>" style="width:16px;height:16px;object-fit:contain">
                                <?php else: ?>
                                <span style="font-size:9px;color:#aaa">none</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:.6rem;color:var(--color-text-muted);margin-top:3px">16 px</div>
                        </div>
                        <!-- 32×32 sim -->
                        <div style="text-align:center">
                            <div style="width:48px;height:48px;background:#f1f5f0;border:1px solid var(--color-border);border-radius:4px;display:flex;align-items:center;justify-content:center;overflow:hidden">
                                <?php if ($_faviconCurrent && $_faviconExt !== 'ico'): ?>
                                <img src="<?= $_faviconCurrent ?>" style="width:32px;height:32px;object-fit:contain">
                                <?php elseif ($_faviconCurrent): ?>
                                <img src="<?= $_faviconCurrent ?>" style="width:32px;height:32px;object-fit:contain">
                                <?php else: ?>
                                <span style="font-size:10px;color:#aaa">none</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:.6rem;color:var(--color-text-muted);margin-top:3px">32 px</div>
                        </div>
                        <!-- 48×48 sim -->
                        <div style="text-align:center">
                            <div style="width:58px;height:58px;background:#f1f5f0;border:1px solid var(--color-border);border-radius:4px;display:flex;align-items:center;justify-content:center;overflow:hidden">
                                <?php if ($_faviconCurrent && $_faviconExt !== 'ico'): ?>
                                <img src="<?= $_faviconCurrent ?>" style="width:48px;height:48px;object-fit:contain">
                                <?php elseif ($_faviconCurrent): ?>
                                <img src="<?= $_faviconCurrent ?>" style="width:48px;height:48px;object-fit:contain">
                                <?php else: ?>
                                <span style="font-size:11px;color:#aaa">none</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:.6rem;color:var(--color-text-muted);margin-top:3px">48 px</div>
                        </div>
                    </div>
                    <?php if ($_faviconCurrent): ?>
                    <div style="font-size:.72rem;color:var(--color-text-muted)">Current: <strong><?= strtoupper($_faviconExt) ?></strong></div>
                    <?php else: ?>
                    <div style="font-size:.72rem;color:var(--color-text-muted)">No favicon uploaded</div>
                    <?php endif; ?>
                </div>

                <!-- Upload + requirements -->
                <div style="flex:1;min-width:220px">
                    <form method="POST" action="<?= url('/settings/favicon') ?>" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:var(--spacing-3)">
                        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                        <input type="file" name="favicon_file" accept=".ico,.png,.svg,image/x-icon,image/png,image/svg+xml" required style="font-size:.85rem;flex:1;min-width:160px">
                        <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                    </form>
                    <?php if ($_faviconCurrent): ?>
                    <form method="POST" action="<?= url('/settings/favicon/delete') ?>" onsubmit="return confirm('Remove favicon?')" style="margin-bottom:var(--spacing-3)">
                        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:#dc3545">✕ Remove favicon</button>
                    </form>
                    <?php endif; ?>
                    <div style="background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius);padding:10px 14px;font-size:.78rem;color:var(--color-text-muted);line-height:1.7">
                        <strong style="color:var(--color-text);display:block;margin-bottom:4px">Size requirements</strong>
                        <div><strong>.ico</strong> — Best option. Should contain 16×16, 32×32, and 48×48 px layers.</div>
                        <div><strong>.png</strong> — Use a square image. 32×32 px minimum, 64×64 px recommended.</div>
                        <div><strong>.svg</strong> — Must be square viewBox. Scales perfectly at any size.</div>
                        <div style="margin-top:6px">💡 Free tool: <strong>favicon.io</strong> can generate an .ico from any image or text.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI / Vision settings -->
        <div class="settings-group" style="margin-top:var(--spacing-6)">
            <div class="settings-group-title">🤖 AI — Seed Photo Identification</div>
            <p class="settings-hint" style="margin-bottom:var(--spacing-4)">
                Powers the <strong>photo seed identification</strong> feature (Add Seed → Take/Choose Photo).
                Choose between a <strong>local AI</strong> running on your own hardware (Ollama, Raspberry Pi, home server)
                or a <strong>HuggingFace</strong> cloud inference endpoint.
            </p>

            <?php
            $aiMode = $settings['ai.mode'] ?? 'local';
            ?>

            <!-- Mode selector -->
            <div style="display:flex;gap:8px;margin-bottom:var(--spacing-4)">
                <button type="button" id="aiModeLocalBtn"
                        onclick="switchAiMode('local')"
                        class="btn <?= $aiMode === 'local' ? 'btn-primary' : 'btn-secondary' ?>"
                        style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px">
                    🖥 Local (Ollama)
                </button>
                <button type="button" id="aiModeHfBtn"
                        onclick="switchAiMode('huggingface')"
                        class="btn <?= $aiMode === 'huggingface' ? 'btn-primary' : 'btn-secondary' ?>"
                        style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px">
                    🤗 HuggingFace
                </button>
            </div>

            <form method="POST" action="<?= url('/settings/update') ?>" class="settings-form">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <input type="hidden" name="ai_mode" id="aiModeInput" value="<?= e($aiMode) ?>">

                <!-- ══ LOCAL / OLLAMA PANEL ══════════════════════════════════════ -->
                <div id="aiLocalPanel" style="<?= $aiMode !== 'local' ? 'display:none' : '' ?>">

                    <!-- Setup guide -->
                    <div style="background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius);padding:var(--spacing-4);margin-bottom:var(--spacing-4)">
                        <div style="font-weight:700;margin-bottom:var(--spacing-3)">📋 Ollama / Raspberry Pi Setup Guide</div>
                        <div style="display:flex;flex-direction:column;gap:var(--spacing-4)">

                            <div>
                                <div style="font-weight:600;font-size:.875rem;margin-bottom:4px">Step 1 — Install Ollama on your server or Raspberry Pi</div>
                                <p class="settings-hint" style="margin-bottom:6px">Run this in your server terminal (Linux / macOS). Requires root or sudo. Works on Raspberry Pi 4/5 (64-bit OS).</p>
                                <code style="display:block;background:#1e1e1e;color:#a8ff78;padding:10px 14px;border-radius:6px;font-size:.82rem;overflow-x:auto">curl -fsSL https://ollama.com/install.sh | sh</code>
                                <p class="settings-hint" style="margin-top:6px">On Windows, download the installer from <strong>ollama.com/download</strong>. Ollama starts automatically as a system service.</p>
                                <p class="settings-hint" style="margin-top:4px"><strong>Raspberry Pi tip:</strong> Ollama v0.1.29+ supports Pi 5 (ARM64). Pi 4 with 8 GB RAM runs <code>moondream</code> well.</p>
                            </div>

                            <div>
                                <div style="font-weight:600;font-size:.875rem;margin-bottom:4px">Step 2 — Pull a vision model</div>
                                <p class="settings-hint" style="margin-bottom:6px">Vision models understand images. Choose based on your hardware RAM:</p>
                                <table style="width:100%;font-size:.8rem;border-collapse:collapse;margin-bottom:8px">
                                    <thead><tr style="border-bottom:1px solid var(--color-border)">
                                        <th style="text-align:left;padding:4px 8px;font-weight:600">Model</th>
                                        <th style="text-align:left;padding:4px 8px;font-weight:600">RAM needed</th>
                                        <th style="text-align:left;padding:4px 8px;font-weight:600">Quality</th>
                                        <th style="text-align:left;padding:4px 8px;font-weight:600">Pull command</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr style="border-bottom:1px solid var(--color-border)"><td style="padding:5px 8px">moondream</td><td style="padding:5px 8px">~2 GB</td><td style="padding:5px 8px">Fast · Pi-friendly</td><td style="padding:5px 8px"><code>ollama pull moondream</code></td></tr>
                                        <tr style="border-bottom:1px solid var(--color-border)"><td style="padding:5px 8px">llava:7b</td><td style="padding:5px 8px">~4 GB</td><td style="padding:5px 8px">Good ✓ recommended</td><td style="padding:5px 8px"><code>ollama pull llava:7b</code></td></tr>
                                        <tr style="border-bottom:1px solid var(--color-border)"><td style="padding:5px 8px">llava:13b</td><td style="padding:5px 8px">~8 GB</td><td style="padding:5px 8px">Better</td><td style="padding:5px 8px"><code>ollama pull llava:13b</code></td></tr>
                                        <tr><td style="padding:5px 8px">llava:34b</td><td style="padding:5px 8px">~20 GB</td><td style="padding:5px 8px">Best</td><td style="padding:5px 8px"><code>ollama pull llava:34b</code></td></tr>
                                    </tbody>
                                </table>
                                <p class="settings-hint">Models download automatically from Ollama's library — no account needed.</p>
                            </div>

                            <div>
                                <div style="font-weight:600;font-size:.875rem;margin-bottom:4px">Step 3 — Start Ollama (if not running)</div>
                                <code style="display:block;background:#1e1e1e;color:#a8ff78;padding:10px 14px;border-radius:6px;font-size:.82rem">ollama serve</code>
                                <p class="settings-hint" style="margin-top:6px">If installed via the script it runs as a service automatically. Test: <code>curl http://localhost:11434</code> → "Ollama is running".</p>
                                <p class="settings-hint" style="margin-top:4px"><strong>Remote server / Pi:</strong> If Ollama runs on a different machine, set its IP as the endpoint below (e.g. <code>http://192.168.1.50:11434</code>) and make sure Ollama is set to <code>OLLAMA_HOST=0.0.0.0</code>.</p>
                            </div>

                        </div>
                    </div>

                    <!-- Config fields -->
                    <div class="settings-field">
                        <label class="settings-label">Ollama Endpoint</label>
                        <p class="settings-hint">Base URL of your Ollama instance. Use <code>http://localhost:11434</code> if on the same machine, or <code>http://&lt;raspberry-pi-ip&gt;:11434</code> for a remote Pi.</p>
                        <input type="url" name="ai_endpoint" class="settings-input"
                               value="<?= e($settings['ai.endpoint'] ?? 'http://localhost:11434') ?>"
                               placeholder="http://localhost:11434">
                    </div>
                    <div class="settings-field">
                        <label class="settings-label">Vision Model</label>
                        <p class="settings-hint">Must support image input. Use the exact name shown in <code>ollama list</code>.</p>
                        <input type="text" name="ai_vision_model" class="settings-input"
                               value="<?= e($settings['ai.vision_model'] ?? 'llava') ?>"
                               placeholder="llava">
                    </div>
                </div>

                <!-- ══ HUGGINGFACE PANEL ══════════════════════════════════════════ -->
                <div id="aiHfPanel" style="<?= $aiMode !== 'huggingface' ? 'display:none' : '' ?>">

                    <!-- Setup guide -->
                    <div style="background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius);padding:var(--spacing-4);margin-bottom:var(--spacing-4)">
                        <div style="font-weight:700;margin-bottom:var(--spacing-3)">📋 HuggingFace Setup Guide</div>
                        <div style="display:flex;flex-direction:column;gap:var(--spacing-4)">

                            <div>
                                <div style="font-weight:600;font-size:.875rem;margin-bottom:4px">Option A — Serverless Inference API (free tier available)</div>
                                <p class="settings-hint" style="margin-bottom:6px">HuggingFace's hosted API runs models without you needing any server. Rate-limited on free tier; faster on PRO (~$9/month).</p>
                                <ol style="margin:0 0 6px 18px;font-size:.82rem;line-height:1.8;color:var(--color-text-muted)">
                                    <li>Create a free account at <strong>huggingface.co</strong></li>
                                    <li>Go to <strong>Settings → Access Tokens</strong> and create a token with <em>Read</em> permission</li>
                                    <li>Choose a multimodal vision model (see list below)</li>
                                    <li>Set Endpoint URL to <code>https://api-inference.huggingface.co/models/Qwen/Qwen2.5-VL-7B-Instruct/v1/chat/completions</code> (model must be in the URL path) and enter the Model ID</li>
                                </ol>
                                <p class="settings-hint"><strong>Recommended free-tier models for seed identification:</strong></p>
                                <table style="width:100%;font-size:.8rem;border-collapse:collapse">
                                    <thead><tr style="border-bottom:1px solid var(--color-border)">
                                        <th style="text-align:left;padding:4px 8px;font-weight:600">Model ID</th>
                                        <th style="text-align:left;padding:4px 8px;font-weight:600">Notes</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr style="border-bottom:1px solid var(--color-border)"><td style="padding:5px 8px"><code>meta-llama/Llama-3.2-11B-Vision-Instruct</code></td><td style="padding:5px 8px">Excellent · free tier ✓</td></tr>
                                        <tr style="border-bottom:1px solid var(--color-border)"><td style="padding:5px 8px"><code>Qwen/Qwen2.5-VL-7B-Instruct</code></td><td style="padding:5px 8px">Very good for text on packets</td></tr>
                                        <tr><td style="padding:5px 8px"><code>microsoft/Florence-2-large</code></td><td style="padding:5px 8px">Good at label OCR</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div>
                                <div style="font-weight:600;font-size:.875rem;margin-bottom:4px">Option B — Dedicated Inference Endpoint (pay-per-hour)</div>
                                <p class="settings-hint" style="margin-bottom:6px">Deploy your own endpoint with a specific model for guaranteed performance. Good for production use.</p>
                                <ol style="margin:0 0 6px 18px;font-size:.82rem;line-height:1.8;color:var(--color-text-muted)">
                                    <li>Go to <strong>huggingface.co/inference-endpoints</strong></li>
                                    <li>Create an endpoint with a vision-language model</li>
                                    <li>Wait for it to be <em>Running</em></li>
                                    <li>Copy the endpoint URL (looks like <code>https://xxxx.endpoints.huggingface.cloud</code>)</li>
                                    <li>Paste it below — Rooted will automatically append <code>/v1/chat/completions</code></li>
                                </ol>
                            </div>

                        </div>
                    </div>

                    <!-- Quick-start: OpenRouter (recommended) -->
                    <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fde68a;border-radius:var(--radius);padding:var(--spacing-3) var(--spacing-4);margin-bottom:var(--spacing-4)">
                        <div style="font-weight:700;font-size:.85rem;margin-bottom:8px">⭐ Quickstart — OpenRouter (recommended, free, no server needed)</div>
                        <p class="settings-hint" style="margin-bottom:10px">OpenRouter is a free API gateway that hosts Qwen2.5-VL, Gemini Flash, and many other vision models. 100% cloud, no SSH, no installation. Takes about 3 minutes.</p>
                        <ol style="margin:0 0 0 16px;padding:0;display:flex;flex-direction:column;gap:10px;font-size:.82rem;line-height:1.7;color:var(--color-text)">
                            <li>
                                Go to <strong>openrouter.ai</strong> and create a free account.
                            </li>
                            <li>
                                Go to <strong>openrouter.ai/keys</strong> → <strong>Create Key</strong>. Copy the key (starts with <code>sk-or-</code>).
                            </li>
                            <li>
                                Paste these values in the fields below, then click <strong>Save AI Settings</strong>:<br>
                                <div style="background:#1e1e1e;color:#a8ff78;padding:8px 12px;border-radius:6px;margin-top:6px;font-size:.8rem;line-height:2">
                                    Endpoint URL: <span style="color:#ffd700">https://openrouter.ai/api/v1/chat/completions</span><br>
                                    Model ID: <span style="color:#ffd700">google/gemini-2.0-flash-exp:free</span><br>
                                    Token: <span style="color:#ffd700">sk-or-xxxxxxxxxxxxxxxxxxxx</span>
                                </div>
                            </li>
                            <li>
                                Go to <strong>Seeds → Add Seed</strong>, upload a packet photo and click Identify. Done.
                            </li>
                        </ol>
                        <p class="settings-hint" style="margin-top:10px">Free tier models (marked <code>:free</code>): <code>google/gemini-2.0-flash-exp:free</code> · <code>meta-llama/llama-3.2-11b-vision-instruct:free</code> · <code>microsoft/phi-4-vision-instruct:free</code>. No credit card required.</p>
                    </div>

                    <!-- Config fields -->
                    <div class="settings-field">
                        <label class="settings-label">Inference Endpoint URL</label>
                        <p class="settings-hint">
                            Any OpenAI-compatible <code>/chat/completions</code> endpoint. Examples:<br>
                            OpenRouter (recommended free): <code>https://openrouter.ai/api/v1/chat/completions</code><br>
                            HuggingFace dedicated endpoint: paste your endpoint base URL — <code>/v1/chat/completions</code> is appended automatically.
                        </p>
                        <input type="url" name="ai_hf_endpoint" class="settings-input"
                               value="<?= e($settings['ai.hf_endpoint'] ?? '') ?>"
                               placeholder="https://openrouter.ai/api/v1/chat/completions">
                    </div>
                    <div class="settings-field">
                        <label class="settings-label">Model ID</label>
                        <p class="settings-hint">
                            OpenRouter free vision models: <code>google/gemini-2.0-flash-exp:free</code> · <code>meta-llama/llama-3.2-11b-vision-instruct:free</code><br>
                            For HuggingFace dedicated endpoints, leave blank or enter <code>tgi</code>.
                        </p>
                        <input type="text" name="ai_hf_model" class="settings-input"
                               value="<?= e($settings['ai.hf_model'] ?? '') ?>"
                               placeholder="google/gemini-2.0-flash-exp:free">
                    </div>
                    <div class="settings-field">
                        <label class="settings-label">API Token</label>
                        <p class="settings-hint">OpenRouter: starts with <code>sk-or-</code>. HuggingFace: starts with <code>hf_</code>. Stored only in your local database.</p>
                        <input type="password" name="ai_hf_token" class="settings-input"
                               value="<?= e($settings['ai.hf_token'] ?? '') ?>"
                               placeholder="sk-or-… or hf_…"
                               autocomplete="new-password">
                    </div>
                </div>

                <!-- ══ SHARED — Extra prompt ══════════════════════════════════════ -->
                <div class="settings-field" style="margin-top:var(--spacing-4);padding-top:var(--spacing-4);border-top:1px solid var(--color-border)">
                    <label class="settings-label">Extra Prompt Instructions (optional)</label>
                    <p class="settings-hint">
                        These instructions are appended to the AI's botanical expert prompt for every identification request.
                        Use this to customise output for your region, language, or specific needs.<br>
                        Examples: <em>"Answer in French."</em> · <em>"Focus on Mediterranean varieties."</em> · <em>"Include pest resistance info in the notes field."</em>
                    </p>
                    <textarea name="ai_extra_prompt" class="settings-input" rows="3"
                              style="resize:vertical"
                              placeholder="e.g. Prefer Italian variety names. Include soil pH range in the notes field."><?= e($settings['ai.extra_prompt'] ?? '') ?></textarea>
                </div>

                <div class="settings-save-row">
                    <button type="submit" class="btn btn-primary">Save AI Settings</button>
                </div>
            </form>
        </div>

<script>
(function () {
    function switchAiMode(mode) {
        document.getElementById('aiModeInput').value = mode;
        document.getElementById('aiLocalPanel').style.display = mode === 'local'        ? '' : 'none';
        document.getElementById('aiHfPanel').style.display    = mode === 'huggingface' ? '' : 'none';
        document.getElementById('aiModeLocalBtn').className   = 'btn ' + (mode === 'local'        ? 'btn-primary' : 'btn-secondary');
        document.getElementById('aiModeHfBtn').className      = 'btn ' + (mode === 'huggingface' ? 'btn-primary' : 'btn-secondary');
    }
    window.switchAiMode = switchAiMode;
}());
</script>

        <!-- Map settings — boundary types -->
        <div class="settings-group" style="margin-top:var(--spacing-6)">
            <div class="settings-group-title">🗺 Map — Boundary Types</div>
            <p class="settings-hint" style="margin-bottom:var(--spacing-3)">
                Choose which item types can have a drawn polygon boundary on the map.
                Only selected types will show the <em>Draw Boundary</em> tool in their map popup.
            </p>
            <form method="POST" action="<?= url('/settings/map') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--spacing-2);margin-bottom:var(--spacing-4)">
                <?php foreach ($itemTypes as $_btKey => $_btCfg): ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1.5px solid var(--color-border);border-radius:8px;cursor:pointer;font-size:0.875rem;<?= in_array($_btKey, $boundaryTypes) ? 'border-color:var(--color-primary);background:var(--color-primary-soft,rgba(42,167,105,.07))' : '' ?>">
                        <input type="checkbox" name="boundary_types[]" value="<?= e($_btKey) ?>"
                               <?= in_array($_btKey, $boundaryTypes) ? 'checked' : '' ?>
                               style="width:16px;height:16px;accent-color:var(--color-primary)">
                        <?= e($_btCfg['label']) ?>
                    </label>
                <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary">Save Map Settings</button>
            </form>
        </div>

        <!-- Image Cache -->
        <div class="settings-group" style="margin-top:var(--spacing-6)">
            <div class="settings-group-title">Image Cache</div>
            <p class="settings-hint" style="margin-bottom:var(--spacing-4)">
                Item photos are cached in the browser for up to 1 year for fast loading. If you replaced a photo and the old version is still showing, use this button to force all browsers to reload fresh images.
            </p>
            <form method="POST" action="<?= url('/settings/clear-image-cache') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="btn btn-ghost">🗑 Clear Image Cache</button>
            </form>
        </div>

    </div>
</div>

