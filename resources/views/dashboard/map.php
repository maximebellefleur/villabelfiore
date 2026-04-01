<?php $mapEnabled = true; ?>
<div class="page-header">
    <h1 class="page-title">Land Map</h1>
    <div class="page-header-actions">
        <span class="text-muted" id="mapItemCount">Loading…</span>
        <button class="btn btn-secondary" id="mapDrawLandToggle">🗺 Set Land Boundary</button>
        <button class="btn btn-primary" id="mapAddItem">+ Add Item</button>
    </div>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (!$hasLandBoundary): ?>
<div class="alert alert-info" id="landBoundaryNotice">
    <strong>No land boundary set.</strong>
    Click <strong>Set Land Boundary</strong> to draw the outer limit of your property first.
    All zones, trees, and gardens should fall within this boundary.
</div>
<?php endif; ?>

<div id="mapWrap">
    <div id="map"></div>

    <div id="mapSidebar">

        <!-- Land boundary panel -->
        <div class="map-sidebar-section" id="landBoundaryPanel" style="display:none">
            <h3 class="map-sidebar-title">🗺 Land Boundary</h3>
            <p class="text-muted text-sm">Draw the outer limit of your entire property. Click to place points, double-click to finish.</p>
            <div class="map-boundary-actions">
                <button class="btn btn-primary btn-sm" id="saveLandBoundary">Save Land Boundary</button>
                <button class="btn btn-secondary btn-sm" id="clearLandBoundary">Clear</button>
                <button class="btn btn-link btn-sm" id="cancelLandDraw">Cancel</button>
            </div>
            <?php if ($hasLandBoundary): ?>
            <div style="margin-top:var(--spacing-2)">
                <button class="btn btn-link btn-sm text-danger" id="deleteLandBoundary">Remove boundary</button>
            </div>
            <?php endif; ?>
            <div id="landBoundaryStatus" class="text-sm" style="margin-top:8px"></div>
        </div>

        <!-- Layers -->
        <div class="map-sidebar-section">
            <h3 class="map-sidebar-title">Layers</h3>
            <label class="map-layer-toggle"><input type="checkbox" class="layer-toggle" data-type="all" checked> Show all</label>
            <div id="layerToggles"></div>
        </div>

        <!-- Zone boundary panel (accessible from item popups) -->
        <div class="map-sidebar-section" id="boundaryPanel" style="display:none">
            <h3 class="map-sidebar-title">Draw Zone Boundary</h3>
            <p class="text-muted text-sm">Click on the map to place points. Double-click to finish. Click a placed point to remove it and all points after.</p>
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

        <!-- Selected item info -->
        <div class="map-sidebar-section" id="itemInfoPanel" style="display:none">
            <h3 class="map-sidebar-title">Selected Item</h3>
            <div id="itemInfoContent"></div>
        </div>

    </div>
</div>

<!-- Add Item slide-up sheet -->
<div class="map-add-sheet" id="mapAddSheet" aria-hidden="true">
    <div class="map-add-sheet-backdrop" id="mapAddSheetBackdrop"></div>
    <div class="map-add-sheet-panel">
        <div class="map-add-sheet-header">
            <h2 class="map-add-sheet-title">Add Item</h2>
            <button class="map-add-sheet-close" id="mapAddSheetClose" aria-label="Close">&#10005;</button>
        </div>
        <div class="map-add-sheet-body">
            <div class="map-add-sheet-coords" id="mapAddCoords">
                <span class="gps-coords-icon">📍</span>
                <span id="mapAddCoordsText">Tap the map to set location</span>
            </div>
            <div class="form-group">
                <label class="form-label">Item Type <span class="required">*</span></label>
                <select id="mapAddType" class="form-input form-input--touch" required>
                    <option value="">— Select type —</option>
                    <?php foreach ($itemTypes as $typeKey => $typeCfg): ?>
                    <option value="<?= e($typeKey) ?>"><?= e($typeCfg['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Name <span class="required">*</span></label>
                <input type="text" id="mapAddName" class="form-input form-input--touch" placeholder="e.g. Olive #12, North Garden…">
            </div>
            <div id="mapAddError" class="map-add-error" style="display:none"></div>
        </div>
        <div class="map-add-sheet-footer">
            <button class="btn btn-primary btn-full" id="mapAddSubmit">Save Item</button>
            <a id="mapAddOpenFull" href="<?= url('/items/create') ?>" class="btn btn-secondary btn-full">Open Full Form</a>
        </div>
    </div>
</div>

<script>
var MAP_DEFAULT_LAT       = <?= (float)$defaultLat ?>;
var MAP_DEFAULT_LNG       = <?= (float)$defaultLng ?>;
var MAP_CSRF_TOKEN        = '<?= e(\App\Support\CSRF::getToken()) ?>';
var MAP_BOUNDARY_URL      = '<?= url('/api/map/boundary/') ?>';
var MAP_LAND_BOUNDARY_URL = '<?= url('/api/map/land-boundary') ?>';
var MAP_ITEMS_URL         = '<?= url('/api/map/items') ?>';
var MAP_API_ITEMS_URL     = '<?= url('/api/items') ?>';
var MAP_ITEM_URL          = '<?= url('/items/') ?>';
var MAP_LAND_NAME         = '<?= e($landName) ?>';
var MAP_HAS_LAND_BOUNDARY = <?= $hasLandBoundary ? 'true' : 'false' ?>;
var MAP_LAND_BOUNDARY     = <?= $landBoundaryJson ?>;
</script>
