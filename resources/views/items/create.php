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
            <button type="button" class="btn btn-primary btn-sm" id="detectGps">Detect my GPS</button>
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
var itemTypeMeta = <?= json_encode(array_map(fn($t) => ['required_meta' => $t['required_meta'], 'optional_meta' => $t['optional_meta']], $itemTypes)) ?>;

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
    var html = '';
    fields.forEach(function(key) {
        var label = key.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
        html += '<div class="form-group"><label class="form-label">' + label + '</label>';
        html += '<input type="text" name="meta[' + key + ']" class="form-input form-input--touch" placeholder="' + label + '"></div>';
    });
    if (html) { $('#metaFieldsInner').html(html); $('#metaFields').show(); }
    else { $('#metaFields').hide(); }
});

$('#detectGps').on('click', function() {
    if (!navigator.geolocation) {
        $('#gpsStatus').text('Geolocation not supported by your browser.').show();
        return;
    }
    var $btn = $(this);
    $btn.prop('disabled', true).text('Detecting…');
    $('#gpsStatus').text('Requesting location — allow access if prompted.').show();

    navigator.geolocation.getCurrentPosition(function(pos) {
        var lat = pos.coords.latitude.toFixed(7);
        var lng = pos.coords.longitude.toFixed(7);
        $('#gpsLat').val(lat).trigger('change');
        $('#gpsLng').val(lng).trigger('change');
        $('#gpsAccuracy').val(Math.round(pos.coords.accuracy));
        $('#gpsSource').val('device');
        $('#gpsStatus').text('✅ ±' + Math.round(pos.coords.accuracy) + 'm — verify the pin on the map.').show();
        $btn.prop('disabled', false).text('Detect my GPS');
    }, function(err) {
        var msgs = {
            1: '⚠️ Permission denied — allow location in browser settings.',
            2: '⚠️ Position unavailable — place pin manually on the map.',
            3: '⚠️ Timed out — try again outdoors.',
        };
        $('#gpsStatus').text(msgs[err.code] || '⚠️ Could not detect. Place pin on map.').show();
        $btn.prop('disabled', false).text('Detect my GPS');
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
});
</script>
