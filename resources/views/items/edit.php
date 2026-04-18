<?php $miniMapEnabled = true; ?>
<div class="page-header">
    <h1 class="page-title">Edit — <?= e($item['name']) ?></h1>
    <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<form method="POST" action="<?= url('/items/' . ((int)$item['id']) . '/update') ?>" class="form item-form-mobile" id="itemForm">
    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
    <input type="hidden" name="type"   value="<?= e($item['type']) ?>">
    <input type="hidden" name="gps_source" id="gpsSource" value="<?= e($item['gps_source'] ?? 'manual') ?>">
    <input type="hidden" name="gps_lat"    id="gpsLat"    value="<?= e($item['gps_lat'] ?? '') ?>">
    <input type="hidden" name="gps_lng"    id="gpsLng"    value="<?= e($item['gps_lng'] ?? '') ?>">
    <input type="hidden" name="gps_accuracy" id="gpsAccuracy" value="<?= e($item['gps_accuracy'] ?? '') ?>">

    <!-- Map — tap to move pin -->
    <div class="item-form-map-block">
        <div class="item-form-map-label">
            <span>📍 Tap the map to move location</span>
            <button type="button" class="btn btn-primary btn-sm" id="detectGps">📍 Locate Me</button>
        </div>
        <div id="miniMap" class="item-form-map"></div>
        <div id="gpsStatus" class="item-form-gps-status" style="display:none"></div>
    </div>

    <!-- Core fields -->
    <div class="card item-form-fields">
        <div class="card-body">

            <div class="form-group">
                <label class="form-label">Type</label>
                <input type="text" class="form-input" value="<?= e(str_replace('_', ' ', ucwords($item['type'], '_'))) ?>" readonly disabled>
            </div>

            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-input form-input--touch" required value="<?= e($item['name']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Parent Item <small class="text-muted">(optional)</small></label>
                <input type="number" name="parent_id" class="form-input form-input--touch" placeholder="Parent item ID"
                       value="<?= e($item['parent_id'] ?? '') ?>">
            </div>

            <!-- Coordinates display -->
            <div class="gps-coords-display" id="gpsCoordsDisplay" style="<?= ($item['gps_lat'] ?? '') ? '' : 'display:none' ?>">
                <span class="gps-coords-icon">📍</span>
                <span id="gpsCoordsText"></span>
                <button type="button" class="btn btn-link btn-sm" id="clearGps">Clear</button>
            </div>

            <!-- Type-specific meta fields (same as create, auto-populated) -->
            <div id="metaFields" style="display:none">
                <hr class="form-divider">
                <div class="form-group-label">Type Details</div>
                <div id="metaFieldsInner"></div>
            </div>

        </div>
    </div>

    <div class="item-form-submit">
        <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
        <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="btn btn-secondary btn-full">Cancel</a>
    </div>

</form>

<?php if (in_array($item['type'], $boundaryTypes ?? [])): ?>
<!-- Boundary walk section (outside the main form — saved independently via AJAX) -->
<div class="card item-form-fields" style="margin-top:var(--spacing-3)">
    <div class="card-body">
        <div class="form-group-label" style="margin-bottom:var(--spacing-2)">📐 Boundary</div>

        <div id="editBoundaryStatus" class="text-sm" style="margin-bottom:var(--spacing-2);color:var(--color-text-muted)">
            <?= $boundaryGeojson ? '⬡ Boundary saved' : 'No boundary set yet' ?>
        </div>

        <div class="walk-mode-block" id="editWalkBlock">
            <button type="button" id="editWalkBtn" class="btn btn-primary btn-sm walk-btn">🚶 Walk the Boundary</button>
            <p class="text-muted text-sm" style="margin:4px 0 0">Walk around the zone — GPS records the path automatically.</p>
            <div id="editWalkActive" style="display:none;margin-top:6px">
                <div id="editWalkStats" class="walk-stats"></div>
                <button type="button" id="editWalkStopBtn" class="btn btn-danger btn-sm" style="width:100%;margin-top:6px">⏹ Stop &amp; Use This Path</button>
            </div>
        </div>

        <div id="editBoundaryActions" style="display:none;margin-top:var(--spacing-2);display:flex;gap:var(--spacing-2)">
            <button type="button" id="editBoundarySave" class="btn btn-primary btn-sm">💾 Save Boundary</button>
            <button type="button" id="editBoundaryDiscard" class="btn btn-secondary btn-sm">✕ Discard</button>
        </div>

        <?php if ($boundaryGeojson): ?>
        <div style="margin-top:var(--spacing-2)" id="editBoundaryDeleteRow">
            <button type="button" id="editBoundaryDelete" class="btn btn-link btn-sm" style="color:var(--color-danger,#c0392b);padding:0">Remove boundary</button>
            <span id="editBoundaryDeleteConfirm" style="display:none;gap:6px;align-items:center">
                <button type="button" id="editBoundaryDeleteYes" class="btn btn-danger btn-sm">✓ Remove</button>
                <button type="button" id="editBoundaryDeleteNo" class="btn btn-secondary btn-sm">Cancel</button>
            </span>
        </div>
        <?php endif; ?>

        <div id="editBoundaryMsg" class="text-sm" style="margin-top:6px"></div>
    </div>
</div>

<script>
(function () {
    var CSRF          = <?= json_encode(\App\Support\CSRF::getToken()) ?>;
    var SAVE_URL      = <?= json_encode(url('/api/map/boundary/' . (int)$item['id'])) ?>;
    var DELETE_URL    = <?= json_encode(url('/api/map/boundary/' . (int)$item['id'] . '/delete')) ?>;
    var EXISTING      = <?= $boundaryGeojson ? $boundaryGeojson : 'null' ?>;

    var WALK_MIN_DIST_M  = 1.5;
    var WALK_MAX_ACC_M   = 25;
    var WALK_SIMPLIFY_M  = 0.8;

    var walkActive   = false;
    var walkUnsub    = null;
    var walkPts      = [];
    var walkDist     = 0;
    var walkLastLat  = null;
    var walkLastLng  = null;
    var walkPolyline = null;
    var walkPolygon  = null;
    var boundaryLayer= null;
    var pendingPts   = null; // simplified points waiting to be saved

    var statusEl  = document.getElementById('editBoundaryStatus');
    var statsEl   = document.getElementById('editWalkStats');
    var activeEl  = document.getElementById('editWalkActive');
    var actionsEl = document.getElementById('editBoundaryActions');
    var msgEl     = document.getElementById('editBoundaryMsg');

    function mm() { return window.miniMapLeaflet || null; }

    function drawBoundary(geojson) {
        var m = mm(); if (!m) return;
        if (boundaryLayer) { m.removeLayer(boundaryLayer); boundaryLayer = null; }
        if (!geojson) return;
        try {
            var parsed = typeof geojson === 'string' ? JSON.parse(geojson) : geojson;
            boundaryLayer = L.geoJSON(parsed, {
                style: { color: '#3c8dbc', weight: 2, fillColor: '#3c8dbc', fillOpacity: 0.1 }
            }).addTo(m);
            m.fitBounds(boundaryLayer.getBounds(), { padding: [20, 20] });
        } catch (e) {}
    }

    // Show existing boundary on mini-map after it initialises
    if (EXISTING) { setTimeout(function () { drawBoundary(EXISTING); }, 350); }

    // ── Walk ──────────────────────────────────────────────────────────────────
    document.getElementById('editWalkBtn').addEventListener('click', startWalk);
    document.getElementById('editWalkStopBtn').addEventListener('click', function () { stopWalk(true); });

    function startWalk() {
        var m = mm();
        if (!navigator.geolocation) { if (msgEl) msgEl.textContent = '⚠️ Geolocation not available.'; return; }
        walkActive  = true;
        walkPts     = [];
        walkDist    = 0;
        walkLastLat = null;
        walkLastLng = null;
        pendingPts  = null;

        if (m) {
            if (walkPolyline) { m.removeLayer(walkPolyline); walkPolyline = null; }
            if (walkPolygon)  { m.removeLayer(walkPolygon);  walkPolygon  = null; }
        }

        document.getElementById('editWalkBtn').style.display = 'none';
        activeEl.style.display  = 'block';
        actionsEl.style.display = 'none';
        statsEl.innerHTML       = '<span class="walk-recording-dot"></span> Waiting for GPS fix…';
        if (msgEl) msgEl.textContent = '';

        if ('wakeLock' in navigator) { navigator.wakeLock.request('screen').catch(function () {}); }

        walkUnsub = window.RootedGPS
            ? RootedGPS.subscribe(onWalkPos)
            : (function () {
                var wid = navigator.geolocation.watchPosition(
                    function (p) { onWalkPos({ lat: p.coords.latitude, lng: p.coords.longitude, accuracy: p.coords.accuracy }); },
                    function () {},
                    { enableHighAccuracy: true, maximumAge: 0 }
                );
                return function () { navigator.geolocation.clearWatch(wid); };
            })();
    }

    function onWalkPos(pos) {
        if (!walkActive) return;
        if (!pos) { stopWalk(false); if (msgEl) msgEl.textContent = '🔒 GPS denied — enable Location in browser settings.'; return; }
        var lat = pos.lat, lng = pos.lng, acc = pos.accuracy;

        if (acc > WALK_MAX_ACC_M) {
            statsEl.innerHTML = '<span class="walk-recording-dot"></span>⚠️ Weak signal ±' + Math.round(acc) + ' m — move to open sky';
            return;
        }
        if (walkLastLat !== null) {
            var d = haversine(walkLastLat, walkLastLng, lat, lng);
            if (d < WALK_MIN_DIST_M) {
                statsEl.innerHTML = '<span class="walk-recording-dot"></span>' + walkPts.length + ' pts · ' + Math.round(walkDist) + ' m · ±' + Math.round(acc) + ' m';
                return;
            }
            walkDist += d;
        }
        walkLastLat = lat; walkLastLng = lng;
        walkPts.push([lat, lng]);

        var m = mm();
        if (m) {
            if (walkPolyline) { walkPolyline.setLatLngs(walkPts); }
            else { walkPolyline = L.polyline(walkPts, { color: '#3c8dbc', weight: 3, opacity: 0.85 }).addTo(m); }
            m.panTo([lat, lng]);
        }
        statsEl.innerHTML = '<span class="walk-recording-dot"></span>' + walkPts.length + ' pts · ' +
            (walkDist >= 1000 ? (walkDist / 1000).toFixed(2) + ' km' : Math.round(walkDist) + ' m') +
            ' · ±' + Math.round(acc) + ' m';
    }

    function stopWalk(usePath) {
        walkActive = false;
        if (walkUnsub) { walkUnsub(); walkUnsub = null; }

        document.getElementById('editWalkBtn').style.display = '';
        activeEl.style.display = 'none';
        statsEl.innerHTML      = '';

        var m = mm();
        if (walkPolyline && m) { m.removeLayer(walkPolyline); walkPolyline = null; }

        if (!usePath || walkPts.length < 3) {
            if (usePath && msgEl) msgEl.textContent = '⚠️ Not enough points (' + walkPts.length + '). Walk further before stopping.';
            return;
        }
        pendingPts = rdp(walkPts, WALK_SIMPLIFY_M);
        if (m) {
            if (walkPolygon) m.removeLayer(walkPolygon);
            walkPolygon = L.polygon(pendingPts, { color: '#3c8dbc', fillColor: '#3c8dbc', fillOpacity: 0.15, weight: 2 }).addTo(m);
            m.fitBounds(walkPolygon.getBounds(), { padding: [20, 20] });
        }
        if (statusEl) statusEl.textContent = '✅ ' + pendingPts.length + ' points · ' + Math.round(walkDist) + ' m — tap Save to confirm';
        actionsEl.style.display = 'flex';
        if (msgEl) msgEl.textContent = '';
    }

    // ── Save ─────────────────────────────────────────────────────────────────
    document.getElementById('editBoundarySave').addEventListener('click', function () {
        if (!pendingPts || pendingPts.length < 3) { if (msgEl) msgEl.textContent = '⚠️ Walk a boundary first.'; return; }
        var geojson = { type: 'Polygon', coordinates: [pendingPts.map(function (p) { return [p[1], p[0]]; })] };
        geojson.coordinates[0].push(geojson.coordinates[0][0]);
        if (msgEl) msgEl.textContent = 'Saving…';
        var fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('geojson', JSON.stringify(geojson));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', SAVE_URL, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', function () {
            var res; try { res = JSON.parse(xhr.responseText); } catch (e) {}
            if (res && res.success) {
                EXISTING = geojson;
                if (walkPolygon) { var m = mm(); if (m) m.removeLayer(walkPolygon); walkPolygon = null; }
                drawBoundary(geojson);
                actionsEl.style.display = 'none';
                pendingPts = null;
                if (statusEl) statusEl.textContent = '⬡ Boundary saved';
                if (msgEl) msgEl.textContent = '✅ ' + (res.message || 'Saved');
                // Show delete button if it was missing (first-time save)
                var delRow = document.getElementById('editBoundaryDeleteRow');
                if (!delRow) {
                    var row = document.createElement('div');
                    row.id = 'editBoundaryDeleteRow';
                    row.style.marginTop = 'var(--spacing-2)';
                    row.innerHTML = '<button type="button" id="editBoundaryDelete" class="btn btn-link btn-sm" style="color:var(--color-danger,#c0392b);padding:0">Remove boundary</button>'
                        + '<span id="editBoundaryDeleteConfirm" style="display:none;gap:6px;align-items:center">'
                        + '<button type="button" id="editBoundaryDeleteYes" class="btn btn-danger btn-sm">✓ Remove</button>'
                        + '<button type="button" id="editBoundaryDeleteNo" class="btn btn-secondary btn-sm">Cancel</button></span>';
                    msgEl.parentNode.insertBefore(row, msgEl);
                    wireDeleteBtn();
                }
            } else {
                if (msgEl) msgEl.textContent = '❌ ' + (res ? res.message : 'Save failed');
            }
        });
        xhr.send(fd);
    });

    // ── Discard ───────────────────────────────────────────────────────────────
    document.getElementById('editBoundaryDiscard').addEventListener('click', function () {
        var m = mm();
        if (walkPolygon && m) { m.removeLayer(walkPolygon); walkPolygon = null; }
        actionsEl.style.display = 'none';
        pendingPts = null;
        if (msgEl) msgEl.textContent = '';
        if (EXISTING) { drawBoundary(EXISTING); if (statusEl) statusEl.textContent = '⬡ Boundary saved'; }
        else if (statusEl) statusEl.textContent = 'No boundary set yet';
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    function wireDeleteBtn() {
        var btn = document.getElementById('editBoundaryDelete');
        var conf = document.getElementById('editBoundaryDeleteConfirm');
        var yes  = document.getElementById('editBoundaryDeleteYes');
        var no   = document.getElementById('editBoundaryDeleteNo');
        if (!btn || !conf) return;
        btn.addEventListener('click', function () { btn.style.display = 'none'; conf.style.display = 'flex'; });
        no.addEventListener('click',  function () { conf.style.display = 'none'; btn.style.display = ''; });
        yes.addEventListener('click', function () {
            yes.textContent = '…';
            var fd = new FormData(); fd.append('_token', CSRF);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', DELETE_URL, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.addEventListener('load', function () {
                var res; try { res = JSON.parse(xhr.responseText); } catch (e) {}
                if (res && res.success) {
                    var m = mm();
                    if (boundaryLayer && m) { m.removeLayer(boundaryLayer); boundaryLayer = null; }
                    document.getElementById('editBoundaryDeleteRow').style.display = 'none';
                    if (statusEl) statusEl.textContent = 'No boundary set yet';
                    if (msgEl) msgEl.textContent = '✅ Boundary removed.';
                    EXISTING = null;
                } else { yes.textContent = '✓ Remove'; conf.style.display = 'none'; btn.style.display = ''; }
            });
            xhr.send(fd);
        });
    }
    wireDeleteBtn();

    // ── Geo utilities ─────────────────────────────────────────────────────────
    function haversine(lat1, lng1, lat2, lng2) {
        var R = 6371000, dLat = (lat2-lat1)*Math.PI/180, dLng = (lng2-lng1)*Math.PI/180;
        var a = Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }
    function rdp(pts, tol) {
        if (pts.length < 3) return pts.slice();
        var dmax = 0, idx = 0, a = pts[0], b = pts[pts.length-1];
        for (var i = 1; i < pts.length-1; i++) {
            var dx = b[1]-a[1], dy = b[0]-a[0], len2 = dx*dx+dy*dy;
            var t = len2 ? Math.max(0,Math.min(1,((pts[i][1]-a[1])*dx+(pts[i][0]-a[0])*dy)/len2)) : 0;
            var d = haversine(pts[i][0],pts[i][1], a[0]+t*dy, a[1]+t*dx);
            if (d > dmax) { dmax = d; idx = i; }
        }
        if (dmax > tol) {
            var l = rdp(pts.slice(0, idx+1), tol), r = rdp(pts.slice(idx), tol);
            return l.slice(0, l.length-1).concat(r);
        }
        return [a, b];
    }
}());
</script>
<?php endif; ?>

