<?php
$typeEmoji = [
    'olive_tree'=>'🫒','tree'=>'🌳','vine'=>'🍇','almond_tree'=>'🌰',
    'garden'=>'🌿','zone'=>'🛖','orchard'=>'🏕','bed'=>'🌱','line'=>'〰️',
    'prep_zone'=>'🟫','mobile_coop'=>'🐓','building'=>'🏠','water_point'=>'💧',
];
// Items with GPS for JS sorting
$gpsItems = array_map(fn($i) => [
    'id'   => (int)$i['id'],
    'name' => $i['name'],
    'type' => $i['type'],
    'lat'  => $i['gps_lat'] ? (float)$i['gps_lat'] : null,
    'lng'  => $i['gps_lng'] ? (float)$i['gps_lng'] : null,
], $items);
?>
<div class="page-header">
    <h1 class="page-title">🔔 Reminders</h1>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- =========================================================
     NEW REMINDER FORM — GPS-sorted item picker
     ========================================================= -->
<div class="rem-new-card">
    <div class="rem-new-title">Add a Reminder</div>
    <form method="POST" action="<?= url('/reminders') ?>" class="rem-form" id="reminderForm">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

        <!-- Item picker (GPS-sorted) -->
        <div class="rem-field">
            <label class="rem-label">
                For which item?
                <span class="rem-gps-badge" id="remGpsBadge" style="display:none">📡 Sorted nearest first</span>
            </label>
            <select name="item_id" id="remItemSelect" class="rem-select">
                <option value="">— No specific item —</option>
                <?php foreach ($items as $i):
                    $emoji = $typeEmoji[$i['type']] ?? '📦';
                ?>
                <option value="<?= (int)$i['id'] ?>"
                        data-name="<?= e($i['name']) ?>"
                        data-emoji="<?= $emoji ?>"
                        data-lat="<?= $i['gps_lat'] ? e($i['gps_lat']) : '' ?>"
                        data-lng="<?= $i['gps_lng'] ? e($i['gps_lng']) : '' ?>">
                    <?= $emoji ?> <?= e($i['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Title + date on one row -->
        <div class="rem-row">
            <div class="rem-field rem-field--grow">
                <label class="rem-label">Reminder</label>
                <input type="text" name="title" class="rem-input"
                       placeholder="What to do?" required>
            </div>
            <div class="rem-field">
                <label class="rem-label">Due</label>
                <input type="datetime-local" name="due_at" class="rem-input" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full">Add Reminder</button>
    </form>
</div>

<!-- =========================================================
     OVERDUE
     ========================================================= -->
<?php
$_remPageCsrf = \App\Support\CSRF::getToken();
function _remRow(array $r, string $csrf, bool $isOD): string {
    $id    = (int)$r['id'];
    $title = e($r['title']);
    $meta  = e(date('d M Y', strtotime($r['due_at'])));
    $item  = !empty($r['item_name']) ? ' · <a href="' . url('/items/'.(int)$r['item_id']) . '" class="rem-item-link">' . e($r['item_name']) . ' →</a>' : '';
    $od    = $isOD ? 'rem-item--overdue' : '';
    return <<<HTML
    <div class="rem-item {$od}" id="remp-{$id}">
        <div class="rem-item-body">
            <div class="rem-item-title">{$title}</div>
            <div class="rem-item-meta">{$meta}{$item}</div>
        </div>
        <div class="rem-item-actions">
            <button class="rem-btn rem-btn--done"    title="Done"    onclick="rempAct({$id},'complete','{$csrf}')">✓</button>
            <button class="rem-btn rem-btn--snooze"  title="+1 Day"  onclick="rempAct({$id},'snooze1','{$csrf}')">+1d</button>
            <button class="rem-btn rem-btn--snooze"  title="+1 Week" onclick="rempAct({$id},'snooze7','{$csrf}')">+1w</button>
            <button class="rem-btn rem-btn--dismiss" title="Dismiss" onclick="rempAct({$id},'dismiss','{$csrf}')">✕</button>
        </div>
    </div>
    HTML;
}
?>
<?php if (!empty($overdue)): ?>
<div class="rem-section-title rem-section-title--danger">⚠️ Overdue (<?= count($overdue) ?>)</div>
<div class="rem-list">
    <?php foreach ($overdue as $r): ?>
    <?= _remRow($r, $_remPageCsrf, true) ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- =========================================================
     UPCOMING
     ========================================================= -->
<div class="rem-section-title">📅 Upcoming (<?= count($upcoming) ?>)</div>
<?php if (empty($upcoming)): ?>
<p class="text-muted" style="padding:var(--spacing-4) 0">No upcoming reminders.</p>
<?php else: ?>
<div class="rem-list">
    <?php foreach ($upcoming as $r): ?>
    <?= _remRow($r, $_remPageCsrf, false) ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function rempAct(id, action, token) {
    var url, body;
    if (action === 'snooze1' || action === 'snooze7') {
        url  = '<?= url('/reminders/') ?>' + id + '/snooze';
        body = '_token=' + encodeURIComponent(token) + '&days=' + (action === 'snooze7' ? 7 : 1) + '&_ajax=1';
    } else {
        url  = '<?= url('/reminders/') ?>' + id + '/' + action;
        body = '_token=' + encodeURIComponent(token) + '&_ajax=1';
    }
    fetch(url, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body:body })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var row = document.getElementById('remp-' + id);
            if (!row) return;
            if (action === 'snooze1' || action === 'snooze7') {
                if (data.due_at) {
                    var d = new Date(data.due_at.replace(' ','T'));
                    var label = d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
                    var meta = row.querySelector('.rem-item-meta');
                    if (meta) {
                        var itemLink = meta.querySelector('.rem-item-link');
                        meta.innerHTML = label + (itemLink ? ' · ' + itemLink.outerHTML : '');
                    }
                    row.classList.remove('rem-item--overdue');
                }
            } else {
                row.style.transition = 'opacity .3s';
                row.style.opacity = '0';
                setTimeout(function(){ row.remove(); }, 300);
            }
        });
}
</script>

