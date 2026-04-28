<?php $miniMapEnabled = true; ?>
<div class="page-header">
    <h1 class="page-title">Edit — <?= e($item['name']) ?></h1>
    <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if ($boundaryGeojson && in_array($item['type'], ['bed', 'garden'])): ?>
<div style="margin-bottom:var(--spacing-3)">
    <button type="button" id="editAdjustPositionBtn" class="btn btn-secondary" style="width:100%">📐 Adjust Position on Map</button>
</div>
<?php endif; ?>

<?php if ($item['type'] === 'bed'): ?>
<div class="card" style="margin-bottom:var(--spacing-3)">
    <div class="card-body">
        <div class="form-group-label" style="margin-bottom:var(--spacing-3)">🌿 Garden Bed Layout</div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Length N–S (m)</label>
                <input type="number" id="bedCfgLength" class="form-input form-input--touch"
                       min="0.1" step="0.1" placeholder="e.g. 8"
                       value="<?= e($meta['bed_length_m'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Width E–W (m)</label>
                <input type="number" id="bedCfgWidth" class="form-input form-input--touch"
                       min="0.1" step="0.1" placeholder="e.g. 1.2"
                       value="<?= e($meta['bed_width_m'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Number of Lines</label>
                <input type="number" id="bedCfgLines" class="form-input form-input--touch"
                       min="0" step="1" placeholder="e.g. 4"
                       value="<?= e($meta['bed_rows'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Lines Direction</label>
                <div style="display:flex;gap:14px;margin-top:8px">
                    <label style="display:flex;align-items:center;gap:5px;cursor:pointer">
                        <input type="radio" name="bedCfgDir" value="NS"
                               <?= ($meta['line_direction'] ?? 'NS') === 'NS' ? 'checked' : '' ?>
                               style="accent-color:var(--color-primary)"> N–S
                    </label>
                    <label style="display:flex;align-items:center;gap:5px;cursor:pointer">
                        <input type="radio" name="bedCfgDir" value="EW"
                               <?= ($meta['line_direction'] ?? 'NS') === 'EW' ? 'checked' : '' ?>
                               style="accent-color:var(--color-primary)"> E–W
                    </label>
                </div>
            </div>
        </div>

        <div id="bedCfgSpacing" class="text-sm" style="color:var(--color-text-muted);margin-bottom:var(--spacing-2)"></div>

        <div style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius);padding:var(--spacing-2);margin-bottom:var(--spacing-3)">
            <svg id="bedPreviewSvg" width="100%" viewBox="0 0 300 180" style="display:block;max-height:180px"></svg>
        </div>

        <button type="button" id="bedCfgSave" class="btn btn-primary btn-sm">💾 Save Layout</button>
        <span id="bedCfgMsg" style="font-size:.85rem;margin-left:8px"></span>
    </div>
