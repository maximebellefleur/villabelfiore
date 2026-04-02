<?php
$miniMapEnabled = !empty($item['gps_lat']) && !empty($item['gps_lng']);

$typeEmoji = [
    'olive_tree'  => '🫒', 'tree' => '🌳', 'vine' => '🍇',
    'almond_tree' => '🌰', 'garden' => '🌿', 'zone' => '🛖',
    'orchard'     => '🏕', 'bed' => '🌱', 'line' => '〰️',
    'prep_zone'   => '🟫', 'mobile_coop' => '🐓',
    'building'    => '🏠', 'water_point' => '💧',
];
$typeColor = [
    'olive_tree'  => '#2d6a4f', 'almond_tree' => '#92400e', 'vine' => '#6d28d9',
    'tree'        => '#166534', 'garden' => '#0369a1', 'bed' => '#0369a1',
    'orchard'     => '#c2410c', 'zone' => '#4338ca', 'prep_zone' => '#b45309',
    'water_point' => '#0284c7', 'mobile_coop' => '#991b1b',
    'building'    => '#374151', 'line' => '#1d4ed8',
];
$emoji     = $typeEmoji[$item['type']] ?? '📦';
$color     = $typeColor[$item['type']] ?? '#2d6a4f';
$typeLabel = ucwords(str_replace('_', ' ', $item['type']));

// Find the identification photo if it exists
$idPhoto = null;
foreach ($attachments as $att) {
    if ($att['category'] === 'identification_photo' && str_starts_with($att['mime_type'] ?? '', 'image/')) {
        $idPhoto = $att;
        break;
    }
}
?>
?>

<!-- ============================================================
     MODERN HERO
     ============================================================ -->
