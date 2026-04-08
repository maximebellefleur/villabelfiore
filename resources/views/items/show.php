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

// Find identification photo for hero
$idPhoto = null;
foreach ($attachments as $att) {
    if ($att['category'] === 'identification_photo' && str_starts_with($att['mime_type'] ?? '', 'image/')) {
        $idPhoto = $att;
        break;
    }
}

// Photo preview: all image attachments, newest first, max 4
$imageAttachments = array_values(array_filter($attachments, fn($a) => str_starts_with($a['mime_type'] ?? '', 'image/')));
$previewPhotos    = array_slice($imageAttachments, 0, 4);
$totalPhotos      = count($imageAttachments);

// Recent activity: last 3 log entries
$recentLog = array_slice($activityLog, 0, 3);

// Pending reminders: first 3 (already sorted by due_at)
$recentReminders = array_slice($reminders, 0, 3);
?>

<!-- =========================================================
     HERO
     ========================================================= -->
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                Items
            </a>
            <a href="<?= url('/items/' . (int)$item['id'] . '/edit') ?>" class="show-edit-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                Edit
            </a>
        </div>
        <div class="show-hero-badge"><?= $emoji ?> <?= e($typeLabel) ?></div>
        <h1 class="show-hero-name"><?= e($item['name']) ?></h1>
        <?php if ($item['status'] !== 'active'): ?>
        <span class="show-hero-status"><?= e($item['status']) ?></span>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- =========================================================
     QUICK ACTIONS (add photo / reminder / note / ai)
     ========================================================= -->
<div class="show-quick-actions">
    <a href="<?= url('/items/' . (int)$item['id'] . '/photos') ?>" class="show-qa-btn">
        <span class="show-qa-icon">📷</span>
        <span class="show-qa-label">Add Photo</span>
    </a>
    <a href="#form-reminder" class="show-qa-btn" onclick="expandSection('reminder-section')">
        <span class="show-qa-icon">🔔</span>
        <span class="show-qa-label">Reminder</span>
    </a>
    <a href="#form-note" class="show-qa-btn" onclick="expandSection('note-section')">
        <span class="show-qa-icon">✏️</span>
        <span class="show-qa-label">Add Note</span>
    </a>
    <button class="show-qa-btn" id="aiPromptBtn"
            data-url="<?= url('/items/' . (int)$item['id'] . '/ai-prompt') ?>">
        <span class="show-qa-icon" id="aiPromptIcon">🤖</span>
        <span class="show-qa-label" id="aiPromptLabel">Copy AI</span>
    </button>
</div>

<!-- =========================================================
     PHOTO PREVIEW STRIP
     ========================================================= -->
<?php if (!empty($previewPhotos)): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">📷 Photos</span>
        <a href="<?= url('/items/' . (int)$item['id'] . '/photos') ?>" class="show-section-link">
            <?= $totalPhotos ?> photo<?= $totalPhotos !== 1 ? 's' : '' ?> — See all →
        </a>
    </div>
    <div class="show-photo-grid show-photo-grid--<?= min(count($previewPhotos), 4) ?>">
        <?php foreach ($previewPhotos as $i => $att): ?>
        <a href="<?= url('/items/' . (int)$item['id'] . '/photos') ?>" class="show-photo-thumb">
            <img src="<?= url('/attachments/' . (int)$att['id'] . '/download') ?>"
                 alt="" loading="lazy">
            <?php if ($i === 3 && $totalPhotos > 4): ?>
            <div class="show-photo-more">+<?= $totalPhotos - 4 ?></div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     RECENT ACTIVITY + REMINDERS
     ========================================================= -->
