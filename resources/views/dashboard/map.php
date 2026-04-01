<?php $mapEnabled = true; ?>
<div class="page-header">
    <h1 class="page-title">Land Map</h1>
    <div class="page-header-actions">
        <span class="text-muted" id="mapItemCount">Loading…</span>
        <button class="btn btn-secondary" id="mapLocateMe">📍 My Location</button>
        <button class="btn btn-primary" id="mapDrawToggle">✏️ Draw Boundary</button>
    </div>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div id="mapWrap">
    <div id="map"></div>

    <!-- Sidebar: layer controls + boundary editor -->
    <div id="mapSidebar">
        <div class="map-sidebar-section">
            <h3 class="map-sidebar-title">Layers</h3>
            <label class="map-layer-toggle"><input type="checkbox" class="layer-toggle" data-type="all" checked> Show all</label>
            <div id="layerToggles"></div>
        </div>

        <div class="map-sidebar-section" id="boundaryPanel" style="display:none">
            <h3 class="map-sidebar-title">Draw Boundary</h3>
            <p class="text-muted text-sm">Click on the map to place polygon points. Double-click to finish.</p>
            <div class="form-group">
                <label class="form-label">Assign to item</label>
                <select id="boundaryItemSelect" class="form-input form-input-sm">
                    <option value="">— pick item —</option>
                </select>
            </div>
            <div class="map-boundary-actions">
                <button class="btn btn-primary btn-sm" id="saveBoundary">Save</button>
                <button class="btn btn-secondary btn-sm" id="clearBoundary">Clear</button>
                <button class="btn btn-link btn-sm" id="cancelDraw">Cancel</button>
            </div>
            <div id="boundaryStatus" class="text-sm" style="margin-top:8px"></div>
        </div>

        <div class="map-sidebar-section" id="itemInfoPanel" style="display:none">
            <h3 class="map-sidebar-title">Selected Item</h3>
            <div id="itemInfoContent"></div>
        </div>
    </div>
</div>

<script>
var MAP_DEFAULT_LAT  = <?= (float)$defaultLat ?>;
var MAP_DEFAULT_LNG  = <?= (float)$defaultLng ?>;
var MAP_CSRF_TOKEN   = '<?= e(\App\Support\CSRF::getToken()) ?>';
var MAP_BOUNDARY_URL = '<?= url('/api/map/boundary/') ?>';
var MAP_ITEMS_URL    = '<?= url('/api/map/items') ?>';
var MAP_ITEM_URL     = '<?= url('/items/') ?>';
</script>