<div class="show-hero" style="--item-color:<?= $color ?>">
    <?php if ($idPhoto): ?>
    <div class="show-hero-photo">
        <img src="<?= url('/attachments/' . (int)$idPhoto['id'] . '/download') ?>"
             alt="<?= e($item['name']) ?>" loading="eager">
        <div class="show-hero-photo-overlay"></div>
    </div>
    <?php else: ?>
    <div class="show-hero-photo show-hero-photo--placeholder">
        <span class="show-hero-placeholder-emoji"><?= $emoji ?></span>
    </div>
    <?php endif; ?>

    <div class="show-hero-content">
        <div class="show-hero-back">
            <a href="<?= url('/items') ?>" class="show-back-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                Items
            </a>
        </div>
        <div class="show-hero-badge"><?= $emoji ?> <?= e($typeLabel) ?></div>
        <h1 class="show-hero-name"><?= e($item['name']) ?></h1>
        <?php if ($item['status'] !== 'active'): ?>
        <span class="show-hero-status"><?= e($item['status']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Big action strip at bottom of hero -->
    <div class="show-hero-actions">
        <a href="<?= url('/items/' . (int)$item['id'] . '/photos') ?>" class="show-action-btn show-action-btn--primary">
            <span class="show-action-icon">📷</span>
            <span>Photos</span>
        </a>
        <a href="<?= url('/items/' . (int)$item['id'] . '/edit') ?>" class="show-action-btn">
            <span class="show-action-icon">✏️</span>
            <span>Edit</span>
        </a>
        <?php if (!empty($item['gps_lat'])): ?>
        <a href="<?= url('/dashboard/map') ?>" class="show-action-btn">
            <span class="show-action-icon">📍</span>
            <span>Map</span>
        </a>
        <?php endif; ?>
        <?php if (!empty($item['is_finance_enabled'])): ?>
        <a href="<?= url('/items/' . (int)$item['id'] . '/finance') ?>" class="show-action-btn">
            <span class="show-action-icon">💰</span>
            <span>Finance</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- BELOW-HERO: Details + Map/Log side by side on desktop -->
<div class="item-detail-grid">
    <!-- LEFT: Details + Properties -->
    <div class="item-detail-left">
        <div class="item-detail-card">
            <h3 class="item-detail-card-title">Details</h3>
            <dl class="item-dl">
                <div class="item-dl-row">
                    <dt>Type</dt>
                    <dd><?= e($typeLabel) ?></dd>
                </div>
                <?php if ($item['gps_lat'] && $item['gps_lng']): ?>
                <div class="item-dl-row">
                    <dt>GPS</dt>
                    <dd class="text-sm"><?= number_format((float)$item['gps_lat'], 6) ?>, <?= number_format((float)$item['gps_lng'], 6) ?><?= $item['gps_source'] ? ' <span class="text-muted">(' . e($item['gps_source']) . ')</span>' : '' ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($item['parent_id']): ?>
                <div class="item-dl-row">
                    <dt>Part of</dt>
                    <dd><a href="<?= url('/items/' . (int)$item['parent_id']) ?>">View parent &rarr;</a></dd>
                </div>
                <?php endif; ?>
                <div class="item-dl-row">
                    <dt>Status</dt>
                    <dd><?= e($item['status']) ?></dd>
                </div>
                <div class="item-dl-row">
                    <dt>Created</dt>
                    <dd><?= e(date('d M Y', strtotime($item['created_at']))) ?></dd>
                </div>
            </dl>
        </div>

        <?php if (!empty($meta)): ?>
        <div class="item-detail-card">
            <h3 class="item-detail-card-title">Properties</h3>
            <dl class="item-dl">
                <?php foreach ($meta as $key => $value): ?>
                <div class="item-dl-row">
                    <dt><?= e(ucwords(str_replace('_', ' ', $key))) ?></dt>
                    <dd><?= e($value) ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: Mini-Map + Log Action -->
    <div class="item-detail-right">
        <?php if ($miniMapEnabled): ?>
        <div class="item-detail-card">
            <h3 class="item-detail-card-title">📍 Location</h3>
            <div id="miniMap" style="height:200px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--color-border)"></div>
        </div>
        <script>
        window.MINI_MAP_LAT = <?= (float)$item['gps_lat'] ?>;
        window.MINI_MAP_LNG = <?= (float)$item['gps_lng'] ?>;
        window.MINI_MAP_READONLY = true;
        </script>
        <?php endif; ?>

        <div class="item-detail-card">
            <h3 class="item-detail-card-title">Log Action</h3>
            <form method="POST" action="<?= url('/items/' . ((int)$item['id']) . '/actions') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <div class="form-group" style="margin-bottom:var(--spacing-3)">
                    <select name="action_type" class="form-input form-input--touch" style="width:100%">
                        <option value="note">Note</option>
                        <option value="pruning">Pruning</option>
                        <option value="treatment">Treatment</option>
                        <option value="amendment">Amendment</option>
                        <option value="harvest">Harvest</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:var(--spacing-3)">
                    <input type="text" name="description" class="form-input form-input--touch" style="width:100%" placeholder="Description (required)" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Log</button>
            </form>
        </div>

        <div class="item-detail-card">
            <h3 class="item-detail-card-title">Add Reminder</h3>
            <form method="POST" action="<?= url('/reminders') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                <div class="form-group" style="margin-bottom:var(--spacing-3)">
                    <input type="text" name="title" class="form-input form-input--touch" style="width:100%" placeholder="Reminder title" required>
                </div>
                <div class="form-group" style="margin-bottom:var(--spacing-3)">
                    <input type="datetime-local" name="due_at" class="form-input form-input--touch" style="width:100%" required>
                </div>
                <button type="submit" class="btn btn-secondary btn-full">Add Reminder</button>
            </form>
        </div>
    </div>
</div>

<!-- TABS -->
<div class="tabs item-tabs" id="itemTabs">
    <nav class="tab-nav item-tab-nav">
        <button class="tab-btn tab-btn--active" data-tab="attachments">📎 Photos (<?= count($attachments) ?>)</button>
        <button class="tab-btn" data-tab="reminders">🔔 Reminders (<?= count($reminders) ?>)</button>
        <?php if (!empty($harvests)): ?>
        <button class="tab-btn" data-tab="harvests">🌾 Harvest</button>
        <?php endif; ?>
        <?php if (!empty($finances)): ?>
        <button class="tab-btn" data-tab="finance">💰 Finance</button>
        <?php endif; ?>
        <button class="tab-btn" data-tab="log">📋 Activity Log</button>
    </nav>

    <!-- ATTACHMENTS TAB -->
    <div class="tab-panel tab-panel--active" id="tab-attachments">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-4);flex-wrap:wrap;gap:var(--spacing-2)">
            <h3 style="margin:0">Attachments</h3>
            <a href="<?= url('/items/' . ((int)$item['id']) . '/photos') ?>" class="btn btn-primary btn-sm">📷 Manage Photos</a>
        </div>

        <form method="POST" action="<?= url('/items/' . ((int)$item['id']) . '/attachments') ?>" enctype="multipart/form-data" class="form" style="margin-bottom:var(--spacing-5);padding:var(--spacing-4);background:var(--color-surface);border-radius:var(--radius);border:1px solid var(--color-border)">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div style="display:flex;gap:var(--spacing-2);flex-wrap:wrap;align-items:flex-end">
                <div style="flex:2;min-width:160px">
                    <input type="file" name="file" class="form-input" required>
                </div>
                <div style="flex:1;min-width:140px">
                    <select name="category" class="form-input form-input--sm">
                        <option value="identification_photo">ID Photo</option>
                        <option value="yearly_refresh_north">Yearly — North</option>
                        <option value="yearly_refresh_south">Yearly — South</option>
                        <option value="yearly_refresh_east">Yearly — East</option>
                        <option value="yearly_refresh_west">Yearly — West</option>
                        <option value="harvest_photo">Harvest Photo</option>
                        <option value="general_attachment">General</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Upload</button>
            </div>
        </form>

        <?php
        $photoCategories = [
            'identification_photo' => 'Identification Photo',
            'yearly_refresh_north' => 'Yearly — North',
            'yearly_refresh_south' => 'Yearly — South',
            'yearly_refresh_east'  => 'Yearly — East',
            'yearly_refresh_west'  => 'Yearly — West',
            'harvest_photo'        => 'Harvest Photos',
            'general_attachment'   => 'General Attachments',
        ];
        $grouped = [];
        foreach ($attachments as $att) {
            $grouped[$att['category']][] = $att;
        }
        ?>
        <?php if (!empty($attachments)): ?>
            <?php foreach ($photoCategories as $catKey => $catLabel): ?>
                <?php if (!empty($grouped[$catKey])): ?>
                <h4 style="margin:var(--spacing-4) 0 var(--spacing-2);font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted)"><?= e($catLabel) ?></h4>
                <div class="attachment-grid">
                    <?php foreach ($grouped[$catKey] as $att): ?>
                    <div class="attachment-card">
                        <?php if (str_starts_with($att['mime_type'], 'image/')): ?>
                        <a href="<?= url('/attachments/' . ((int)$att['id']) . '/download') ?>" target="_blank">
                            <img src="<?= url('/attachments/' . ((int)$att['id']) . '/download') ?>" class="attachment-thumb" loading="lazy">
                        </a>
                        <?php endif; ?>
                        <div class="attachment-info">
                            <a href="<?= url('/attachments/' . ((int)$att['id']) . '/download') ?>" class="attachment-name"><?= e($att['original_filename']) ?></a>
                        </div>
                        <form method="POST" action="<?= url('/attachments/' . ((int)$att['id']) . '/trash') ?>">
                            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                            <button class="btn btn-sm btn-danger">&times;</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php
            foreach ($grouped as $catKey => $catAtts) {
                if (!array_key_exists($catKey, $photoCategories)) {
            ?>
                <h4 style="margin:var(--spacing-4) 0 var(--spacing-2)"><?= e(str_replace('_', ' ', ucfirst($catKey))) ?></h4>
                <div class="attachment-grid">
                    <?php foreach ($catAtts as $att): ?>
                    <div class="attachment-card">
                        <?php if (str_starts_with($att['mime_type'], 'image/')): ?>
                        <a href="<?= url('/attachments/' . ((int)$att['id']) . '/download') ?>" target="_blank">
                            <img src="<?= url('/attachments/' . ((int)$att['id']) . '/download') ?>" class="attachment-thumb" loading="lazy">
                        </a>
                        <?php endif; ?>
                        <div class="attachment-info">
                            <a href="<?= url('/attachments/' . ((int)$att['id']) . '/download') ?>" class="attachment-name"><?= e($att['original_filename']) ?></a>
                            <span class="badge badge-sm"><?= e(str_replace('_', ' ', $att['category'])) ?></span>
                        </div>
                        <form method="POST" action="<?= url('/attachments/' . ((int)$att['id']) . '/trash') ?>">
                            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                            <button class="btn btn-sm btn-danger">&times;</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php
                }
            }
            ?>
        <?php else: ?>
        <p class="text-muted">No attachments yet. <a href="<?= url('/items/' . ((int)$item['id']) . '/photos') ?>">Upload photos &rarr;</a></p>
        <?php endif; ?>
    </div>

    <!-- REMINDERS TAB -->
    <div class="tab-panel" id="tab-reminders">
        <h3 style="margin-bottom:var(--spacing-4)">Reminders</h3>
        <?php if (empty($reminders)): ?>
        <p class="text-muted">No pending reminders.</p>
        <?php else: ?>
        <ul class="reminder-list">
            <?php foreach ($reminders as $r): ?>
            <li class="reminder-item">
                <span class="reminder-date <?= (strtotime($r['due_at']) < time()) ? 'text-danger' : '' ?>">
                    <?= e(date('d M Y', strtotime($r['due_at']))) ?>
                </span>
                <span class="reminder-title"><?= e($r['title']) ?></span>
                <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/complete') ?>" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-success">Done</button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- HARVESTS TAB -->
    <?php if (!empty($harvests)): ?>
    <div class="tab-panel" id="tab-harvests">
        <h3 style="margin-bottom:var(--spacing-3)">Harvests</h3>
        <?php
        $harvestTotals = [];
        foreach ($harvests as $h) {
            $u = $h['unit'] ?? 'units';
            $harvestTotals[$u] = ($harvestTotals[$u] ?? 0) + (float)$h['quantity'];
        }
        $totalParts = [];
        foreach ($harvestTotals as $unit => $qty) {
            $totalParts[] = number_format($qty, 3) . ' ' . e($unit);
        }
        ?>
        <p style="margin-bottom:var(--spacing-3)"><strong>Total harvested:</strong> <?= implode(', ', $totalParts) ?></p>
        <form method="POST" action="<?= url('/items/' . ((int)$item['id']) . '/harvests') ?>" class="form-inline" style="margin-bottom:var(--spacing-4)">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <input type="number" step="0.001" name="quantity" class="form-input form-input--sm" placeholder="Qty" required>
            <input type="text" name="unit" class="form-input form-input--sm" placeholder="Unit (kg, L…)" required>
            <input type="datetime-local" name="recorded_at" class="form-input form-input--sm" required>
            <input type="text" name="quality_grade" class="form-input form-input--sm" placeholder="Grade (opt.)">
            <input type="text" name="notes" class="form-input form-input--sm" placeholder="Notes (opt.)">
            <button type="submit" class="btn btn-primary">Record</button>
        </form>
        <table class="table">
            <thead>
                <tr><th>Date</th><th>Quantity</th><th>Unit</th><th>Grade</th><th>Notes</th></tr>
            </thead>
            <tbody>
                <?php foreach ($harvests as $h): ?>
                <tr>
                    <td><?= e(date('d M Y', strtotime($h['recorded_at']))) ?></td>
                    <td><?= number_format((float)$h['quantity'], 3) ?></td>
                    <td><?= e($h['unit']) ?></td>
                    <td><?= e($h['quality_grade'] ?? '—') ?></td>
                    <td><?= e($h['notes'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- FINANCE TAB -->
    <?php if (!empty($finances)): ?>
    <div class="tab-panel" id="tab-finance">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-4)">
            <h3 style="margin:0">Finance</h3>
            <a href="<?= url('/items/' . ((int)$item['id']) . '/finance') ?>" class="btn btn-secondary btn-sm">Full View</a>
        </div>
        <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Label</th><th>Amount</th></tr></thead>
            <tbody>
                <?php foreach ($finances as $f): ?>
                <tr>
                    <td><?= e($f['entry_date']) ?></td>
                    <td><span class="badge badge-<?= $f['entry_type'] === 'revenue' ? 'success' : 'warning' ?>"><?= e($f['entry_type']) ?></span></td>
                    <td><?= e($f['label']) ?></td>
                    <td><?= number_format((float)$f['amount'], 2) ?> <?= e($f['currency']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ACTIVITY LOG TAB -->
    <div class="tab-panel" id="tab-log">
        <h3 style="margin-bottom:var(--spacing-4)">Activity Log</h3>
        <?php if (empty($activityLog)): ?>
        <p class="text-muted">No activity recorded yet.</p>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Action</th><th>Description</th></tr></thead>
            <tbody>
                <?php foreach ($activityLog as $a): ?>
                <tr>
                    <td class="text-sm text-muted"><?= e(date('d M Y H:i', strtotime($a['performed_at']))) ?></td>
                    <td><span class="badge"><?= e($a['action_label']) ?></span></td>
                    <td><?= e($a['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<style>
/* =========================================================
   Item Show — Modern 2026
   ========================================================= */

/* Hero */
.show-hero {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: var(--spacing-5);
    box-shadow: 0 4px 32px rgba(0,0,0,0.14), 0 1px 4px rgba(0,0,0,0.08);
}

.show-hero-photo {
    width: 100%;
    height: 220px;
    position: relative;
    overflow: hidden;
    background: var(--item-color, #2d6a4f);
}
.show-hero-photo img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}
.show-hero-photo-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.55) 100%);
}
.show-hero-photo--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--item-color, #2d6a4f), color-mix(in srgb, var(--item-color, #2d6a4f) 60%, #000));
}
.show-hero-placeholder-emoji { font-size: 6rem; opacity: 0.5; }

