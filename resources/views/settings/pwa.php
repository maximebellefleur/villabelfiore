<?php
$csrf    = e(\App\Support\CSRF::getToken());
$enabled = ($cfg['pwa.enabled']     ?? '') === '1';
$name    = $cfg['pwa.name']         ?? 'Rooted';
$short   = $cfg['pwa.short_name']   ?? 'Rooted';
$desc    = $cfg['pwa.description']  ?? 'Land management system for orchards, gardens, and productive land.';
$theme   = $cfg['pwa.theme_color']  ?? '#29402B';
$bgCol   = $cfg['pwa.bg_color']     ?? '#F5F0EA';
$display = $cfg['pwa.display']      ?? 'standalone';
$orient  = $cfg['pwa.orientation']  ?? 'portrait-primary';
$startUrl= $cfg['pwa.start_url']    ?? '/dashboard';

$iconDir = PUBLIC_PATH . '/assets/images/';
$has512  = file_exists($iconDir . 'icon-512.png');
$has192  = file_exists($iconDir . 'icon-192.png');
$hasApple= file_exists($iconDir . 'apple-touch-icon.png');
$hasFav  = file_exists($iconDir . 'favicon-32.png');
?>

<div class="settings-wrap">

    <!-- Tab nav -->
    <nav class="settings-tab-nav" role="tablist">
        <a href="<?= url('/settings') ?>"               class="settings-tab" role="tab">General</a>
        <a href="<?= url('/settings/harvest') ?>"       class="settings-tab" role="tab">🌾 Harvest</a>
        <a href="<?= url('/settings/storage') ?>"       class="settings-tab" role="tab">Storage</a>
        <a href="<?= url('/settings/action-types') ?>"  class="settings-tab" role="tab">Action Types</a>
        <a href="<?= url('/settings/weather') ?>"       class="settings-tab" role="tab">🌤️ Weather</a>
        <a href="<?= url('/settings/calendar') ?>"      class="settings-tab" role="tab">📅 Calendar</a>
        <a href="<?= url('/settings/pwa') ?>"           class="settings-tab settings-tab--active" role="tab">📱 PWA</a>
        <a href="<?= url('/logs/errors') ?>"            class="settings-tab" role="tab">Error Logs</a>
        <a href="<?= url('/settings/upcoming') ?>"      class="settings-tab" role="tab">🗺 Roadmap</a>
        <a href="<?= url('/settings/upgrade') ?>"       class="settings-tab" role="tab">⬆️ Upgrade</a>
    </nav>

    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

    <!-- ── PWA Settings ─────────────────────────────────────────────────── -->
    <div class="settings-panel">
        <form method="POST" action="<?= url('/settings/pwa') ?>" class="settings-form">
            <input type="hidden" name="_token" value="<?= $csrf ?>">

            <!-- Enable -->
            <div class="settings-group">
                <div class="settings-group-title">Progressive Web App</div>
                <div class="settings-field settings-field--toggle">
                    <label class="settings-label" for="pwa_enabled">Enable PWA (Installable App)</label>
                    <p class="settings-hint">When enabled, users on mobile and desktop are prompted to install Rooted as a native-like app with an icon on the home screen.</p>
                    <label class="toggle-switch">
                        <input type="hidden" name="pwa_enabled" value="0">
                        <input type="checkbox" id="pwa_enabled" name="pwa_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Identity -->
            <div class="settings-group">
                <div class="settings-group-title">App Identity</div>
                <p class="settings-hint" style="padding: 0 var(--spacing-4) var(--spacing-2);">These values appear in the app manifest and are shown to users when they install the app.</p>

                <div class="settings-field">
                    <label class="settings-label" for="pwa_name">App Name</label>
                    <p class="settings-hint">Full name shown in app stores and install prompts.</p>
                    <input type="text" id="pwa_name" name="pwa_name" class="settings-input"
                           value="<?= e($name) ?>" placeholder="Rooted" maxlength="64">
                </div>

                <div class="settings-field">
                    <label class="settings-label" for="pwa_short_name">Short Name</label>
                    <p class="settings-hint">Used below the icon on the home screen (keep it under 12 characters).</p>
                    <input type="text" id="pwa_short_name" name="pwa_short_name" class="settings-input"
                           value="<?= e($short) ?>" placeholder="Rooted" maxlength="12">
                </div>

                <div class="settings-field">
                    <label class="settings-label" for="pwa_description">Description</label>
                    <p class="settings-hint">Shown in browser install prompts.</p>
                    <input type="text" id="pwa_description" name="pwa_description" class="settings-input"
                           value="<?= e($desc) ?>" placeholder="Land management system…" maxlength="128">
                </div>

                <div class="settings-field">
                    <label class="settings-label" for="pwa_start_url">Start URL</label>
                    <p class="settings-hint">The page the app opens to when launched from the home screen.</p>
                    <input type="text" id="pwa_start_url" name="pwa_start_url" class="settings-input"
                           value="<?= e($startUrl) ?>" placeholder="/dashboard" maxlength="128">
                </div>
            </div>

            <!-- Appearance -->
            <div class="settings-group">
                <div class="settings-group-title">Appearance</div>

                <div class="settings-field">
                    <label class="settings-label" for="pwa_theme_color">Theme Color</label>
                    <p class="settings-hint">Colours the browser chrome / status bar when running as an app.</p>
                    <div class="color-field-row">
                        <input type="color" id="pwa_theme_color" name="pwa_theme_color" class="color-picker"
                               value="<?= e($theme) ?>">
                        <input type="text" class="settings-input settings-input--sm color-text-input"
                               value="<?= e($theme) ?>"
                               oninput="document.getElementById('pwa_theme_color').value=this.value">
                    </div>
                </div>

                <div class="settings-field">
                    <label class="settings-label" for="pwa_bg_color">Background Color</label>
                    <p class="settings-hint">Shown during app launch (splash screen) before the first paint.</p>
                    <div class="color-field-row">
                        <input type="color" id="pwa_bg_color" name="pwa_bg_color" class="color-picker"
                               value="<?= e($bgCol) ?>">
                        <input type="text" class="settings-input settings-input--sm color-text-input"
                               value="<?= e($bgCol) ?>"
                               oninput="document.getElementById('pwa_bg_color').value=this.value">
                    </div>
                </div>

                <div class="settings-field">
                    <label class="settings-label" for="pwa_display">Display Mode</label>
                    <p class="settings-hint"><strong>standalone</strong> removes the browser UI (recommended). <strong>minimal-ui</strong> keeps a minimal toolbar.</p>
                    <select id="pwa_display" name="pwa_display" class="settings-input">
                        <?php foreach (['standalone' => 'Standalone (recommended)', 'minimal-ui' => 'Minimal UI', 'browser' => 'Browser', 'fullscreen' => 'Fullscreen'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $display === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-field">
                    <label class="settings-label" for="pwa_orientation">Orientation</label>
                    <select id="pwa_orientation" name="pwa_orientation" class="settings-input">
                        <?php foreach (['portrait-primary' => 'Portrait (recommended)', 'landscape-primary' => 'Landscape', 'any' => 'Any'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $orient === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="btn btn-primary">Save PWA Settings</button>
            </div>
        </form>
    </div>

    <!-- ── Icon Upload ───────────────────────────────────────────────────── -->
    <div class="settings-panel" style="margin-top: var(--spacing-5);">
        <div class="settings-group">
            <div class="settings-group-title">App Icons</div>
            <div class="settings-field">
                <p class="settings-hint">Upload a square PNG or JPG image (at least 512×512 px). Rooted will automatically generate all required icon sizes.</p>

                <!-- Current icons preview -->
                <div class="pwa-icon-preview">
                    <?php if ($has512): ?>
                    <div class="pwa-icon-item">
                        <img src="<?= url('/assets/images/icon-512.png') ?>?v=<?= filemtime($iconDir . 'icon-512.png') ?>" alt="512px icon" class="pwa-icon-thumb">
                        <span>512×512</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($has192): ?>
                    <div class="pwa-icon-item">
                        <img src="<?= url('/assets/images/icon-192.png') ?>?v=<?= filemtime($iconDir . 'icon-192.png') ?>" alt="192px icon" class="pwa-icon-thumb pwa-icon-thumb--sm">
                        <span>192×192</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($hasApple): ?>
                    <div class="pwa-icon-item">
                        <img src="<?= url('/assets/images/apple-touch-icon.png') ?>?v=<?= filemtime($iconDir . 'apple-touch-icon.png') ?>" alt="Apple touch icon" class="pwa-icon-thumb pwa-icon-thumb--sm">
                        <span>Apple 180</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($hasFav): ?>
                    <div class="pwa-icon-item">
                        <img src="<?= url('/assets/images/favicon-32.png') ?>?v=<?= filemtime($iconDir . 'favicon-32.png') ?>" alt="Favicon 32px" class="pwa-icon-thumb pwa-icon-thumb--xs">
                        <span>Favicon 32</span>
                    </div>
                    <?php endif; ?>
                    <?php if (!$has512 && !$has192): ?>
                    <p class="settings-hint">No icons found — upload a source image below to generate them.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= url('/settings/pwa/upload-icon') ?>" enctype="multipart/form-data" class="settings-form" style="margin-top: var(--spacing-3);">
            <input type="hidden" name="_token" value="<?= $csrf ?>">
            <div class="settings-group">
                <div class="settings-group-title">Upload New Icon</div>
                <div class="settings-field">
                    <label class="settings-label" for="icon_source">Source Image</label>
                    <p class="settings-hint">PNG, JPG, or WebP. Minimum 512×512 px recommended. The image should be square. A transparent background is preserved for PNG files.</p>
                    <input type="file" id="icon_source" name="icon_source" class="settings-input"
                           accept="image/png,image/jpeg,image/webp">
                </div>
                <div class="settings-field">
                    <button type="submit" class="btn btn-secondary">Upload &amp; Generate Icons</button>
                </div>
            </div>
        </form>
    </div>

    <!-- ── Install prompt ───────────────────────────────────────────────── -->
    <div class="settings-panel" style="margin-top: var(--spacing-5);">
        <div class="settings-group">
            <div class="settings-group-title">Install App</div>
            <div class="settings-field">
                <p class="settings-hint">If your browser supports it, you can install Rooted directly from here. On iOS Safari, use the share sheet → "Add to Home Screen".</p>
                <button type="button" class="btn btn-primary" id="pwaInstallBtn" style="display:none">
                    📱 Install Rooted App
                </button>
                <p id="pwaInstallMsg" class="settings-hint" style="display:none;color:var(--color-success);font-weight:600">
                    Rooted is already installed or your browser does not support direct install.
                </p>
                <p id="pwaUnsupported" class="settings-hint">
                    Open this page in a supported browser (Chrome, Edge, Samsung Internet) or use Safari on iOS to add to your home screen.
                </p>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    var deferredPrompt = null;
    var installBtn     = document.getElementById('pwaInstallBtn');
    var installMsg     = document.getElementById('pwaInstallMsg');
    var unsupported    = document.getElementById('pwaUnsupported');

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        installBtn.style.display = 'inline-flex';
        unsupported.style.display = 'none';
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        installBtn.style.display = 'none';
        installMsg.style.display = 'block';
        unsupported.style.display = 'none';
    });

    if (installBtn) {
        installBtn.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (choiceResult) {
                deferredPrompt = null;
                if (choiceResult.outcome === 'accepted') {
                    installBtn.style.display = 'none';
                    installMsg.style.display = 'block';
                }
            });
        });
    }

    // Keep color text input in sync with color picker
    document.querySelectorAll('.color-picker').forEach(function (picker) {
        picker.addEventListener('input', function () {
            var row   = picker.closest('.color-field-row');
            var input = row && row.querySelector('.color-text-input');
            if (input) input.value = picker.value;
        });
    });
}());
</script>