<?php if (!empty($recentLog) || !empty($recentReminders)): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">📋 Recent Activity</span>
        <a href="#full-log" class="show-section-link" onclick="expandSection('log-section')">See all →</a>
    </div>
    <div class="show-activity-feed">
        <?php foreach ($recentReminders as $r):
            $overdue = strtotime($r['due_at']) < time();
        ?>
        <div class="show-feed-item show-feed-item--reminder <?= $overdue ? 'show-feed-item--overdue' : '' ?>">
            <span class="show-feed-icon">🔔</span>
            <div class="show-feed-body">
                <div class="show-feed-text"><?= e($r['title']) ?></div>
                <div class="show-feed-date"><?= $overdue ? '⚠️ Overdue · ' : '' ?><?= e(date('d M Y', strtotime($r['due_at']))) ?></div>
            </div>
            <form method="POST" action="<?= url('/reminders/' . (int)$r['id'] . '/complete') ?>" class="show-feed-action">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="show-feed-done" title="Mark done">✓</button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php foreach ($recentLog as $a): ?>
        <div class="show-feed-item">
            <span class="show-feed-icon">
                <?php
                $actionIcons = ['note'=>'📝','pruning'=>'✂️','treatment'=>'💊','amendment'=>'🌿','harvest'=>'🌾','maintenance'=>'🔧'];
                echo $actionIcons[$a['action_label'] ?? ''] ?? '📋';
                ?>
            </span>
            <div class="show-feed-body">
                <div class="show-feed-text"><?= e($a['description']) ?></div>
                <div class="show-feed-date"><?= e($a['action_label']) ?> · <?= e(date('d M Y', strtotime($a['performed_at']))) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     DETAILS
     ========================================================= -->
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">ℹ️ Details</span>
        <?php if (!empty($item['gps_lat'])): ?>
        <a href="<?= url('/dashboard/map') ?>" class="show-section-link">📍 View on map →</a>
        <?php endif; ?>
    </div>
    <div class="item-detail-card">
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
                <dd><a href="<?= url('/items/' . (int)$item['parent_id']) ?>">View parent →</a></dd>
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
            <?php foreach ($meta as $key => $value): ?>
            <div class="item-dl-row">
                <dt><?= e(ucwords(str_replace('_', ' ', $key))) ?></dt>
                <dd><?= e($value) ?></dd>
            </div>
            <?php endforeach; ?>
        </dl>
    </div>
</div>

<!-- =========================================================
     LOCATION MAP
     ========================================================= -->
<?php if ($miniMapEnabled): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">📍 Location</span>
    </div>
    <div id="miniMap" style="height:200px;border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--color-border)"></div>
</div>
<script>
window.MINI_MAP_LAT = <?= (float)$item['gps_lat'] ?>;
window.MINI_MAP_LNG = <?= (float)$item['gps_lng'] ?>;
window.MINI_MAP_READONLY = true;
</script>
<?php endif; ?>

<!-- =========================================================
     ADD REMINDER FORM (anchor target)
     ========================================================= -->
<div class="show-section" id="reminder-section">
    <div class="show-section-head">
        <span class="show-section-title">🔔 Add Reminder</span>
    </div>
    <div class="item-detail-card" id="form-reminder">
        <form method="POST" action="<?= url('/reminders') ?>">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
            <div class="show-reminder-for-badge">
                <?= $emoji ?> <?= e($item['name']) ?>
            </div>
            <div class="show-form-row">
                <input type="text" name="title" class="form-input form-input--touch"
                       placeholder="What to do?" required style="flex:1">
                <input type="datetime-local" name="due_at" class="form-input form-input--touch"
                       required style="flex:1">
                <button type="submit" class="btn btn-secondary">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     ADD NOTE FORM (anchor target)
     ========================================================= -->
<div class="show-section" id="note-section">
    <div class="show-section-head">
        <span class="show-section-title">✏️ Log Note or Action</span>
    </div>
    <div class="item-detail-card" id="form-note">
        <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/actions') ?>">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="show-form-row">
                <select name="action_type" class="form-input form-input--touch" style="flex-shrink:0">
                    <option value="note">Note</option>
                    <option value="pruning">Pruning</option>
                    <option value="treatment">Treatment</option>
                    <option value="amendment">Amendment</option>
                    <option value="harvest">Harvest</option>
                    <option value="maintenance">Maintenance</option>
                </select>
                <input type="text" name="description" class="form-input form-input--touch"
                       placeholder="Description (required)" required style="flex:1">
                <button type="submit" class="btn btn-primary">Log</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     HARVESTS (if any)
     ========================================================= -->