@media (min-width: 600px) {
    .show-hero-photo, .show-hero-photo--placeholder { height: 280px; }
}

.show-hero-content {
    position: absolute;
    bottom: 72px; left: 0; right: 0;
    padding: 0 var(--spacing-4);
    color: #fff;
}
.show-hero-back { margin-bottom: var(--spacing-3); }
.show-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: rgba(255,255,255,0.85);
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    background: rgba(0,0,0,0.25);
    padding: 4px 10px;
    border-radius: var(--radius-pill);
    backdrop-filter: blur(8px);
}
.show-back-btn:hover { color: #fff; text-decoration: none; }

.show-hero-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    background: rgba(255,255,255,0.20);
    backdrop-filter: blur(8px);
    padding: 3px 10px;
    border-radius: var(--radius-pill);
    margin-bottom: 6px;
    color: #fff;
}
.show-hero-name {
    font-size: 1.9rem;
    font-weight: 900;
    color: #fff;
    margin: 0;
    letter-spacing: -0.03em;
    line-height: 1.1;
    text-shadow: 0 1px 8px rgba(0,0,0,0.3);
}
@media (max-width: 400px) { .show-hero-name { font-size: 1.5rem; } }
.show-hero-status {
    display: inline-block;
    background: rgba(220,38,38,0.85);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: var(--radius-pill);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 4px;
}