</div>
<?php endif; ?>

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
                <select name="type" class="form-input form-input--touch">
                    <?php foreach ($itemTypes as $typeKey => $typeDef): ?>
                    <?php if ($typeKey === 'line') continue; ?>
                    <option value="<?= e($typeKey) ?>" <?= $item['type'] === $typeKey ? 'selected' : '' ?>>
                        <?= e($typeDef['label'] ?? ucwords(str_replace('_', ' ', $typeKey))) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-input form-input--touch" required value="<?= e($item['name']) ?>">
            </div>

            <?php if ($item['type'] === 'bed'): ?>
            <div class="form-group">
                <label class="form-label">Garden <small class="text-muted">(optional)</small></label>
                <select name="parent_id" class="form-input form-input--touch">
                    <option value="">— No garden —</option>
                    <?php foreach ($allGardens ?? [] as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= (int)($item['parent_id'] ?? 0) === (int)$g['id'] ? 'selected' : '' ?>>
                        <?= e($g['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

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
<?php $hasCornerMode = in_array($item['type'], ['bed', 'garden']); ?>
<!-- Boundary section (outside the main form — saved independently via AJAX) -->
<div class="card item-form-fields" style="margin-top:var(--spacing-3)">
    <div class="card-body">
        <div class="form-group-label" style="margin-bottom:var(--spacing-2)">📐 Boundary</div>

        <div id="editBoundaryStatus" class="text-sm" style="margin-bottom:var(--spacing-2);color:var(--color-text-muted)">
            <?= $boundaryGeojson ? '⬡ Boundary saved' : 'No boundary set yet' ?>
        </div>

        <?php if ($hasCornerMode): ?>
        <!-- Mode tabs -->
        <div style="display:flex;gap:6px;margin-bottom:var(--spacing-3)">
            <button type="button" id="editModeWalkBtn" class="btn btn-primary btn-sm" onclick="editSwitchMode('walk')">🚶 Walk</button>
            <button type="button" id="editModeCornerBtn" class="btn btn-secondary btn-sm" onclick="editSwitchMode('corner')">📐 Corner + Size</button>
        </div>
        <?php endif; ?>

        <!-- Walk mode -->
        <div id="editWalkSection">
            <div class="walk-mode-block" id="editWalkBlock">
                <button type="button" id="editWalkBtn" class="btn btn-primary btn-sm walk-btn">🚶 Walk the Boundary</button>
                <p class="text-muted text-sm" style="margin:4px 0 0">Walk around the zone — GPS records the path automatically.</p>
                <div id="editWalkActive" style="display:none;margin-top:6px">
                    <div id="editWalkStats" class="walk-stats"></div>
                    <button type="button" id="editWalkStopBtn" class="btn btn-danger btn-sm" style="width:100%;margin-top:6px">⏹ Stop &amp; Use This Path</button>
                </div>
            </div>
        </div>

        <?php if ($hasCornerMode): ?>
        <!-- Corner + Size mode -->
        <div id="editCornerSection" style="display:none">
            <p class="text-muted text-sm" style="margin:0 0 var(--spacing-2)">Stand at one corner of your <?= e($item['type']) ?>, tap <strong>Get Position</strong>, select the corner, then tap <strong>Position on Map</strong>. Dimensions come from the Layout card above.</p>
            <button type="button" id="editCornerGpsBtn" class="btn btn-secondary btn-sm">📡 Get Corner Position</button>
            <div id="editCornerGpsStatus" class="map-gps-status" style="display:none"></div>

            <div id="editCornerForm" style="display:none;margin-top:var(--spacing-3)">
                <div class="form-group" style="margin-bottom:var(--spacing-2)">
                    <label class="form-label">Which corner are you at?</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;max-width:200px;margin-top:4px">
                        <button type="button" class="btn btn-secondary btn-sm edit-corner-btn" data-corner="NW">↖ NW</button>
                        <button type="button" class="btn btn-secondary btn-sm edit-corner-btn" data-corner="NE">↗ NE</button>
                        <button type="button" class="btn btn-secondary btn-sm edit-corner-btn" data-corner="SW">↙ SW</button>
                        <button type="button" class="btn btn-secondary btn-sm edit-corner-btn" data-corner="SE">↘ SE</button>
                    </div>
                </div>
                <button type="button" id="editCornerPreviewBtn" class="btn btn-primary btn-sm">🗺 Position on Map</button>
                <div id="editCornerMsg" class="text-sm" style="margin-top:6px;color:var(--color-danger,#c0392b)"></div>
            </div>
        </div>
        <?php endif; ?>


        <div id="editBoundaryActions" style="display:none;margin-top:var(--spacing-2);display:flex;gap:var(--spacing-2)">
            <button type="button" id="editBoundarySave" class="btn btn-primary btn-sm">💾 Save Boundary</button>
            <button type="button" id="editBoundaryDiscard" class="btn btn-secondary btn-sm">✕ Discard</button>
        </div>

        <div id="editBoundaryToast" style="display:none;margin-top:var(--spacing-2);padding:10px 14px;border-radius:var(--radius);font-size:.875rem;font-weight:500"></div>

        <?php if ($boundaryGeojson): ?>
        <div style="margin-top:var(--spacing-3);padding-top:var(--spacing-2);border-top:1px solid var(--color-border)" id="editBoundaryDeleteRow">
            <p class="text-sm" style="margin:0 0 var(--spacing-2);color:var(--color-text-muted)">Danger zone</p>
            <span id="editBoundaryDeleteConfirmWrap" style="display:flex;gap:6px;align-items:center">
                <button type="button" id="editBoundaryDelete" class="btn btn-danger btn-sm" style="font-size:.8rem">🗑 Remove Boundary</button>
            </span>
            <span id="editBoundaryDeleteConfirm" style="display:none;gap:6px;align-items:center">
                <span style="font-size:.85rem;color:var(--color-danger,#c0392b)">Sure?</span>
                <button type="button" id="editBoundaryDeleteYes" class="btn btn-danger btn-sm">Yes, remove</button>
                <button type="button" id="editBoundaryDeleteNo" class="btn btn-secondary btn-sm">Cancel</button>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Full-screen Position Modal -->
<div id="posModal" style="display:none;position:fixed;inset:0;z-index:9999;background:#fff;flex-direction:column;overflow:hidden">
    <div style="display:flex;align-items:center;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#fff;flex-shrink:0">
        <span style="font-weight:700;font-size:1rem;flex:1">📍 Position on Map</span>
        <button type="button" id="posModalClose" class="btn btn-secondary btn-sm">✕ Cancel</button>
    </div>
    <div id="posModalMap" style="flex:1;min-height:0;z-index:1"></div>
    <div style="padding:12px 16px;border-top:1px solid #e5e7eb;background:#f9fafb;flex-shrink:0">
        <div style="display:flex;gap:8px;margin-bottom:8px">
            <button type="button" class="btn btn-secondary btn-sm" id="posRotateCCW" style="flex:1">↺ 5°</button>
            <button type="button" class="btn btn-secondary btn-sm" id="posRotateCW"  style="flex:1">↻ 5°</button>
        </div>
        <p style="font-size:.75rem;color:#999;margin:0 0 8px;text-align:center">Drag the polygon to move · rotate with buttons</p>
        <button type="button" id="posModalSave" class="btn btn-primary" style="width:100%;display:none">💾 Save Changes</button>
    </div>
</div>

<script>
(function () {
    var CSRF          = <?= json_encode(\App\Support\CSRF::getToken()) ?>;
    var SAVE_URL      = <?= json_encode(url('/api/map/boundary/' . (int)$item['id'])) ?>;
    var DELETE_URL    = <?= json_encode(url('/api/map/boundary/' . (int)$item['id'] . '/delete')) ?>;
    var EXISTING      = <?= $boundaryGeojson ? $boundaryGeojson : 'null' ?>;
    var HAS_CORNER_MODE = <?= $hasCornerMode ? 'true' : 'false' ?>;

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
    var pendingPts   = null;
    var pendingBedMeta = null;

    // Corner+Size mode state
    var cornerLat = null, cornerLng = null, cornerAcc = null;
    var cornerSide = null;

    // Position modal state
    var posMap = null;
    var posPolygon = null;
    var posPts = [];
    var posOrigPts = [];
    var posBedMeta = null;
    var posDragging = false;
    var posDragLastLatLng = null;

    var statusEl  = document.getElementById('editBoundaryStatus');
    var statsEl   = document.getElementById('editWalkStats');
    var activeEl  = document.getElementById('editWalkActive');
    var actionsEl = document.getElementById('editBoundaryActions');
    var toastEl   = document.getElementById('editBoundaryToast');

    function showToast(msg, type) {
        if (!toastEl) return;
        var bg    = type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#fff3cd';
        var color = type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#856404';
        var border= type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#ffeeba';
        toastEl.style.cssText = 'display:block;padding:10px 14px;border-radius:var(--radius);font-size:.875rem;font-weight:500;'
            + 'background:' + bg + ';color:' + color + ';border:1px solid ' + border;
        toastEl.textContent = msg;
        if (type === 'success') { setTimeout(function() { toastEl.style.display = 'none'; }, 4000); }
    }

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

    if (EXISTING) { setTimeout(function () { drawBoundary(EXISTING); }, 350); }

    // ── Walk ──────────────────────────────────────────────────────────────────
    document.getElementById('editWalkBtn').addEventListener('click', startWalk);
    document.getElementById('editWalkStopBtn').addEventListener('click', function () { stopWalk(true); });

    function startWalk() {
        if (!navigator.geolocation) { showToast('⚠️ Geolocation not available.', 'error'); return; }
        var m = mm();
        walkActive  = true; walkPts = []; walkDist = 0; walkLastLat = null; walkLastLng = null;
        pendingPts  = null;
        if (m) {
            if (walkPolyline) { m.removeLayer(walkPolyline); walkPolyline = null; }
            if (walkPolygon)  { m.removeLayer(walkPolygon);  walkPolygon  = null; }
        }
        document.getElementById('editWalkBtn').style.display = 'none';
        activeEl.style.display = 'block'; actionsEl.style.display = 'none';
        statsEl.innerHTML = '<span class="walk-recording-dot"></span> Waiting for GPS fix…';
        if ('wakeLock' in navigator) { navigator.wakeLock.request('screen').catch(function () {}); }
        walkUnsub = window.RootedGPS
            ? RootedGPS.subscribe(onWalkPos)
            : (function () {
                var wid = navigator.geolocation.watchPosition(
                    function (p) { onWalkPos({ lat: p.coords.latitude, lng: p.coords.longitude, accuracy: p.coords.accuracy }); },
                    function () {}, { enableHighAccuracy: true, maximumAge: 0 }
                );
                return function () { navigator.geolocation.clearWatch(wid); };
            })();
    }

    function onWalkPos(pos) {
        if (!walkActive) return;
        if (!pos) { stopWalk(false); showToast('🔒 GPS denied — enable Location in browser settings.', 'error'); return; }
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
        walkLastLat = lat; walkLastLng = lng; walkPts.push([lat, lng]);
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
        activeEl.style.display = 'none'; statsEl.innerHTML = '';
        var m = mm();
        if (walkPolyline && m) { m.removeLayer(walkPolyline); walkPolyline = null; }
        if (!usePath || walkPts.length < 3) {
            if (usePath) showToast('⚠️ Not enough points (' + walkPts.length + '). Walk further before stopping.', 'error');
            return;
        }
        pendingPts = rdp(walkPts, WALK_SIMPLIFY_M);
        if (m) {
            if (walkPolygon) m.removeLayer(walkPolygon);
            walkPolygon = L.polygon(pendingPts, { color: '#3c8dbc', fillColor: '#3c8dbc', fillOpacity: 0.15, weight: 2 }).addTo(m);
            m.fitBounds(walkPolygon.getBounds(), { padding: [20, 20], maxZoom: 20 });
        }
        if (statusEl) statusEl.textContent = '✅ ' + pendingPts.length + ' points · ' + Math.round(walkDist) + ' m — tap Save to confirm';
        actionsEl.style.display = 'flex';
    }

    // ── Mode toggle (bed/garden only) ─────────────────────────────────────────
    window.editSwitchMode = function(mode) {
        var walkSec   = document.getElementById('editWalkSection');
        var cornerSec = document.getElementById('editCornerSection');
        var walkBtn   = document.getElementById('editModeWalkBtn');
        var cornerBtn = document.getElementById('editModeCornerBtn');
        if (!walkSec || !cornerSec) return;
        if (mode === 'walk') {
            walkSec.style.display = ''; cornerSec.style.display = 'none';
            walkBtn.className = 'btn btn-primary btn-sm'; cornerBtn.className = 'btn btn-secondary btn-sm';
        } else {
            walkSec.style.display = 'none'; cornerSec.style.display = '';
            cornerBtn.className = 'btn btn-primary btn-sm'; walkBtn.className = 'btn btn-secondary btn-sm';
        }
        pendingPts = null; pendingBedMeta = null; actionsEl.style.display = 'none';
    };

    // ── Corner + Size mode ────────────────────────────────────────────────────
    if (HAS_CORNER_MODE) {
        var cornerGpsBtn    = document.getElementById('editCornerGpsBtn');
        var cornerGpsStatus = document.getElementById('editCornerGpsStatus');
        var cornerFormEl    = document.getElementById('editCornerForm');
        var cornerMsgEl     = document.getElementById('editCornerMsg');

        function showCornerStatus(msg, type) {
            cornerGpsStatus.textContent = msg;
            cornerGpsStatus.className = 'map-gps-status map-gps-status--' + (type || 'info');
            cornerGpsStatus.style.display = 'block';
        }

        cornerGpsBtn.addEventListener('click', function () {
            var btn = this;
            btn.disabled = true; btn.textContent = '📡 …';
            var readings = [], watchId = null, done = false;

            function finish() {
                if (done) return; done = true;
                if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
                btn.disabled = false; btn.textContent = '📡 Get Corner Position';
                if (!readings.length) { showCornerStatus('⚠️ No GPS fix', 'error'); return; }
                readings.sort(function (a, b) { return a.acc - b.acc; });
                var keep = Math.max(1, Math.ceil(readings.length / 2));
                var best = readings.slice(0, keep);
                cornerLat = best.reduce(function (s, r) { return s + r.lat; }, 0) / best.length;
                cornerLng = best.reduce(function (s, r) { return s + r.lng; }, 0) / best.length;
                cornerAcc = best[0].acc;
                var dot  = cornerAcc <= 5 ? '🟢' : cornerAcc <= 20 ? '🟡' : '🔴';
                var type = cornerAcc <= 5 ? 'success' : cornerAcc <= 20 ? 'warning' : 'info';
                showCornerStatus(dot + ' ±' + Math.round(cornerAcc) + ' m — select corner and tap Position on Map', type);
                cornerFormEl.style.display = 'block';
            }

            watchId = navigator.geolocation.watchPosition(function (pos) {
                var r = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy };
                readings.push(r);
                var dot = r.acc <= 5 ? '🟢' : r.acc <= 20 ? '🟡' : '🔴';
                var t   = r.acc <= 5 ? 'success' : r.acc <= 20 ? 'warning' : 'error';
                showCornerStatus(dot + ' ' + readings.length + ' fixes · ±' + Math.round(r.acc) + ' m', t);
                if (readings.length >= 3 && r.acc <= 5) finish();
                else if (readings.length >= 12) finish();
            }, function () {
                if (readings.length > 0) { finish(); return; }
                done = true; if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                btn.disabled = false; btn.textContent = '📡 Get Corner Position';
                showCornerStatus('🔒 GPS unavailable', 'error');
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 25000 });
            setTimeout(function () { if (readings.length > 0) finish(); }, 6000);
        });

        document.querySelectorAll('.edit-corner-btn').forEach(function (b) {
            b.addEventListener('click', function () {
                cornerSide = this.dataset.corner;
                document.querySelectorAll('.edit-corner-btn').forEach(function (x) {
                    x.className = x === b ? 'btn btn-primary btn-sm edit-corner-btn' : 'btn btn-secondary btn-sm edit-corner-btn';
                });
            });
        });

        // "Position on Map" — reads dims from Layout card, opens modal
        document.getElementById('editCornerPreviewBtn').addEventListener('click', function () {
            if (!cornerLat) { if (cornerMsgEl) cornerMsgEl.textContent = '⚠️ Get your GPS position first.'; return; }
            if (!cornerSide) { if (cornerMsgEl) cornerMsgEl.textContent = '⚠️ Select which corner you are at.'; return; }
            var lmEl = document.getElementById('bedCfgLength');
            var wmEl = document.getElementById('bedCfgWidth');
            var lM   = lmEl ? parseFloat(lmEl.value) : NaN;
            var wM   = wmEl ? parseFloat(wmEl.value) : NaN;
            if (!lM || !wM || lM <= 0 || wM <= 0) {
                if (cornerMsgEl) cornerMsgEl.textContent = '⚠️ Set length and width in the Garden Bed Layout card first.';
                return;
            }
            var latPerM = 1 / 111111;
            var lngPerM = 1 / (111111 * Math.cos(cornerLat * Math.PI / 180));
            var dLat = lM * latPerM, dLng = wM * lngPerM;
            var nw, ne, se, sw;
            if (cornerSide === 'NE') { ne=[cornerLat,cornerLng]; nw=[cornerLat,cornerLng-dLng]; se=[cornerLat-dLat,cornerLng]; sw=[cornerLat-dLat,cornerLng-dLng]; }
            else if (cornerSide === 'NW') { nw=[cornerLat,cornerLng]; ne=[cornerLat,cornerLng+dLng]; sw=[cornerLat-dLat,cornerLng]; se=[cornerLat-dLat,cornerLng+dLng]; }
            else if (cornerSide === 'SE') { se=[cornerLat,cornerLng]; sw=[cornerLat,cornerLng-dLng]; ne=[cornerLat+dLat,cornerLng]; nw=[cornerLat+dLat,cornerLng-dLng]; }
            else { sw=[cornerLat,cornerLng]; se=[cornerLat,cornerLng+dLng]; nw=[cornerLat+dLat,cornerLng]; ne=[cornerLat+dLat,cornerLng+dLng]; }
            var pts = [nw, ne, se, sw];
            var linesEl = document.getElementById('bedCfgLines');
            var dirEl   = document.querySelector('input[name="bedCfgDir"]:checked');
            var bm = {
                bed_rows: linesEl ? (parseInt(linesEl.value) || 0) : 0,
                bed_length_m: lM, bed_width_m: wM,
                line_direction: dirEl ? dirEl.value : 'NS'
            };
            if (cornerMsgEl) cornerMsgEl.textContent = '';
            openPositionModal(pts, bm);
        });

        // "Adjust Position on Map" button (shown when boundary already exists)
        var adjBtn = document.getElementById('editAdjustPositionBtn');
        if (adjBtn) {
            adjBtn.addEventListener('click', function() {
                if (!EXISTING) return;
                try {
                    var geo    = typeof EXISTING === 'string' ? JSON.parse(EXISTING) : EXISTING;
                    var coords = geo.coordinates[0];
                    var pts    = coords.slice(0,-1).map(function(c){ return [c[1], c[0]]; });
                    openPositionModal(pts, pendingBedMeta || {});
                } catch(e) {}
            });
        }
    }

    // ── Position Modal ────────────────────────────────────────────────────────
    function checkPosChanged() {
        var changed = posOrigPts.length !== posPts.length;
        if (!changed) {
            for (var i = 0; i < posPts.length; i++) {
                if (Math.abs(posPts[i][0] - posOrigPts[i][0]) > 1e-9 || Math.abs(posPts[i][1] - posOrigPts[i][1]) > 1e-9) {
                    changed = true; break;
                }
            }
        }
        document.getElementById('posModalSave').style.display = changed ? '' : 'none';
    }

    function openPositionModal(pts, bedMeta) {
        posPts = pts.slice();
        posOrigPts = pts.map(function(p) { return [p[0], p[1]]; });
        posBedMeta = bedMeta || {};
        document.getElementById('posModalSave').style.display = 'none';
        var modal = document.getElementById('posModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(function() {
            if (!posMap) {
                posMap = L.map('posModalMap', { zoomControl: true });
                L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
                    maxZoom: 22,
                    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                    attribution: '© Google'
                }).addTo(posMap);
                posMap.on('mousemove', function(e) {
                    if (!posDragging) return;
                    var dLat = e.latlng.lat - posDragLastLatLng.lat;
                    var dLng = e.latlng.lng - posDragLastLatLng.lng;
                    posDragLastLatLng = e.latlng;
                    posPts = posPts.map(function(p) { return [p[0]+dLat, p[1]+dLng]; });
                    if (posPolygon) posPolygon.setLatLngs(posPts);
                    checkPosChanged();
                });
                posMap.on('mouseup', function() {
                    if (posDragging) { posDragging = false; posMap.dragging.enable(); }
                });
                posMap.getContainer().addEventListener('mouseleave', function() {
                    if (posDragging) { posDragging = false; posMap.dragging.enable(); }
                });
                posMap.getContainer().addEventListener('touchmove', function(e) {
                    if (!posDragging) return;
                    e.preventDefault();
                    var t = e.touches[0];
                    var latlng = posMap.containerPointToLatLng(posMap.mouseEventToContainerPoint({clientX: t.clientX, clientY: t.clientY}));
                    var dLat = latlng.lat - posDragLastLatLng.lat;
                    var dLng = latlng.lng - posDragLastLatLng.lng;
                    posDragLastLatLng = latlng;
                    posPts = posPts.map(function(p) { return [p[0]+dLat, p[1]+dLng]; });
                    if (posPolygon) posPolygon.setLatLngs(posPts);
                    checkPosChanged();
                }, { passive: false });
                posMap.getContainer().addEventListener('touchend', function() {
                    if (posDragging) { posDragging = false; posMap.dragging.enable(); }
                });
            } else {
                posMap.invalidateSize();
            }
            drawPosPolygon();
            var bounds = L.latLngBounds(posPts.map(function(p){ return [p[0], p[1]]; }));
            posMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 20 });
        }, 80);
    }

    function drawPosPolygon() {
        if (!posMap) return;
        if (posPolygon) { posMap.removeLayer(posPolygon); posPolygon = null; }
        posPolygon = L.polygon(posPts, {
            color: '#2d8a27', fillColor: '#2d8a27', fillOpacity: 0.25, weight: 3,
            interactive: true
        }).addTo(posMap);
        posPolygon.on('mousedown', function(e) {
            L.DomEvent.stopPropagation(e);
            posMap.dragging.disable();
            posDragging = true;
            posDragLastLatLng = e.latlng;
        });
        posPolygon.on('touchstart', function(e) {
            L.DomEvent.stopPropagation(e);
            posMap.dragging.disable();
            posDragging = true;
            var t = e.originalEvent.touches[0];
            posDragLastLatLng = posMap.containerPointToLatLng(posMap.mouseEventToContainerPoint({clientX: t.clientX, clientY: t.clientY}));
        });
        var el = posPolygon.getElement ? posPolygon.getElement() : null;
        if (el) el.style.cursor = 'grab';
    }

    function rotatePts(pts, deg) {
        var cx = pts.reduce(function(s,p){return s+p[0];},0) / pts.length;
        var cy = pts.reduce(function(s,p){return s+p[1];},0) / pts.length;
        var latPerM = 1 / 111111;
        var lngPerM = 1 / (111111 * Math.cos(cx * Math.PI / 180));
        var rad = deg * Math.PI / 180;
        return pts.map(function(p) {
            var dy = (p[0] - cx) / latPerM, dx = (p[1] - cy) / lngPerM;
            var rDy = dy * Math.cos(rad) - dx * Math.sin(rad);
            var rDx = dx * Math.cos(rad) + dy * Math.sin(rad);
            return [cx + rDy * latPerM, cy + rDx * lngPerM];
        });
    }

    document.getElementById('posRotateCCW').addEventListener('click', function() { posPts = rotatePts(posPts, -5); drawPosPolygon(); checkPosChanged(); });
    document.getElementById('posRotateCW').addEventListener('click',  function() { posPts = rotatePts(posPts,  5); drawPosPolygon(); checkPosChanged(); });

    document.getElementById('posModalClose').addEventListener('click', function() {
        document.getElementById('posModal').style.display = 'none';
        document.body.style.overflow = '';
    });

    document.getElementById('posModalSave').addEventListener('click', function() {
        var btn = this; btn.disabled = true; btn.textContent = 'Saving…';
        var geojson = { type: 'Polygon', coordinates: [posPts.map(function(p){ return [p[1], p[0]]; })] };
        geojson.coordinates[0].push(geojson.coordinates[0][0]);
        var fd = new FormData();
        fd.append('_token', CSRF); fd.append('geojson', JSON.stringify(geojson));
        if (posBedMeta) {
            if (posBedMeta.bed_rows > 0)     fd.append('bed_rows',       posBedMeta.bed_rows);
            if (posBedMeta.bed_length_m > 0) fd.append('bed_length_m',   posBedMeta.bed_length_m);
            if (posBedMeta.bed_width_m > 0)  fd.append('bed_width_m',    posBedMeta.bed_width_m);
            if (posBedMeta.line_direction)   fd.append('line_direction',  posBedMeta.line_direction);
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', SAVE_URL, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', function() {
            btn.disabled = false; btn.textContent = '💾 Save Changes';
            var res; try { res = JSON.parse(xhr.responseText); } catch(e) {}
            if (res && res.success) {
                EXISTING = geojson;
                pendingPts = posPts.slice(); pendingBedMeta = posBedMeta;
                drawBoundary(geojson);
                if (statusEl) statusEl.textContent = '⬡ Boundary saved';
                document.getElementById('posModal').style.display = 'none';
                document.body.style.overflow = '';
                showToast('✅ Position saved successfully', 'success');
                ensureDeleteRow();
            } else {
                showToast('❌ ' + (res ? (res.message || res.error || 'Save failed') : 'Save failed'), 'error');
            }
        });
        xhr.addEventListener('error', function() {
            btn.disabled = false; btn.textContent = '💾 Save Changes';
            showToast('❌ Network error — try again.', 'error');
        });
        xhr.send(fd);
    });

    // ── Save (Walk mode) ─────────────────────────────────────────────────────
    document.getElementById('editBoundarySave').addEventListener('click', function () {
        if (!pendingPts || pendingPts.length < 3) { showToast('⚠️ Walk a boundary first.', 'error'); return; }
        var btn = this; btn.disabled = true; btn.textContent = 'Saving…';
        var geojson = { type: 'Polygon', coordinates: [pendingPts.map(function (p) { return [p[1], p[0]]; })] };
        geojson.coordinates[0].push(geojson.coordinates[0][0]);
        var fd = new FormData();
        fd.append('_token', CSRF); fd.append('geojson', JSON.stringify(geojson));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', SAVE_URL, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', function () {
            btn.disabled = false; btn.textContent = '💾 Save Boundary';
            var res; try { res = JSON.parse(xhr.responseText); } catch (e) {}
            if (res && res.success) {
                EXISTING = geojson;
                if (walkPolygon) { var m = mm(); if (m) m.removeLayer(walkPolygon); walkPolygon = null; }
                drawBoundary(geojson);
                actionsEl.style.display = 'none'; pendingPts = null;
                if (statusEl) statusEl.textContent = '⬡ Boundary saved';
                showToast('✅ Boundary saved', 'success');
                ensureDeleteRow();
            } else {
                showToast('❌ ' + (res ? (res.message || 'Save failed') : 'Save failed'), 'error');
            }
        });
        xhr.send(fd);
    });

    // ── Discard ───────────────────────────────────────────────────────────────
    document.getElementById('editBoundaryDiscard').addEventListener('click', function () {
        var m = mm();
        if (walkPolygon && m) { m.removeLayer(walkPolygon); walkPolygon = null; }
        actionsEl.style.display = 'none'; pendingPts = null; pendingBedMeta = null;
        if (EXISTING) { drawBoundary(EXISTING); if (statusEl) statusEl.textContent = '⬡ Boundary saved'; }
        else if (statusEl) statusEl.textContent = 'No boundary set yet';
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    function ensureDeleteRow() {
        var delRow = document.getElementById('editBoundaryDeleteRow');
        if (delRow) { delRow.style.display = ''; return; }
        var row = document.createElement('div');
        row.id = 'editBoundaryDeleteRow';
        row.style.cssText = 'margin-top:var(--spacing-3);padding-top:var(--spacing-2);border-top:1px solid var(--color-border)';
        row.innerHTML = '<p class="text-sm" style="margin:0 0 var(--spacing-2);color:var(--color-text-muted)">Danger zone</p>'
            + '<span id="editBoundaryDeleteConfirmWrap" style="display:flex;gap:6px;align-items:center">'
            + '<button type="button" id="editBoundaryDelete" class="btn btn-danger btn-sm" style="font-size:.8rem">🗑 Remove Boundary</button>'
            + '</span>'
            + '<span id="editBoundaryDeleteConfirm" style="display:none;gap:6px;align-items:center">'
            + '<span style="font-size:.85rem;color:var(--color-danger,#c0392b)">Sure?</span>'
            + '<button type="button" id="editBoundaryDeleteYes" class="btn btn-danger btn-sm">Yes, remove</button>'
            + '<button type="button" id="editBoundaryDeleteNo" class="btn btn-secondary btn-sm">Cancel</button>'
            + '</span>';
        var t2 = document.getElementById('editBoundaryToast');
        if (t2) t2.parentNode.insertBefore(row, t2); else document.querySelector('#editBoundaryMsg') || document.querySelector('.card-body').appendChild(row);
        wireDeleteBtn();
    }

    function wireDeleteBtn() {
        var btn  = document.getElementById('editBoundaryDelete');
        var wrap = document.getElementById('editBoundaryDeleteConfirmWrap');
        var conf = document.getElementById('editBoundaryDeleteConfirm');
        var yes  = document.getElementById('editBoundaryDeleteYes');
        var no   = document.getElementById('editBoundaryDeleteNo');
        if (!btn || !conf) return;
        btn.addEventListener('click', function () { if (wrap) wrap.style.display = 'none'; conf.style.display = 'flex'; });
        no.addEventListener('click',  function () { conf.style.display = 'none'; if (wrap) wrap.style.display = 'flex'; });
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
                    showToast('✅ Boundary removed', 'success');
                    EXISTING = null;
                } else {
                    yes.textContent = 'Yes, remove'; conf.style.display = 'none';
                    if (wrap) wrap.style.display = 'flex';
                    showToast('❌ Could not remove boundary', 'error');
                }
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
var GPS_DETECT_ZOOM = <?= (int)($gpsDetectZoom ?? 18) ?>;

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

// ── Bed layout card ────────────────────────────────────────────
<?php if ($item['type'] === 'bed'): ?>
(function() {
    var CSRF_BED = <?= json_encode(\App\Support\CSRF::getToken()) ?>;
    var BED_CFG_URL = <?= json_encode(url('/items/' . (int)$item['id'] . '/bed-config')) ?>;

    function redrawBed() {
        var len   = parseFloat(document.getElementById('bedCfgLength').value) || 0;
        var wid   = parseFloat(document.getElementById('bedCfgWidth').value) || 0;
        var lines = parseInt(document.getElementById('bedCfgLines').value) || 0;
        var dirEl = document.querySelector('input[name="bedCfgDir"]:checked');
        var dir   = dirEl ? dirEl.value : 'NS';

        var spacingEl = document.getElementById('bedCfgSpacing');
        if (lines > 0) {
            var dim = dir === 'NS' ? wid : len;
            spacingEl.textContent = dim > 0
                ? '≈ ' + (dim / lines).toFixed(2) + ' m per row (' + lines + ' line' + (lines !== 1 ? 's' : '') + ')'
                : '';
        } else {
            spacingEl.textContent = '';
        }
        drawBedSvg(len, wid, lines, dir);
    }

    function drawBedSvg(len, wid, lines, dir) {
        var svg = document.getElementById('bedPreviewSvg');
        var W = 300, H = 180, pad = 32;

        if (!len || !wid) {
            svg.innerHTML = '<text x="150" y="95" text-anchor="middle" fill="#aaa" font-size="13">Enter length and width to preview</text>';
            return;
        }

        var scale = Math.min((W - 2*pad) / wid, (H - 2*pad) / len);
        var rW = wid * scale, rH = len * scale;
        var rX = (W - rW) / 2, rY = (H - rH) / 2;

        var out = [];
        out.push('<rect x="'+rX+'" y="'+rY+'" width="'+rW+'" height="'+rH+'" fill="#e8f5e9" stroke="#2d8a27" stroke-width="2.5" rx="2"/>');

        for (var i = 1; i <= lines; i++) {
            if (dir === 'NS') {
                var x = rX + (rW * i / (lines + 1));
                out.push('<line x1="'+x+'" y1="'+(rY+4)+'" x2="'+x+'" y2="'+(rY+rH-4)+'" stroke="#4caf50" stroke-width="1.5" stroke-dasharray="5 3"/>');
            } else {
                var y = rY + (rH * i / (lines + 1));
                out.push('<line x1="'+(rX+4)+'" y1="'+y+'" x2="'+(rX+rW-4)+'" y2="'+y+'" stroke="#4caf50" stroke-width="1.5" stroke-dasharray="5 3"/>');
            }
        }

        out.push('<text x="'+(rX+rW/2)+'" y="'+(rY-7)+'" text-anchor="middle" fill="#555" font-size="11" font-weight="600">E–W '+wid+' m</text>');
        out.push('<text x="'+(rX+rW+8)+'" y="'+(rY+rH/2)+'" dominant-baseline="middle" fill="#555" font-size="11" font-weight="600">N–S '+len+' m</text>');
        out.push('<text x="'+(W-18)+'" y="16" text-anchor="middle" fill="#999" font-size="10" font-weight="bold">N</text>');
        out.push('<polygon points="'+(W-18)+',18 '+(W-21)+',26 '+(W-18)+',24 '+(W-15)+',26" fill="#bbb"/>');

        svg.innerHTML = out.join('');
    }

    ['bedCfgLength','bedCfgWidth','bedCfgLines'].forEach(function(id) {
        document.getElementById(id).addEventListener('input', redrawBed);
    });
    document.querySelectorAll('input[name="bedCfgDir"]').forEach(function(r) {
        r.addEventListener('change', redrawBed);
    });
    redrawBed();

    document.getElementById('bedCfgSave').addEventListener('click', function() {
        var btn = this, msg = document.getElementById('bedCfgMsg');
        btn.disabled = true; btn.textContent = '⏳ Saving…'; msg.textContent = '';
        var fd = new FormData();
        fd.append('_token', CSRF_BED);
        fd.append('bed_rows', parseInt(document.getElementById('bedCfgLines').value) || 0);
        fd.append('bed_length_m', parseFloat(document.getElementById('bedCfgLength').value) || 0);
        fd.append('bed_width_m', parseFloat(document.getElementById('bedCfgWidth').value) || 0);
        var dirEl = document.querySelector('input[name="bedCfgDir"]:checked');
        fd.append('line_direction', dirEl ? dirEl.value : 'NS');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', BED_CFG_URL, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', function() {
            btn.disabled = false; btn.textContent = '💾 Save Layout';
            var res; try { res = JSON.parse(xhr.responseText); } catch(e) {}
            if (res && res.success) {
                msg.textContent = '✅ Saved';
                msg.style.color = '';
                setTimeout(function() { if (msg.textContent === '✅ Saved') msg.textContent = ''; }, 3000);
            } else {
                msg.textContent = '❌ ' + (res && res.error ? res.error : 'Save failed');
                msg.style.color = 'var(--color-danger,red)';
            }
        });
        xhr.addEventListener('error', function() {
            btn.disabled = false; btn.textContent = '💾 Save Layout';
            msg.textContent = '❌ Network error'; msg.style.color = 'var(--color-danger,red)';
        });
        xhr.send(fd);
    });
}());
<?php endif; ?>

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
        if (window.miniMapLeaflet) {
            window.miniMapLeaflet.setView([pos.lat, pos.lng], GPS_DETECT_ZOOM);
        }
    }, 20000);
});
</script>