<!-- Danger zone: delete item -->
<div class="card" style="margin-top:var(--spacing-4);border-color:rgba(220,53,69,.3)">
    <div class="card-body">
        <p style="font-weight:600;margin:0 0 var(--spacing-2)">Delete this item</p>
        <p class="text-muted text-sm" style="margin:0 0 var(--spacing-3)">This will permanently delete the item and all its attachments, logs, and reminders. This cannot be undone.</p>
        <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/trash') ?>"
              onsubmit="return confirm('Permanently delete this item? All logs, photos and reminders will be lost.')">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <button type="submit" class="btn btn-danger">🗑 Delete Item</button>
        </form>
    </div>
</div>

<script>
window.MINI_MAP_LAT = <?= (float)($item['gps_lat'] ?? 41.9) ?>;
window.MINI_MAP_LNG = <?= (float)($item['gps_lng'] ?? 12.5) ?>;

var itemTypeMeta = <?= json_encode(array_map(fn($t) => [
    'required_meta' => $t['required_meta'],
    'optional_meta' => $t['optional_meta'],
    'meta_options'  => $t['meta_options'] ?? [],
], $itemTypes)) ?>;

var CUSTOM_TREE_TYPES  = <?= json_encode(array_map(fn($t) => ['key' => $t['key'], 'label' => $t['label']], $customTypes ?? [])) ?>;
var CSRF_TOKEN_ITEM    = <?= json_encode(\App\Support\CSRF::getToken()) ?>;
var EXISTING_META      = <?= json_encode($meta) ?>;
var CURRENT_TYPE       = <?= json_encode($item['type']) ?>;

