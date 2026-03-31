<div class="page-header">
    <h1 class="page-title">Edit — <?= e($item['name']) ?></h1>
    <a href="/items/<?= (int)$item['id'] ?>" class="btn btn-secondary">&larr; Back</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/items/<?= (int)$item['id'] ?>/update" class="form">
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
            <button type="button" class="btn btn-secondary btn-sm" id="detectGps">Re-detect Location</button>
            <input type="hidden" name="gps_source" id="gpsSource" value="<?= e($item['gps_source'] ?? 'manual') ?>">

            <?php foreach ($meta as $key => $value): ?>
            <div class="form-group">
                <label class="form-label"><?= e(str_replace('_', ' ', ucfirst($key))) ?></label>
                <input type="text" name="meta[<?= e($key) ?>]" class="form-input" value="<?= e($value) ?>">
            </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/items/<?= (int)$item['id'] ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
$('#detectGps').on('click', function() {
    if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
        $('#gpsLat').val(pos.coords.latitude.toFixed(7));
        $('#gpsLng').val(pos.coords.longitude.toFixed(7));
        $('#gpsSource').val('device');
        alert('Location updated. Please verify before saving.');
    }, function() { alert('Could not detect location.'); });
});
</script>