<style>
.settings-actions {
    padding: var(--spacing-3) 0 var(--spacing-2);
}

.color-field-row {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-top: 6px;
}
.color-picker {
    width: 48px;
    height: 40px;
    border: 1.5px solid var(--color-border);
    border-radius: 8px;
    padding: 2px;
    cursor: pointer;
    background: var(--color-surface);
    flex-shrink: 0;
}

/* Icon preview grid */
.pwa-icon-preview {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-3);
    margin-top: var(--spacing-3);
    align-items: flex-end;
}
.pwa-icon-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    color: var(--color-text-muted);
}
.pwa-icon-thumb {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    border: 1px solid var(--color-border);
    object-fit: contain;
    background: var(--color-surface);
}
.pwa-icon-thumb--sm { width: 56px; height: 56px; border-radius: 12px; }
.pwa-icon-thumb--xs { width: 32px; height: 32px; border-radius: 6px; }

.settings-field--toggle {
    flex-direction: column;
    gap: var(--spacing-2);
}
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
    flex-shrink: 0;
}
.toggle-switch input[type="checkbox"] {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}
.toggle-slider {
    position: absolute;
    inset: 0;
    background: var(--color-border-strong);
    border-radius: 999px;
    transition: background 0.2s;
    cursor: pointer;
}
.toggle-slider::before {
    content: '';
    position: absolute;
    left: 3px;
    top: 3px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #fff;
    transition: transform 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2);
}
.toggle-switch input:checked + .toggle-slider {
    background: var(--color-primary);
}
.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(24px);
}
</style>
