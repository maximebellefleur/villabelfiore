<?php
$csrf = e(\App\Support\CSRF::getToken());

// Only show types that are harvest-capable (enabled by default or currently enabled)
$displayTypes = [];
foreach ($itemTypes as $typeKey => $typeCfg) {
    if (!empty($typeCfg['harvest_enabled']) || ($harvestConfig[$typeKey]['enabled'] ?? 0)) {
        $displayTypes[$typeKey] = $typeCfg['label'] ?? ucwords(str_replace('_', ' ', $typeKey));
    }
}

$typeIcons = [
    'olive_tree'  => '🫒',
    'almond_tree' => '🌰',
    'vine'        => '🍇',
    'tree'        => '🌳',
];
?>

<div class="settings-wrap">

    <!-- Tab nav -->
    <nav class="settings-tab-nav" role="tablist">
        <a href="<?= url('/settings') ?>"               class="settings-tab" role="tab">General</a>
        <a href="<?= url('/settings/harvest') ?>"       class="settings-tab settings-tab--active" role="tab">🌾 Harvest</a>
        <a href="<?= url('/settings/storage') ?>"       class="settings-tab" role="tab">Storage</a>
        <a href="<?= url('/settings/action-types') ?>"  class="settings-tab" role="tab">Action Types</a>
        <a href="<?= url('/settings/weather') ?>"       class="settings-tab" role="tab">🌤️ Weather</a>
        <a href="<?= url('/settings/calendar') ?>"      class="settings-tab" role="tab">📅 Calendar</a>
        <a href="<?= url('/settings/pwa') ?>"           class="settings-tab" role="tab">📱 PWA</a>
        <a href="<?= url('/logs/errors') ?>"            class="settings-tab" role="tab">Error Logs</a>
        <a href="<?= url('/settings/upcoming') ?>"      class="settings-tab" role="tab">🗺 Roadmap</a>
        <a href="<?= url('/settings/upgrade') ?>"       class="settings-tab" role="tab">⬆️ Upgrade</a>
    </nav>

    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

    <div class="settings-panel">

        <p class="settings-hint" style="margin-bottom:var(--spacing-4)">
            Configure which item types can be harvested, how many times per year, and the slider range shown on the Quick Harvest page.
        </p>

        <form method="POST" action="<?= url('/settings/harvest') ?>" class="settings-form">
            <input type="hidden" name="_token" value="<?= $csrf ?>">

            <?php foreach ($displayTypes as $typeKey => $typeLabel):
                $cfg = $harvestConfig[$typeKey] ?? [];
                $icon = $typeIcons[$typeKey] ?? '🌱';
            ?>
            <div class="settings-group">
                <div class="settings-group-title"><?= $icon ?> <?= e($typeLabel) ?></div>

                <div class="settings-field settings-field--inline">
                    <label class="settings-label">Enable Harvesting</label>
                    <label class="settings-toggle">
                        <input type="hidden" name="enabled_<?= e($typeKey) ?>" value="0">
                        <input type="checkbox" name="enabled_<?= e($typeKey) ?>" value="1"
                               <?= !empty($cfg['enabled']) ? 'checked' : '' ?>>
                        <span class="settings-toggle-track"></span>
                    </label>
                </div>

                <div class="settings-field settings-field--row">
                    <div class="settings-field-col">
                        <label class="settings-label">Max Harvests / Year</label>
                        <p class="settings-hint">How many times this type can be harvested per calendar year.</p>
                        <input type="number" name="max_per_year_<?= e($typeKey) ?>" class="settings-input settings-input--sm"
                               value="<?= (int)($cfg['max_per_year'] ?? 1) ?>" min="1" max="52">
                    </div>
                    <div class="settings-field-col">
                        <label class="settings-label">Unit Label</label>
                        <p class="settings-hint">e.g. baskets, kg, wheelbarrows</p>
                        <input type="text" name="unit_<?= e($typeKey) ?>" class="settings-input settings-input--sm"
                               value="<?= e($cfg['unit'] ?? 'units') ?>" placeholder="units" maxlength="32">
                    </div>
                </div>

                <div class="settings-field settings-field--row">
                    <div class="settings-field-col">
                        <label class="settings-label">Slider Max</label>
                        <p class="settings-hint">Highest value shown on harvest slider.</p>
                        <input type="number" name="slider_max_<?= e($typeKey) ?>" class="settings-input settings-input--sm"
                               value="<?= (float)($cfg['slider_max'] ?? 5) ?>" min="0.25" max="9999" step="0.25">
                    </div>
                    <div class="settings-field-col">
                        <label class="settings-label">Slider Step</label>
                        <p class="settings-hint">Smallest increment (e.g. 0.25, 1).</p>
                        <input type="number" name="slider_step_<?= e($typeKey) ?>" class="settings-input settings-input--sm"
                               value="<?= (float)($cfg['slider_step'] ?? 0.25) ?>" min="0.05" max="100" step="0.05">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="settings-save-row">
                <button type="submit" class="btn btn-primary btn-lg">Save Harvest Settings</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Re-use settings styles from index.php (same page layout) */
