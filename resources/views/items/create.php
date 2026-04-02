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
                    <?php foreach ($itemTypes as $typeKey => $typeCfg): ?>
                    <option value="<?= e($typeKey) ?>" <?= (getFlash('old')['type'] ?? '') === $typeKey ? 'selected' : '' ?>>
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
            html += '<select name="meta[' + key + ']" class="form-input form-input--touch">';
            html += '<option value="">— Select —</option>';
            opts.forEach(function(opt) {
                var oLabel = opt.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                html += '<option value="' + opt + '">' + oLabel + '</option>';
            });
            html += '</select>';
        } else {
            html += '<input type="text" name="meta[' + key + ']" class="form-input form-input--touch" placeholder="' + label + '">';
        }
        html += '</div>';
    });
    if (html) { $('#metaFieldsInner').html(html); $('#metaFields').show(); }
    else { $('#metaFields').hide(); }
});

// GPS detection with retry logic
var GPS_RETRIES = 2;
function doGpsDetect(attempt) {
    var opts = attempt === 0
        ? { enableHighAccuracy: true,  timeout: 10000, maximumAge: 5000  }
        : { enableHighAccuracy: false, timeout: 8000,  maximumAge: 30000 };
    if (attempt > 0) {
        $('#gpsStatus').text('📡 Retrying (' + attempt + '/' + GPS_RETRIES + ')…').show();
    }
    navigator.geolocation.getCurrentPosition(function(pos) {
        var lat = pos.coords.latitude.toFixed(7);
        var lng = pos.coords.longitude.toFixed(7);
        // Update values and fire both jQuery + native events (minimap.js uses native addEventListener)
        var latEl = document.getElementById('gpsLat');
        var lngEl = document.getElementById('gpsLng');
        latEl.value = lat; latEl.dispatchEvent(new Event('change'));
        lngEl.value = lng; lngEl.dispatchEvent(new Event('change'));
        $('#gpsLat, #gpsLng').trigger('change');
        $('#gpsAccuracy').val(Math.round(pos.coords.accuracy));
        $('#gpsSource').val('device');
        $('#gpsStatus').text('✅ Located ±' + Math.round(pos.coords.accuracy) + 'm — drag pin to adjust.').show();
        $('#detectGps').prop('disabled', false).text('📍 Locate Me');
    }, function(err) {
        if (err.code === 1) {
            $('#gpsStatus').text('🔒 Location blocked — enable it in browser settings, then tap again.').show();
            $('#detectGps').prop('disabled', false).text('📍 Locate Me');
            return;
        }
        if (attempt < GPS_RETRIES) {
            setTimeout(function() { doGpsDetect(attempt + 1); }, 1500);
        } else {
            var msgs = {
                2: '⚠️ Signal weak — move outdoors or tap the map to place a pin.',
                3: '⚠️ GPS timed out — tap the map to place a pin manually.',
            };
            $('#gpsStatus').text(msgs[err.code] || '⚠️ Location unavailable — tap the map instead.').show();
            $('#detectGps').prop('disabled', false).text('📍 Locate Me');
        }
    }, opts);
}

$('#detectGps').on('click', function() {
    if (!navigator.geolocation) {
        $('#gpsStatus').text('⚠️ Geolocation not supported. Tap map to place pin.').show();
        return;
    }
    $(this).prop('disabled', true).text('⏳ Detecting…');
    $('#gpsStatus').text('📡 Requesting your location — allow access if prompted…').show();
    doGpsDetect(0);
});
</script>
