<?php $miniMapEnabled = true; ?>
<div class="page-header">
    <h1 class="page-title">Edit — <?= e($item['name']) ?></h1>
    <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/items/' . ((int)$item['id']) . '/update') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <input type="hidden" name="type" value="<?= e($item['type']) ?>">

            <div class="form-group">
                <label class="form-label">Type</label>
                <input type="text" class="form-input" value="<?= e(str_replace('_', ' ', $item['type'])) ?>" readonly disabled>
            </div>

            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-input" required value="<?= e($item['name']) ?>">
            </div>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Location</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="gps_lat" class="form-input" id="gpsLat" value="<?= e($item['gps_lat'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="gps_lng" class="form-input" id="gpsLng" value="<?= e($item['gps_lng'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group" style="margin-top:var(--spacing-2)">
                    <button type="button" class="btn btn-secondary btn-sm" id="detectGps">📍 Re-detect Location</button>
                    <input type="hidden" name="gps_source" id="gpsSource" value="<?= e($item['gps_source'] ?? 'manual') ?>">
                </div>
                <div id="gpsStatus" class="text-muted text-sm" style="display:none"></div>
                <div id="miniMap"></div>
                <p class="mini-map-hint">Click the map to move the pin, or drag the pin to adjust.</p>
            </fieldset>

            <?php foreach ($meta as $key => $value): ?>
            <div class="form-group">
                <label class="form-label"><?= e(str_replace('_', ' ', ucfirst($key))) ?></label>
                <input type="text" name="meta[<?= e($key) ?>]" class="form-input" value="<?= e($value) ?>">
            </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?= url('/items/' . ((int)$item['id'])) ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>

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
$('#detectGps').on('click', function() {
    if (!navigator.geolocation) {
        $('#gpsStatus').text('Geolocation is not supported by your browser.').show();
        return;
    }
    var $btn = $(this);
    $btn.prop('disabled', true).text('Detecting…');

    navigator.geolocation.getCurrentPosition(function(pos) {
        var lat = pos.coords.latitude.toFixed(7);
        var lng = pos.coords.longitude.toFixed(7);
        $('#gpsLat').val(lat).trigger('change');
        $('#gpsLng').val(lng).trigger('change');
        $('#gpsAccuracy').val(Math.round(pos.coords.accuracy));
        $('#gpsSource').val('device');
        $btn.prop('disabled', false).text('📍 Re-detect Location');
    }, function(err) {
        var msgs = {
            1: '⚠️ Permission denied — allow location in browser settings.',
            2: '⚠️ Position unavailable — place pin manually on the map.',
            3: '⚠️ Timed out — try again outdoors.',
        };
        $('#gpsStatus').text(msgs[err.code] || '⚠️ Could not detect. Place pin on map.').show();
        $btn.prop('disabled', false).text('📍 Re-detect Location');
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
});
</script>