<?php if (!empty($harvests)): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">🌾 Harvests</span>
    </div>
    <div class="item-detail-card">
        <?php
        $harvestTotals = [];
        foreach ($harvests as $h) {
            $u = $h['unit'] ?? 'units';
            $harvestTotals[$u] = ($harvestTotals[$u] ?? 0) + (float)$h['quantity'];
        }
        ?>
        <div style="margin-bottom:var(--spacing-3);font-size:.85rem;color:var(--color-text-muted)">
            Total: <strong><?= implode(', ', array_map(fn($u,$q)=>number_format($q,2).' '.$u, array_keys($harvestTotals), $harvestTotals)) ?></strong>
        </div>
        <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/harvests') ?>" class="show-form-row" style="margin-bottom:var(--spacing-3)">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <input type="number" step="0.001" name="quantity" class="form-input form-input--sm" placeholder="Qty" required style="width:80px">
            <input type="text" name="unit" class="form-input form-input--sm" placeholder="Unit" required style="width:80px">
            <input type="datetime-local" name="recorded_at" class="form-input form-input--sm" required style="flex:1">
            <button type="submit" class="btn btn-primary btn-sm">Record</button>
        </form>
        <table class="table" style="font-size:.82rem">
            <thead><tr><th>Date</th><th>Qty</th><th>Unit</th><th>Grade</th></tr></thead>
            <tbody>
                <?php foreach ($harvests as $h): ?>
                <tr>
                    <td><?= e(date('d M Y', strtotime($h['recorded_at']))) ?></td>
                    <td><?= number_format((float)$h['quantity'], 2) ?></td>
                    <td><?= e($h['unit']) ?></td>
                    <td><?= e($h['quality_grade'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     FINANCE (if any)
     ========================================================= -->
<?php if (!empty($finances)): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">💰 Finance</span>
        <a href="<?= url('/items/' . (int)$item['id'] . '/finance') ?>" class="show-section-link">Full view →</a>
    </div>
    <div class="item-detail-card">
        <table class="table" style="font-size:.82rem">
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
</div>
<?php endif; ?>

<!-- =========================================================
     FULL ACTIVITY LOG
     ========================================================= -->
<div class="show-section" id="log-section">
    <div class="show-section-head">
        <span class="show-section-title" id="full-log">📋 Activity Log</span>
    </div>
    <div class="item-detail-card">
        <?php if (empty($activityLog)): ?>
        <p class="text-muted">No activity recorded yet.</p>
        <?php else: ?>
        <?php $logCsrf = e(\App\Support\CSRF::getToken()); ?>
        <table class="table show-log-table">
            <thead><tr><th>Date</th><th>Action</th><th>Description</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($activityLog as $a):
                    $logId = (int)$a['id'];
                ?>
                <tr id="logrow_<?= $logId ?>">
                    <td class="text-sm text-muted" style="white-space:nowrap"><?= e(date('d M Y', strtotime($a['performed_at']))) ?></td>
                    <td><span class="badge"><?= e($a['action_label']) ?></span></td>
                    <td class="show-log-desc"><?= e($a['description']) ?></td>
                    <td class="show-log-del-cell">
                        <button type="button" class="show-log-del-btn" data-log-id="<?= $logId ?>" title="Delete">✕</button>
                        <span class="show-log-del-confirm" id="logdel_<?= $logId ?>" style="display:none">
                            <form method="POST" action="<?= url('/activity-log/' . $logId . '/trash') ?>" style="display:inline">
                                <input type="hidden" name="_token" value="<?= $logCsrf ?>">
                                <button type="submit" class="show-log-del-yes">✓</button>
                            </form>
                            <button type="button" class="show-log-del-no" data-log-id="<?= $logId ?>">✕</button>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<style>
.show-log-table { font-size:.82rem; width:100%; }
.show-log-desc { word-break:break-word; overflow-wrap:anywhere; max-width:180px; }
.show-log-del-cell { white-space:nowrap; text-align:right; padding-right:4px; }
.show-log-del-btn { background:none;border:none;cursor:pointer;color:var(--color-text-muted);font-size:.8rem;padding:2px 6px;border-radius:4px;opacity:.5;transition:opacity .15s,color .15s; }
.show-log-del-btn:hover { opacity:1;color:var(--color-danger,#c0392b); }
.show-log-del-confirm { display:inline-flex;align-items:center;gap:3px; }
.show-log-del-yes { background:var(--color-danger,#c0392b);color:#fff;border:none;border-radius:4px;font-size:.72rem;font-weight:700;padding:2px 7px;cursor:pointer; }
.show-log-del-no  { background:var(--color-border);color:var(--color-text-muted);border:none;border-radius:4px;font-size:.72rem;font-weight:700;padding:2px 7px;cursor:pointer; }
</style>
<script>
// Log delete inline confirm
document.querySelectorAll('.show-log-del-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.logId;
        btn.style.display = 'none';
        document.getElementById('logdel_' + id).style.display = 'inline-flex';
    });
});
document.querySelectorAll('.show-log-del-no').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.logId;
        document.getElementById('logdel_' + id).style.display = 'none';
        document.querySelector('.show-log-del-btn[data-log-id="' + id + '"]').style.display = '';
    });
});

