<div class="settings-wrap">

    <!-- Tab nav -->
    <nav class="settings-tab-nav" role="tablist">
        <a href="<?= url('/settings') ?>"               class="settings-tab" role="tab">General</a>
        <a href="<?= url('/settings/harvest') ?>"       class="settings-tab" role="tab">🌾 Harvest</a>
        <a href="<?= url('/settings/storage') ?>"       class="settings-tab" role="tab">Storage</a>
        <a href="<?= url('/settings/action-types') ?>"  class="settings-tab settings-tab--active" role="tab">Action Types</a>
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
            This is a read-only reference list of all action types used in activity logs (pruning, harvest, treatments, etc.).
            Action types are defined by the system and are recorded automatically when you perform actions on items.
        </p>

        <div class="settings-group">
            <div class="settings-group-title">System Action Types</div>
            <div class="action-types-table-wrap">
                <table class="action-types-table">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Label</th>
                            <th>Scope</th>
                            <th>System</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $t): ?>
                        <tr>
                            <td><code class="action-key"><?= e($t['action_key']) ?></code></td>
                            <td><?= e($t['action_label']) ?></td>
                            <td><?= e($t['scope_type'] ?? '—') ?></td>
                            <td><?= $t['is_system'] ? '<span class="at-badge at-badge--sys">System</span>' : '—' ?></td>
                            <td><?= $t['is_active'] ? '<span class="at-badge at-badge--on">Active</span>' : '<span class="at-badge">Off</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
/* Action Types table — page-specific */
.action-types-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.action-types-table { width:100%; border-collapse:collapse; white-space:nowrap; min-width:420px; }
.action-types-table th {
    padding:10px 16px; text-align:left; font-size:.75rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.05em; color:var(--color-text-muted);
    border-bottom:1px solid var(--color-border); background:var(--color-surface);
}
.action-types-table td {
    padding:10px 16px; font-size:.85rem; border-bottom:1px solid var(--color-border);
    color:var(--color-text);
}
.action-types-table tr:last-child td { border-bottom:none; }
.action-types-table tr:hover td { background:var(--color-surface); }
.action-key { font-size:.8rem; background:var(--color-surface); padding:2px 7px; border-radius:5px; border:1px solid var(--color-border); font-family:monospace; }
.at-badge { display:inline-flex; padding:2px 10px; border-radius:999px; font-size:.72rem; font-weight:700; background:var(--color-border); color:var(--color-text-muted); }
.at-badge--sys { background:#ede9fe; color:#5b21b6; }
.at-badge--on  { background:#e8f5e1; color:#276749; }
</style>