// ── Coords display ─────────────────────────────────────────────
function updateCoordsDisplay() {
    var lat = document.getElementById('gpsLat').value;
    var lng = document.getElementById('gpsLng').value;
    if (lat && lng) {
        document.getElementById('gpsCoordsText').textContent =
            parseFloat(lat).toFixed(5) + ', ' + parseFloat(lng).toFixed(5);
        document.getElementById('gpsCoordsDisplay').style.display = '';
    } else {
        document.getElementById('gpsCoordsDisplay').style.display = 'none';
    }
}
updateCoordsDisplay();
document.getElementById('gpsLat').addEventListener('change', updateCoordsDisplay);
document.getElementById('gpsLng').addEventListener('change', updateCoordsDisplay);

document.getElementById('clearGps').addEventListener('click', function () {
    document.getElementById('gpsLat').value = '';
    document.getElementById('gpsLng').value = '';
    document.getElementById('gpsAccuracy').value = '';
    document.getElementById('gpsSource').value = 'manual';
    document.getElementById('gpsStatus').style.display = 'none';
    updateCoordsDisplay();
});

// ── Meta fields (same logic as create.php) ──────────────────────
function buildMetaFields(type) {
    if (!type || !itemTypeMeta[type]) { document.getElementById('metaFields').style.display = 'none'; return; }
    var fields   = itemTypeMeta[type].required_meta.concat(itemTypeMeta[type].optional_meta);
    var metaOpts = itemTypeMeta[type].meta_options || {};
    var html = '';
    fields.forEach(function (key) {
        var label = key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
        var existing = EXISTING_META[key] || '';
        html += '<div class="form-group"><label class="form-label">' + label + '</label>';
        var opts = metaOpts[key];
        if (opts && opts.length) {
            html += '<select name="meta[' + key + ']" class="form-input form-input--touch" id="metaSelect_' + key + '">';
            html += '<option value="">— Select —</option>';
            opts.forEach(function (opt) {
                if (opt === 'other') return;
                var oLabel = opt.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
                html += '<option value="' + opt + '"' + (existing === opt ? ' selected' : '') + '>' + oLabel + '</option>';
            });
            // Inject custom tree types
            if (key === 'tree_type' && CUSTOM_TREE_TYPES.length) {
                html += '<optgroup label="Custom">';
                CUSTOM_TREE_TYPES.forEach(function (ct) {
                    html += '<option value="' + ct.key + '"' + (existing === ct.key ? ' selected' : '') + '>' + ct.label + '</option>';
                });
                html += '</optgroup>';
            }
            // If existing value not in any option, add it as a custom entry
            if (existing && !opts.includes(existing)) {
                var matched = CUSTOM_TREE_TYPES.some(function(ct) { return ct.key === existing; });
                if (!matched) {
                    html += '<option value="' + existing + '" selected>' + existing + ' (current)</option>';
                }
            }
            if (opts.indexOf('other') !== -1) {
                html += '<option value="other">Other (specify below)</option>';
            }
            html += '</select>';
            if (key === 'tree_type') {
                html += '<div id="customTreeWrap" style="display:none;margin-top:8px">'
                      + '<input type="text" id="customTreeLabel" class="form-input form-input--touch" placeholder="Enter tree type name…" style="margin-bottom:6px">'
                      + '<button type="button" id="customTreeSave" class="btn btn-secondary btn-sm">💾 Save as new type</button>'
                      + '<span id="customTreeMsg" style="font-size:.78rem;margin-left:8px"></span>'
                      + '</div>';
            }
        } else {
            html += '<input type="text" name="meta[' + key + ']" class="form-input form-input--touch"'
                  + ' placeholder="' + label + '" value="' + existing.replace(/"/g, '&quot;') + '">';
        }
        html += '</div>';
    });
    if (html) {
        document.getElementById('metaFieldsInner').innerHTML = html;
        document.getElementById('metaFields').style.display = '';
        wireCustomTreeType();
    } else {
        document.getElementById('metaFields').style.display = 'none';
    }
}

function wireCustomTreeType() {
    var sel    = document.getElementById('metaSelect_tree_type');
    var wrap   = document.getElementById('customTreeWrap');
    var input  = document.getElementById('customTreeLabel');
    var saveBtn = document.getElementById('customTreeSave');
    var msg    = document.getElementById('customTreeMsg');
    if (!sel || !wrap) return;
    sel.addEventListener('change', function () {
        wrap.style.display = sel.value === 'other' ? 'block' : 'none';
        if (sel.value !== 'other') msg.textContent = '';
    });
    saveBtn.addEventListener('click', function () {
        var label = input.value.trim();
        if (!label) { msg.textContent = '⚠️ Enter a name first.'; return; }
        saveBtn.disabled = true; saveBtn.textContent = '⏳ Saving…'; msg.textContent = '';
        var fd = new FormData();
        fd.append('label', label); fd.append('_token', CSRF_TOKEN_ITEM);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.APP_BASE + '/settings/tree-types/add', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', function () {
            var res; try { res = JSON.parse(xhr.responseText); } catch (e) {}
            saveBtn.disabled = false; saveBtn.textContent = '💾 Save as new type';
            if (res && res.success) {
                if (!sel.querySelector('option[value="' + res.key + '"]')) {
                    var opt = document.createElement('option');
                    opt.value = res.key; opt.textContent = res.label;
                    sel.insertBefore(opt, sel.querySelector('option[value="other"]'));
                    CUSTOM_TREE_TYPES.push({ key: res.key, label: res.label });
                }
                sel.value = res.key; wrap.style.display = 'none'; input.value = '';
            } else {
                msg.textContent = '⚠️ ' + (res && res.error ? res.error : 'Save failed.');
            }
        });
        xhr.send(fd);
    });
}

