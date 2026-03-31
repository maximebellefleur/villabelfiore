<div class="page-header">
    <h1 class="page-title">Add Item</h1>
    <a href="<?= url('/items') ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/items') ?>" class="form" id="itemForm">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

            <div class="form-group">
                <label class="form-label">Item Type <span class="required">*</span></label>
                <select name="type" id="itemType" class="form-input" required>
                    <option value="">— Select Type —</option>
                    <?php foreach ($itemTypes as $typeKey => $typeCfg): ?>
                    <option value="<?= e($typeKey) ?>" <?= (getFlash('old')['type'] ?? '') === $typeKey ? 'selected' : '' ?>>
                        <?= e($typeCfg['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-input" required
                       value="<?= e(getFlash('old')['name'] ?? '') ?>" placeholder="Item name">
            </div>

            <div class="form-group">
                <label class="form-label">Parent Item <small>(optional)</small></label>
                <input type="number" name="parent_id" class="form-input" placeholder="Parent item ID"
                       value="<?= e(getFlash('old')['parent_id'] ?? '') ?>">
            </div>

            <fieldset class="fieldset" id="locationFields">
                <legend class="fieldset-legend">Location</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="gps_lat" id="gpsLat" class="form-input"
                               value="<?= e(getFlash('old')['gps_lat'] ?? '') ?>" placeholder="e.g. 41.902782">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="gps_lng" id="gpsLng" class="form-input"
                               value="<?= e(getFlash('old')['gps_lng'] ?? '') ?>" placeholder="e.g. 12.496366">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">GPS Accuracy (m)</label>
                        <input type="text" name="gps_accuracy" id="gpsAccuracy" class="form-input" readonly>
                    </div>
                    <div class="form-group" style="align-self:flex-end">
                        <button type="button" class="btn btn-secondary" id="detectGps">Detect Location</button>
                        <input type="hidden" name="gps_source" id="gpsSource" value="manual">
                    </div>
                </div>
                <div id="gpsStatus" class="text-muted text-sm" style="display:none"></div>
            </fieldset>

            <fieldset class="fieldset" id="metaFields" style="display:none">
                <legend class="fieldset-legend">Type-Specific Details</legend>
                <div id="metaFieldsInner"></div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Item</button>
                <a href="<?= url('/items') ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
var itemTypeMeta = <?= json_encode(array_map(fn($t) => ['required_meta' => $t['required_meta'], 'optional_meta' => $t['optional_meta']], $itemTypes)) ?>;

$('#itemType').on('change', function() {
    var type = $(this).val();
    if (!type || !itemTypeMeta[type]) { $('#metaFields').hide(); return; }
    var fields = itemTypeMeta[type].required_meta.concat(itemTypeMeta[type].optional_meta);
    var html = '';
    fields.forEach(function(key) {
        var label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        html += '<div class="form-group"><label class="form-label">' + label + '</label>';
        html += '<input type="text" name="meta[' + key + ']" class="form-input" placeholder="' + label + '"></div>';
    });
    if (html) { $('#metaFieldsInner').html(html); $('#metaFields').show(); }
    else { $('#metaFields').hide(); }
});

$('#detectGps').on('click', function() {
    if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
    $('#gpsStatus').text('Detecting…').show();
    navigator.geolocation.getCurrentPosition(function(pos) {
        $('#gpsLat').val(pos.coords.latitude.toFixed(7));
        $('#gpsLng').val(pos.coords.longitude.toFixed(7));
        $('#gpsAccuracy').val(Math.round(pos.coords.accuracy));
        $('#gpsSource').val('device');
        $('#gpsStatus').text('Location detected (accuracy: ' + Math.round(pos.coords.accuracy) + 'm). Please confirm before saving.').show();
    }, function() { $('#gpsStatus').text('Could not detect location. Please enter manually.').show(); });
});
</script>