/* Action strip */
.show-hero-actions {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    display: flex;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-top: 1px solid rgba(255,255,255,0.3);
}
.show-action-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 10px 4px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-text-muted);
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
    border-right: 1px solid var(--color-border);
}
.show-action-btn:last-child { border-right: none; }
.show-action-btn:hover { background: var(--color-primary-soft); color: var(--color-primary); text-decoration: none; }
.show-action-btn--primary { color: var(--color-primary); font-weight: 800; }
.show-action-icon { font-size: 1.3rem; line-height: 1; }

/* Detail Grid */
.item-detail-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-5);
}
@media (min-width: 720px) { .item-detail-grid { grid-template-columns: 1fr 1fr; } }
.item-detail-left, .item-detail-right { display: flex; flex-direction: column; gap: var(--spacing-3); }

.item-detail-card {
    background: var(--color-surface-raised);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    box-shadow: var(--shadow-card);
}
.item-detail-card-title {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-text-muted);
    margin: 0 0 var(--spacing-3);
}

.item-dl { margin: 0; }
.item-dl-row {
    display: flex;
    gap: var(--spacing-3);
    padding: 9px 0;
    border-bottom: 1px solid var(--color-border);
    font-size: 0.875rem;
    align-items: baseline;
}
.item-dl-row:last-child { border-bottom: none; }
.item-dl-row dt { width: 90px; flex-shrink: 0; color: var(--color-text-muted); font-weight: 600; font-size: 0.78rem; }
.item-dl-row dd { flex: 1; min-width: 0; font-weight: 500; }

/* Tabs */
.item-tab-nav {
    display: flex;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    gap: 6px;
    padding-bottom: var(--spacing-3);
    border-bottom: 2px solid var(--color-border);
    margin-bottom: var(--spacing-4);
}
.item-tab-nav::-webkit-scrollbar { display: none; }
.item-tab-nav .tab-btn {
    white-space: nowrap;
    flex-shrink: 0;
    padding: 8px 16px;
    min-height: 38px;
    font-size: 0.82rem;
}
</style>

<script>
$('.tab-btn').on('click', function() {
    var tab = $(this).data('tab');
    $('.tab-btn').removeClass('tab-btn--active');
    $('.tab-panel').removeClass('tab-panel--active');
    $(this).addClass('tab-btn--active');
    $('#tab-' + tab).addClass('tab-panel--active');
});
</script>
