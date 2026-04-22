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

                <!-- Finance tracking toggle -->
                <div class="settings-field settings-field--inline" style="margin-top:var(--spacing-2)">
                    <div>
                        <label class="settings-label">Enable Financial Tracking</label>
                        <p class="settings-hint">Track costs &amp; revenue for this crop type in the Finance module.</p>
                    </div>
                    <label class="settings-toggle">
                        <input type="hidden"   name="finance_enabled_<?= e($typeKey) ?>" value="0">
                        <input type="checkbox" name="finance_enabled_<?= e($typeKey) ?>" value="1"
                               id="finChk_<?= e($typeKey) ?>"
                               <?= !empty($cfg['finance_enabled']) ? 'checked' : '' ?>
                               onchange="toggleFinRule('<?= e($typeKey) ?>')">
                        <span class="settings-toggle-track"></span>
                    </label>
                </div>
                <div id="finRule_<?= e($typeKey) ?>" style="<?= empty($cfg['finance_enabled']) ? 'display:none' : '' ?>;margin-top:var(--spacing-2)">
                    <label class="settings-label">Cost/Revenue Rule <span style="font-weight:400;color:var(--color-text-muted)">(optional note for AI or reference)</span></label>
                    <p class="settings-hint">Describe how costs &amp; revenues are calculated for this type — e.g. "€0.45/kg harvest, pruning cost split equally across trees".</p>
                    <textarea name="finance_rule_<?= e($typeKey) ?>" class="settings-input" rows="2"
                              placeholder="e.g. Harvest revenue = kg × market price. Pruning shared across all olive trees."
                              style="resize:vertical"><?= e($cfg['finance_rule'] ?? '') ?></textarea>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="settings-save-row">
                <button type="submit" class="btn btn-primary btn-lg">Save Harvest Settings</button>
            </div>
        </form>
    </div>
</div>
<script>
function toggleFinRule(key) {
    var chk = document.getElementById('finChk_' + key);
    var box = document.getElementById('finRule_' + key);
    if (box) box.style.display = chk.checked ? '' : 'none';
}
</script>

