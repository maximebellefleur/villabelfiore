<div class="settings-wrap">

    <nav class="settings-tab-nav" role="tablist">
        <a href="<?= url('/settings') ?>"               class="settings-tab" role="tab">General</a>
        <a href="<?= url('/settings/harvest') ?>"       class="settings-tab" role="tab">🌾 Harvest</a>
        <a href="<?= url('/settings/storage') ?>"       class="settings-tab" role="tab">Storage</a>
        <a href="<?= url('/settings/action-types') ?>"  class="settings-tab" role="tab">Action Types</a>
        <a href="<?= url('/settings/item-types') ?>"    class="settings-tab settings-tab--active" role="tab">🌿 Item Types</a>
        <a href="<?= url('/settings/weather') ?>"       class="settings-tab" role="tab">🌤️ Weather</a>
        <a href="<?= url('/settings/calendar') ?>"      class="settings-tab" role="tab">📅 Calendar</a>
        <a href="<?= url('/settings/pwa') ?>"           class="settings-tab" role="tab">📱 PWA</a>
        <a href="<?= url('/settings/menus') ?>"         class="settings-tab" role="tab">☰ Menus</a>
        <a href="<?= url('/logs/errors') ?>"            class="settings-tab" role="tab">Error Logs</a>
        <a href="<?= url('/settings/upcoming') ?>"      class="settings-tab" role="tab">🗺 Roadmap</a>
        <a href="<?= url('/settings/upgrade') ?>"       class="settings-tab" role="tab">⬆️ Upgrade</a>
    </nav>

    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

    <div class="settings-panel">

        <!-- Add new type -->
        <div class="settings-group">
            <div class="settings-group-title">Add Custom Type</div>
            <form method="POST" action="<?= url('/settings/item-types/add') ?>" class="it-add-form">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <div class="it-add-row">
                    <input type="text" name="emoji" class="it-emoji-input" placeholder="📦" maxlength="4" value="">
                    <input type="text" name="label" class="settings-input it-label-input" placeholder="e.g. Rose Bush" required>
                    <button type="submit" class="btn btn-primary btn-sm">+ Add</button>
                </div>
                <p class="settings-hint">The emoji is optional. The type key is auto-generated from the name (e.g. "Rose Bush" → <code>rose_bush</code>).</p>
            </form>
        </div>

        <!-- Custom types -->
        <?php if (!empty($custom)): ?>
        <div class="settings-group">
            <div class="settings-group-title">Your Custom Types</div>
            <div class="it-grid">
                <?php foreach ($custom as $t): ?>
                <div class="it-card it-card--custom">
                    <span class="it-emoji"><?= e($t['emoji'] ?? '📦') ?></span>
                    <div class="it-info">
                        <span class="it-label"><?= e($t['label']) ?></span>
                        <code class="it-key"><?= e($t['key']) ?></code>
                    </div>
                    <form method="POST" action="<?= url('/settings/item-types/' . urlencode($t['key']) . '/delete') ?>" onsubmit="return confirm('Remove <?= e(addslashes($t['label'])) ?>?')">
                        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                        <button type="submit" class="it-del-btn" title="Remove">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Built-in types -->
        <div class="settings-group">
            <div class="settings-group-title">Built-in Types <span class="it-builtin-note">(read-only)</span></div>
            <div class="it-grid">
                <?php
                $builtInEmojis = [
                    'tree'=>'🌳','olive_tree'=>'🫒','almond_tree'=>'🌰','vine'=>'🍇',
                    'garden'=>'🌿','bed'=>'🌱','line'=>'〰️','prep_zone'=>'🟫',
                    'mobile_coop'=>'🐓','building'=>'🏠','water_point'=>'💧',
                    'zone'=>'🛖','orchard'=>'🏕',
                ];
                foreach ($builtIn as $key => $cfg):
                ?>
                <div class="it-card it-card--builtin">
                    <span class="it-emoji"><?= $builtInEmojis[$key] ?? '📦' ?></span>
                    <div class="it-info">
                        <span class="it-label"><?= e($cfg['label']) ?></span>
                        <code class="it-key"><?= e($key) ?></code>
                    </div>
                    <?php if (!empty($cfg['harvest_enabled'])): ?>
                    <span class="it-tag">🌾</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /settings-panel -->
</div><!-- /settings-wrap -->

<style>
.it-add-form { max-width:520px; }
.it-add-row { display:flex;gap:8px;align-items:center;margin-bottom:6px; }
.it-emoji-input { width:54px;text-align:center;font-size:1.3rem;padding:8px 4px;border:1.5px solid var(--color-border);border-radius:var(--radius-md);background:var(--color-surface-raised); }
.it-label-input { flex:1; }
.it-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-top:8px; }
.it-card { display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1.5px solid var(--color-border);background:var(--color-surface); }
.it-card--custom { border-color:var(--color-primary); }
.it-emoji { font-size:1.4rem;flex-shrink:0; }
.it-info { flex:1;min-width:0; }
.it-label { display:block;font-weight:700;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.it-key { display:block;font-size:.72rem;color:var(--color-text-muted);margin-top:1px; }
.it-del-btn { background:none;border:none;color:var(--color-text-muted);cursor:pointer;font-size:.9rem;padding:2px 4px;border-radius:4px; }
.it-del-btn:hover { color:#dc3545;background:#fff0f0; }
.it-tag { font-size:.8rem;flex-shrink:0; }
.it-builtin-note { font-weight:400;font-size:.78rem;color:var(--color-text-muted); }
</style>