// Auto-build meta fields for this item's type on page load
buildMetaFields(CURRENT_TYPE);

// ── GPS detection (uses shared RootedGPS) ──────────────────────
document.getElementById('detectGps').addEventListener('click', function () {
    if (!navigator.geolocation) {
        document.getElementById('gpsStatus').textContent = '⚠️ Geolocation not supported.';
        document.getElementById('gpsStatus').style.display = '';
        return;
    }
    var btn = this;
    btn.disabled = true; btn.textContent = '⏳ Detecting…';
    document.getElementById('gpsStatus').textContent = '📡 Locating…';
    document.getElementById('gpsStatus').style.display = '';

    RootedGPS.get(function (pos) {
        btn.disabled = false; btn.textContent = '📍 Locate Me';
        if (!pos) {
            document.getElementById('gpsStatus').textContent = '🔒 Location unavailable — enable it in browser settings.';
            return;
        }
        document.getElementById('gpsLat').value = pos.lat.toFixed(7);
        document.getElementById('gpsLng').value = pos.lng.toFixed(7);
        document.getElementById('gpsAccuracy').value = Math.round(pos.accuracy);
        document.getElementById('gpsSource').value = 'device';
        document.getElementById('gpsStatus').textContent = '✅ Located ±' + Math.round(pos.accuracy) + 'm — drag pin to adjust.';
        updateCoordsDisplay();
    }, 20000);
});
</script>