.settings-wrap { display:block; width:100%; }
.settings-tab-nav {
    display:flex; flex-direction:row; flex-wrap:nowrap; gap:6px;
    overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:none;
    padding:0 0 var(--spacing-4); border-bottom:2px solid var(--color-border);
    margin-bottom:var(--spacing-5);
}
.settings-tab-nav::-webkit-scrollbar { display:none; }
.settings-tab {
    display:inline-flex; align-items:center; white-space:nowrap; flex-shrink:0;
    padding:8px 16px; border-radius:999px; font-size:.85rem; font-weight:500;
    color:var(--color-text-muted); background:var(--color-surface);
    border:1px solid var(--color-border); text-decoration:none;
    transition:background .15s, color .15s, border-color .15s;
}
.settings-tab:hover { background:var(--color-primary); color:#fff; border-color:var(--color-primary); text-decoration:none; }
.settings-tab--active { background:var(--color-primary); color:#fff; border-color:var(--color-primary); }
.settings-panel { display:block; width:100%; }
.settings-form { display:flex; flex-direction:column; gap:var(--spacing-5); }
.settings-group { background:var(--color-surface-raised); border-radius:16px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06),0 4px 12px rgba(0,0,0,.04); }
.settings-group-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--color-text-muted); padding:var(--spacing-3) var(--spacing-4) var(--spacing-2); border-bottom:1px solid var(--color-border); background:var(--color-surface); }
.settings-field { padding:var(--spacing-3) var(--spacing-4); border-bottom:1px solid var(--color-border); display:flex; flex-direction:column; gap:4px; }
.settings-field:last-child { border-bottom:none; }
.settings-field--inline { flex-direction:row; align-items:center; justify-content:space-between; }
.settings-field--row { flex-direction:row; gap:var(--spacing-4); padding:var(--spacing-3) var(--spacing-4); }
.settings-field-col { flex:1; display:flex; flex-direction:column; gap:4px; }
.settings-label { font-size:.9rem; font-weight:600; color:var(--color-text); }
.settings-hint { font-size:.78rem; color:var(--color-text-muted); margin:0; line-height:1.4; }
.settings-input { width:100%; padding:10px var(--spacing-3); border:1.5px solid var(--color-border); border-radius:10px; font-size:.95rem; font-family:inherit; background:var(--color-surface); color:var(--color-text); transition:border-color .15s,box-shadow .15s; margin-top:4px; }
.settings-input:focus { outline:none; border-color:var(--color-primary); box-shadow:0 0 0 3px rgba(45,90,39,.12); background:#fff; }
.settings-input--sm { max-width:140px; }
.settings-save-row { padding:var(--spacing-2) 0 var(--spacing-6); }
.btn-lg { padding:14px 32px; font-size:1rem; border-radius:999px; font-weight:600; }

/* Toggle switch */
.settings-toggle { position:relative; display:inline-flex; align-items:center; cursor:pointer; }
.settings-toggle input[type=checkbox] { position:absolute; opacity:0; width:0; height:0; }
.settings-toggle-track {
    width:44px; height:24px; border-radius:12px; background:var(--color-border);
    transition:background .2s; position:relative; flex-shrink:0;
}
.settings-toggle-track::after {
    content:''; position:absolute; top:3px; left:3px;
    width:18px; height:18px; border-radius:50%; background:#fff;
    box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s;
}
.settings-toggle input:checked + .settings-toggle-track { background:var(--color-primary); }
.settings-toggle input:checked + .settings-toggle-track::after { transform:translateX(20px); }
</style>