<script>
(function() {
    var GPS_ITEMS = <?= json_encode($gpsItems) ?>;
    var sel   = document.getElementById('remItemSelect');
    var badge = document.getElementById('remGpsBadge');

    function hav(lat1,lon1,lat2,lon2){var R=6371000,d1=(lat2-lat1)*Math.PI/180,d2=(lon2-lon1)*Math.PI/180,a=Math.sin(d1/2)*Math.sin(d1/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(d2/2)*Math.sin(d2/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}
    function fmt(m){return m<1000?Math.round(m)+' m':(m/1000).toFixed(1)+' km';}

    function sortByDistance(pos) {
        var current = sel.value;
        // Collect all item options (skip the blank first one)
        var opts = Array.from(sel.querySelectorAll('option[data-lat]'));
        opts.forEach(function(opt) {
            var lat = parseFloat(opt.dataset.lat);
            var lng = parseFloat(opt.dataset.lng);
            if (lat && lng) {
                var d = hav(pos.lat, pos.lng, lat, lng);
                opt._dist = d;
                opt.textContent = opt.dataset.emoji + ' ' + opt.dataset.name + '  —  ' + fmt(d);
            } else {
                opt._dist = Infinity;
                opt.textContent = opt.dataset.emoji + ' ' + opt.dataset.name;
            }
        });
        opts.sort(function(a, b){ return a._dist - b._dist; });
        var blank = sel.querySelector('option[value=""]');
        // Rebuild
        while (sel.firstChild) sel.removeChild(sel.firstChild);
        if (blank) sel.appendChild(blank);
        opts.forEach(function(o){ sel.appendChild(o); });
        // Restore selection
        sel.value = current;
        badge.style.display = 'inline';
    }

    // Sort immediately if GPS warm
    var last = RootedGPS.last();
    if (last) {
        sortByDistance(last);
    } else {
        RootedGPS.get(function(pos) { if (pos) sortByDistance(pos); }, 20000);
    }

    // Re-sort as accuracy improves
    RootedGPS.onAccuracyImprove(function(pos) { sortByDistance(pos); });
}());
</script>

<style>
/* New reminder card */
.rem-new-card {
    background:var(--color-surface-raised);border-radius:18px;
    box-shadow:0 2px 12px rgba(0,0,0,.08);padding:var(--spacing-4);
    margin-bottom:var(--spacing-5);
}
.rem-new-title {
    font-size:.8rem;font-weight:800;text-transform:uppercase;
    letter-spacing:.07em;color:var(--color-text-muted);margin-bottom:var(--spacing-3);
}
.rem-form { display:flex;flex-direction:column;gap:var(--spacing-3); }
.rem-field { display:flex;flex-direction:column;gap:4px; }
.rem-field--grow { flex:1;min-width:0; }
.rem-row { display:flex;gap:var(--spacing-2);flex-wrap:wrap;align-items:flex-end; }
.rem-label { font-size:.75rem;font-weight:700;color:var(--color-text-muted);display:flex;align-items:center;gap:6px; }
.rem-gps-badge {
    font-size:.68rem;font-weight:700;padding:2px 8px;
    background:var(--color-primary-soft);color:var(--color-primary);
    border-radius:var(--radius-pill);
}
.rem-select, .rem-input {
    width:100%;padding:10px 14px;border:1.5px solid var(--color-border);
    border-radius:var(--radius-pill);font-size:.88rem;font-family:inherit;
    background:var(--color-surface);color:var(--color-text);
}
.rem-select:focus, .rem-input:focus { outline:none;border-color:var(--color-primary); }

/* Section titles */
.rem-section-title {
    font-size:.75rem;font-weight:800;text-transform:uppercase;
    letter-spacing:.07em;color:var(--color-text-muted);
    margin:var(--spacing-4) 0 var(--spacing-2);
}
.rem-section-title--danger { color:var(--color-danger,#c0392b); }

/* Reminder list */
.rem-list { display:flex;flex-direction:column;gap:6px; }
.rem-item {
    display:flex;align-items:center;gap:var(--spacing-3);
    padding:var(--spacing-3) var(--spacing-3);
    background:var(--color-surface-raised);border-radius:var(--radius-lg);
    border:1px solid var(--color-border);box-shadow:var(--shadow-sm);
}
.rem-item--overdue { border-left:3px solid var(--color-danger,#c0392b);background:#fff8f8; }
.rem-item-body { flex:1;min-width:0; }
.rem-item-title { font-size:.9rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.rem-item-meta { font-size:.72rem;color:var(--color-text-muted);margin-top:2px; }
.rem-item-link { color:var(--color-primary);font-weight:700;text-decoration:none; }
.rem-item-link:hover { text-decoration:underline; }
.rem-item-actions { display:flex;gap:4px;flex-shrink:0; }
.rem-btn {
    height:30px;border-radius:6px;border:none;cursor:pointer;
    font-weight:700;font-size:.8rem;padding:0 8px;transition:background .15s;white-space:nowrap;
}
.rem-btn--done { background:var(--color-primary-soft);color:var(--color-primary); }
.rem-btn--done:hover { background:var(--color-primary);color:#fff; }
.rem-btn--snooze { background:rgba(0,0,0,.06);color:var(--color-text-muted);font-size:.7rem; }
.rem-btn--snooze:hover { background:rgba(0,0,0,.13); }
.rem-btn--dismiss { background:rgba(0,0,0,.06);color:#c0392b; }
.rem-btn--dismiss:hover { background:#ffd5d5; }
</style>