// AI Prompt
(function() {
    var btn = document.getElementById('aiPromptBtn');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var icon  = document.getElementById('aiPromptIcon');
        var label = document.getElementById('aiPromptLabel');
        icon.textContent = '⏳'; label.textContent = 'Building…';
        fetch(btn.dataset.url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.prompt) throw new Error('empty');
                return navigator.clipboard.writeText(data.prompt);
            })
            .then(function() {
                icon.textContent = '✅'; label.textContent = 'Copied!';
                setTimeout(function() {
                    icon.textContent = '🤖'; label.textContent = 'Copy AI';
                }, 2500);
            })
            .catch(function() {
                icon.textContent = '❌'; label.textContent = 'Error';
                setTimeout(function() {
                    icon.textContent = '🤖'; label.textContent = 'Copy AI';
                }, 2000);
            });
    });
}());

// Scroll to anchor sections
function expandSection(id) {
    setTimeout(function() {
        var el = document.getElementById(id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 50);
}
</script>

<style>
/* =========================================================
   Item Show — v1.4.15
   ========================================================= */

/* Hero */
.show-hero {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: var(--spacing-4);
    box-shadow: 0 4px 32px rgba(0,0,0,0.14), 0 1px 4px rgba(0,0,0,0.08);
}
.show-hero-photo {
    width: 100%; height: 200px;
    position: relative; overflow: hidden;
    background: var(--item-color, #2d6a4f);
}
.show-hero-photo img { width:100%;height:100%;object-fit:cover;display:block; }
.show-hero-photo-overlay { position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.1) 0%,rgba(0,0,0,.55) 100%); }
.show-hero-photo--placeholder { display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--item-color,#2d6a4f),color-mix(in srgb,var(--item-color,#2d6a4f) 60%,#000)); }
.show-hero-placeholder-emoji { font-size:5rem;opacity:.45; }
@media(min-width:600px){.show-hero-photo,.show-hero-photo--placeholder{height:240px;}}

.show-hero-content { position:absolute;bottom:0;left:0;right:0;padding:var(--spacing-3) var(--spacing-4) var(--spacing-4);color:#fff; }
.show-hero-back { display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3); }
.show-back-btn {
    display:inline-flex;align-items:center;gap:4px;color:rgba(255,255,255,.85);
    font-size:.78rem;font-weight:600;text-decoration:none;
    background:rgba(0,0,0,.25);padding:4px 10px;border-radius:var(--radius-pill);
    backdrop-filter:blur(8px);
}
.show-back-btn:hover{color:#fff;text-decoration:none;}
.show-edit-btn {
    display:inline-flex;align-items:center;gap:4px;color:rgba(255,255,255,.85);
    font-size:.78rem;font-weight:600;text-decoration:none;
    background:rgba(0,0,0,.25);padding:4px 10px;border-radius:var(--radius-pill);
    backdrop-filter:blur(8px);
}
.show-edit-btn:hover{color:#fff;text-decoration:none;}
.show-hero-badge {
    display:inline-flex;align-items:center;font-size:.7rem;font-weight:800;
    text-transform:uppercase;letter-spacing:.08em;background:rgba(255,255,255,.2);
    backdrop-filter:blur(8px);padding:3px 10px;border-radius:var(--radius-pill);
    margin-bottom:5px;color:#fff;
}
.show-hero-name {
    font-size:1.8rem;font-weight:900;color:#fff;margin:0;
    letter-spacing:-.03em;line-height:1.1;text-shadow:0 1px 8px rgba(0,0,0,.3);
}
@media(max-width:380px){.show-hero-name{font-size:1.4rem;}}
.show-hero-status {
    display:inline-block;background:rgba(220,38,38,.85);color:#fff;
    font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);
    text-transform:uppercase;letter-spacing:.05em;margin-top:4px;
}

/* Quick actions */
.show-quick-actions {
    display:grid;grid-template-columns:repeat(4,1fr);gap:var(--spacing-2);
    margin-bottom:var(--spacing-4);
}
.show-qa-btn {
    display:flex;flex-direction:column;align-items:center;gap:4px;
    padding:12px 6px;border-radius:var(--radius-lg);
    background:var(--color-surface-raised);border:1.5px solid var(--color-border);
    color:var(--color-text);text-decoration:none;cursor:pointer;
    font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
    transition:border-color .15s,background .15s,color .15s;
    box-shadow:var(--shadow-sm);font-family:inherit;
}
.show-qa-btn:hover{border-color:var(--color-primary);background:var(--color-primary-soft);color:var(--color-primary);text-decoration:none;}
.show-qa-icon{font-size:1.5rem;line-height:1;}

/* Sections */
.show-section { margin-bottom:var(--spacing-4); }
.show-section-head {
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:var(--spacing-2);
}
.show-section-title { font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted); }
.show-section-link { font-size:.78rem;font-weight:700;color:var(--color-primary);text-decoration:none; }
.show-section-link:hover{text-decoration:underline;}

/* Photo grid */
.show-photo-grid {
    display:grid;gap:4px;border-radius:var(--radius-lg);overflow:hidden;
}
.show-photo-grid--1{grid-template-columns:1fr;}
.show-photo-grid--2{grid-template-columns:repeat(2,1fr);}
.show-photo-grid--3{grid-template-columns:repeat(2,1fr);grid-template-rows:auto auto;}
.show-photo-grid--3 .show-photo-thumb:first-child{grid-column:1/-1;}
.show-photo-grid--4{grid-template-columns:repeat(2,1fr);}
.show-photo-thumb {
    position:relative;aspect-ratio:1;overflow:hidden;display:block;
    background:var(--color-surface);
}
.show-photo-thumb img { width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s; }
.show-photo-thumb:hover img { transform:scale(1.04); }
.show-photo-more {
    position:absolute;inset:0;background:rgba(0,0,0,.55);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:1.4rem;font-weight:800;
}

/* Activity feed */
.show-activity-feed { display:flex;flex-direction:column;gap:2px; }
.show-feed-item {
    display:flex;align-items:center;gap:var(--spacing-3);
    padding:var(--spacing-2) var(--spacing-3);
    background:var(--color-surface-raised);border-radius:var(--radius);
    border:1px solid var(--color-border);
}
.show-feed-item--reminder { border-left:3px solid var(--color-primary); }
.show-feed-item--overdue  { border-left:3px solid var(--color-danger,#c0392b);background:#fff8f8; }
.show-feed-icon { font-size:1.2rem;flex-shrink:0; }
.show-feed-body { flex:1;min-width:0; }
.show-feed-text { font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.show-feed-date { font-size:.72rem;color:var(--color-text-muted);margin-top:1px; }
.show-feed-action { flex-shrink:0; }
.show-feed-done {
    width:28px;height:28px;border-radius:50%;background:var(--color-primary-soft);
    border:1.5px solid var(--color-primary);color:var(--color-primary);
    cursor:pointer;font-size:.9rem;font-weight:800;
    display:flex;align-items:center;justify-content:center;
    transition:background .15s;
}
.show-feed-done:hover{background:var(--color-primary);color:#fff;}

/* Detail card */
.item-detail-card {
    background:var(--color-surface-raised);border-radius:var(--radius-lg);
    padding:var(--spacing-3) var(--spacing-4);box-shadow:var(--shadow-card);
}
.item-dl { margin:0; }
.item-dl-row {
    display:flex;gap:var(--spacing-3);padding:8px 0;
    border-bottom:1px solid var(--color-border);
    font-size:.875rem;align-items:baseline;
}
.item-dl-row:last-child{border-bottom:none;}
.item-dl-row dt{width:90px;flex-shrink:0;color:var(--color-text-muted);font-weight:600;font-size:.78rem;}
.item-dl-row dd{flex:1;min-width:0;font-weight:500;}

/* Inline forms */
.show-form-row { display:flex;gap:var(--spacing-2);flex-wrap:wrap;align-items:center; }
.show-reminder-for-badge {
    display:inline-flex;align-items:center;gap:6px;
    padding:5px 12px;border-radius:var(--radius-pill);
    background:var(--color-primary-soft);color:var(--color-primary);
    font-size:.78rem;font-weight:700;margin-bottom:var(--spacing-2);
}
</style>
