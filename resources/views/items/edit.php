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
        xhr.open('POST', window.APP_BASE + 'settings/tree-types/add', true);
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
