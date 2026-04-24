<?php $miniMapEnabled = true; ?>
<div class="page-header">
    <h1 class="page-title">Add Item</h1>
    <a href="<?= url('/items') ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<form method="POST" action="<?= url('/items') ?>" class="form item-form-mobile" id="itemForm">
    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
    <input type="hidden" name="gps_source" id="gpsSource" value="manual">
    <input type="hidden" name="gps_lat"    id="gpsLat"    value="<?= e(getFlash('old')['gps_lat'] ?? '') ?>">
    <input type="hidden" name="gps_lng"    id="gpsLng"    value="<?= e(getFlash('old')['gps_lng'] ?? '') ?>">
    <input type="hidden" name="gps_accuracy" id="gpsAccuracy" value="">

    <!-- ① Map — full width, tap to place pin -->
    <div class="item-form-map-block">
        <div class="item-form-map-label">
            <span>📍 Tap the map to set location</span>
            <button type="button" class="btn btn-primary btn-sm" id="detectGps">📍 Locate Me</button>
        </div>
        <div id="miniMap" class="item-form-map"></div>
        <div id="gpsStatus" class="item-form-gps-status" style="display:none"></div>
    </div>

    <!-- ② Core fields -->
    <div class="card item-form-fields">
        <div class="card-body">

            <div class="form-group">
                <label class="form-label">Item Type <span class="required">*</span></label>
                <select name="type" id="itemType" class="form-input form-input--touch" required>
                    <option value="">— Select type —</option>
                    <?php
                    $preType = getFlash('old')['type'] ?? (isset($_GET['type']) ? htmlspecialchars_decode($_GET['type']) : '');
                    foreach ($itemTypes as $typeKey => $typeCfg): ?>
                    <option value="<?= e($typeKey) ?>" <?= $preType === $typeKey ? 'selected' : '' ?>>
                        <?= e($typeCfg['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-input form-input--touch" required
                       value="<?= e(getFlash('old')['name'] ?? '') ?>" placeholder="e.g. Olive #12, North Garden…">
            </div>

            <div class="form-group">
                <label class="form-label">Parent Item <small class="text-muted">(optional)</small></label>
                <input type="number" name="parent_id" class="form-input form-input--touch" placeholder="Parent item ID"
                       value="<?= e(getFlash('old')['parent_id'] ?? '') ?>">
            </div>

            <!-- Coordinates (read-only display, set by map or GPS) -->
            <div class="gps-coords-display" id="gpsCoordsDisplay" style="display:none">
                <span class="gps-coords-icon">📍</span>
                <span id="gpsCoordsText"></span>
                <button type="button" class="btn btn-link btn-sm" id="clearGps">Clear</button>
            </div>

            <!-- Type-specific meta fields -->
            <div id="metaFields" style="display:none">
                <hr class="form-divider">
                <div class="form-group-label">Type Details</div>
                <div id="metaFieldsInner"></div>
            </div>

        </div>
    </div>

    <!-- ③ Submit -->
    <div class="item-form-submit">
        <button type="submit" class="btn btn-primary btn-full">Save Item</button>
        <a href="<?= url('/items') ?>" class="btn btn-secondary btn-full">Cancel</a>
    </div>

</form>

<script>
var itemTypeMeta = <?= json_encode(array_map(fn($t) => [
    'required_meta' => $t['required_meta'],
    'optional_meta' => $t['optional_meta'],
    'meta_options'  => $t['meta_options'] ?? [],
], $itemTypes)) ?>;

// Custom tree types from DB — merged into tree_type select options
var CUSTOM_TREE_TYPES = <?= json_encode(array_map(fn($t) => ['key' => $t['key'], 'label' => $t['label']], $customTypes ?? [])) ?>;
var CSRF_TOKEN_ITEM = <?= json_encode(\App\Support\CSRF::getToken()) ?>;

// Update the coords display whenever lat/lng change
function updateCoordsDisplay() {
    var lat = $('#gpsLat').val();
    var lng = $('#gpsLng').val();
    if (lat && lng) {
        $('#gpsCoordsText').text(parseFloat(lat).toFixed(5) + ', ' + parseFloat(lng).toFixed(5));
        $('#gpsCoordsDisplay').show();
    } else {
        $('#gpsCoordsDisplay').hide();
    }
}

$('#gpsLat, #gpsLng').on('change', updateCoordsDisplay);
updateCoordsDisplay();

$('#clearGps').on('click', function() {
    $('#gpsLat').val('').trigger('change');
    $('#gpsLng').val('').trigger('change');
    $('#gpsAccuracy').val('');
    $('#gpsSource').val('manual');
    $('#gpsStatus').hide();
});

$('#itemType').on('change', function() {
    var type = $(this).val();
    if (!type || !itemTypeMeta[type]) { $('#metaFields').hide(); return; }
    var fields = itemTypeMeta[type].required_meta.concat(itemTypeMeta[type].optional_meta);
    var metaOpts = itemTypeMeta[type].meta_options || {};
    var html = '';
    fields.forEach(function(key) {
        var label = key.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
        html += '<div class="form-group"><label class="form-label">' + label + '</label>';
        var opts = metaOpts[key];
        if (opts && opts.length) {
            html += '<select name="meta[' + key + ']" class="form-input form-input--touch" id="metaSelect_' + key + '">';
            html += '<option value="">— Select —</option>';
            opts.forEach(function(opt) {
                var oLabel = opt.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                // Skip "other" — we'll add it after custom types
                if (opt !== 'other') {
                    html += '<option value="' + opt + '">' + oLabel + '</option>';
                }
            });
            // For tree_type: inject custom types before "Other"
            if (key === 'tree_type' && CUSTOM_TREE_TYPES.length) {
                html += '<optgroup label="Custom">';
                CUSTOM_TREE_TYPES.forEach(function(ct) {
                    html += '<option value="' + ct.key + '">' + ct.label + '</option>';
                });
                html += '</optgroup>';
            }
            // Always add Other last
            if (opts.indexOf('other') !== -1) {
                html += '<option value="other">Other (specify below)</option>';
            }
            html += '</select>';
            // For tree_type: add "Other" text input + save-as-new button
            if (key === 'tree_type') {
                html += '<div id="customTreeWrap" style="display:none;margin-top:8px">'
                      + '<input type="text" id="customTreeLabel" class="form-input form-input--touch" placeholder="Enter tree type name…" style="margin-bottom:6px">'
                      + '<button type="button" id="customTreeSave" class="btn btn-secondary btn-sm">💾 Save as new type</button>'
                      + '<span id="customTreeMsg" style="font-size:.78rem;margin-left:8px"></span>'
                      + '</div>';
            }
        } else {
            html += '<input type="text" name="meta[' + key + ']" class="form-input form-input--touch" placeholder="' + label + '">';
        }
        html += '</div>';
    });
    if (html) {
        $('#metaFieldsInner').html(html);
        $('#metaFields').show();
        wireCustomTreeType();
    }
    else { $('#metaFields').hide(); }
});

// GPS detection — uses shared RootedGPS service (already warming since page load)
function applyGpsPosition(pos) {
    var lat = pos.lat.toFixed(7);
    var lng = pos.lng.toFixed(7);
    var latEl = document.getElementById('gpsLat');
    var lngEl = document.getElementById('gpsLng');
    latEl.value = lat; latEl.dispatchEvent(new Event('change'));
    lngEl.value = lng; lngEl.dispatchEvent(new Event('change'));
    $('#gpsLat, #gpsLng').trigger('change');
    $('#gpsAccuracy').val(Math.round(pos.accuracy));
    $('#gpsSource').val('device');
    $('#gpsStatus').text('✅ Located ±' + Math.round(pos.accuracy) + 'm — drag pin to adjust.').show();
    $('#detectGps').prop('disabled', false).text('📍 Locate Me');
}

$('#detectGps').on('click', function() {
    if (!navigator.geolocation) {
        $('#gpsStatus').text('⚠️ Geolocation not supported. Tap map to place pin.').show();
        return;
    }
    $(this).prop('disabled', true).text('⏳ Detecting…');
    $('#gpsStatus').text('📡 Locating…').show();

    RootedGPS.get(function(pos) {
        if (!pos) {
            $('#gpsStatus').text('🔒 Location unavailable — enable it in browser settings, then tap again.').show();
            $('#detectGps').prop('disabled', false).text('📍 Locate Me');
            return;
        }
        applyGpsPosition(pos);
    }, 20000); // accept up to 20s old (GPS has been warming since page load)
});

function wireCustomTreeType() {
    var sel   = document.getElementById('metaSelect_tree_type');
    var wrap  = document.getElementById('customTreeWrap');
    var input = document.getElementById('customTreeLabel');
    var saveBtn = document.getElementById('customTreeSave');
    var msg   = document.getElementById('customTreeMsg');
    if (!sel || !wrap) return;

    sel.addEventListener('change', function() {
        wrap.style.display = sel.value === 'other' ? 'block' : 'none';
        if (sel.value !== 'other') msg.textContent = '';
    });

    saveBtn.addEventListener('click', function() {
        var label = input.value.trim();
        if (!label) { msg.textContent = '⚠️ Enter a name first.'; return; }
        saveBtn.disabled = true;
        saveBtn.textContent = '⏳ Saving…';
        msg.textContent = '';

        var fd = new FormData();
        fd.append('label', label);
        fd.append('_token', CSRF_TOKEN_ITEM);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.APP_BASE + '/settings/tree-types/add', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', function() {
            var res; try { res = JSON.parse(xhr.responseText); } catch(e) {}
            saveBtn.disabled = false;
            saveBtn.textContent = '💾 Save as new type';
            if (res && res.success) {
                // Add to select if not already present
                if (!sel.querySelector('option[value="' + res.key + '"]')) {
                    var opt = document.createElement('option');
                    opt.value = res.key;
                    opt.textContent = res.label;
                    // Insert before "other" option
                    var otherOpt = sel.querySelector('option[value="other"]');
                    sel.insertBefore(opt, otherOpt);
                    // Also push into CUSTOM_TREE_TYPES for future rebuilds
                    CUSTOM_TREE_TYPES.push({key: res.key, label: res.label});
                }
                sel.value = res.key;
                wrap.style.display = 'none';
                msg.textContent = '';
                input.value = '';
            } else {
                msg.textContent = '⚠️ ' + (res && res.error ? res.error : 'Save failed.');
            }
        });
        xhr.addEventListener('error', function() {
            saveBtn.disabled = false;
            saveBtn.textContent = '💾 Save as new type';
            msg.textContent = '⚠️ Network error.';
        });
        xhr.send(fd);
    });
}
</script>
