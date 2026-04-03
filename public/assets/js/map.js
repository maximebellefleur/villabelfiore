/* Rooted — Land Map (Leaflet) */
(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // Type configuration: colors and icons
    // -------------------------------------------------------------------------
    var TYPE_CONFIG = {
        olive_tree:  { color: '#4a7c59', label: 'Olive Tree',   icon: '🫒' },
        almond_tree: { color: '#a0785a', label: 'Almond Tree',  icon: '🌰' },
        vine:        { color: '#7c4fa0', label: 'Vine',         icon: '🍇' },
        tree:        { color: '#2d5a27', label: 'Tree',         icon: '🌳' },
        garden:      { color: '#6abf69', label: 'Garden',       icon: '🥦' },
        bed:         { color: '#c5e1a5', label: 'Garden Bed',   icon: '🌿' },
        orchard:     { color: '#ef8c2f', label: 'Orchard',      icon: '🍊' },
        zone:        { color: '#3c8dbc', label: 'Zone',         icon: '📐' },
        prep_zone:   { color: '#00bcd4', label: 'Prep Zone',    icon: '🔧' },
        water_point: { color: '#2196f3', label: 'Water Point',  icon: '💧' },
        tool:        { color: '#9e9e9e', label: 'Tool',         icon: '🔨' },
        mobile_coop: { color: '#795548', label: 'Mobile Coop',  icon: '🐔' },
        building:    { color: '#607d8b', label: 'Building',     icon: '🏠' },
    };

    var BOUNDARY_TYPES = ['garden', 'bed', 'orchard', 'zone', 'prep_zone'];
    var LINE_TYPES = ['line']; // types that draw LineString (rows) not Polygon
    var ALL_DRAWABLE = BOUNDARY_TYPES.concat(LINE_TYPES);

    function typeColor(type) { return (TYPE_CONFIG[type] || { color: '#888' }).color; }
    function typeLabel(type) { return (TYPE_CONFIG[type] || { label: type }).label; }
    function typeIcon(type)  { return (TYPE_CONFIG[type] || { icon: '📍' }).icon; }

    // -------------------------------------------------------------------------
    // Build map
    // -------------------------------------------------------------------------
    var map = L.map('map', { zoomControl: true, maxZoom: 22 }).setView(
        [MAP_DEFAULT_LAT || 41.9, MAP_DEFAULT_LNG || 12.5],
        15
    );

    // Satellite on by default
    var satelliteLayer = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 22, maxNativeZoom: 20, attribution: 'Tiles &copy; Esri &mdash; Source: Esri, DigitalGlobe' }
    ).addTo(map);

    // OSM road overlay (labels on top of satellite)
    var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        opacity: 0.35,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    // Plain OSM base — used when satellite is off
    var plainOsmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    });

    var useSatellite = true;

    // Layer groups
    var layerGroups = {};
    var allItems    = [];
    var markerMap   = {};
    var polygonMap  = {};

    // -------------------------------------------------------------------------
    // Custom marker icon — div-based (reliable emoji on iOS/Android, no SVG text)
    // -------------------------------------------------------------------------
    function makeIcon(type, size) {
        size = size || 28;
        var color = typeColor(type);
        var emoji = typeIcon(type);
        var fs    = Math.round(size * 0.48);
        // Subtle drop-shadow, slightly smaller border, 90% opacity so clusters look lighter
        var html  = '<div style="' +
            'width:' + size + 'px;height:' + size + 'px;' +
            'background:' + color + ';' +
            'border-radius:50%;border:2px solid rgba(255,255,255,0.9);' +
            'box-shadow:0 1px 4px rgba(0,0,0,.28);' +
            'display:flex;align-items:center;justify-content:center;' +
            'font-size:' + fs + 'px;line-height:1;cursor:pointer;opacity:0.92;' +
            '">' + emoji + '</div>';
        return L.divIcon({
            html: html,
            className: '',
            iconSize:     [size, size],
            iconAnchor:   [size / 2, size],
            popupAnchor:  [0, -(size + 2)],
        });
    }

    // -------------------------------------------------------------------------
    // Load items from API
    // -------------------------------------------------------------------------
    function loadItems() {
        fetch(MAP_ITEMS_URL, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) return;
                allItems = res.data;
                renderItems(allItems);
                populateBoundarySelect(allItems);
                document.getElementById('mapItemCount').textContent =
                    allItems.filter(function (i) { return i.lat; }).length + ' items on map';
            })
            .catch(function () {
                document.getElementById('mapItemCount').textContent = 'Could not load items';
            });
    }

    function renderItems(items) {
        Object.keys(layerGroups).forEach(function (t) { map.removeLayer(layerGroups[t]); });
        layerGroups = {};
        markerMap   = {};
        polygonMap  = {};

        items.forEach(function (item) {
            if (!layerGroups[item.type]) {
                layerGroups[item.type] = L.layerGroup().addTo(map);
            }
            if (item.lat && item.lng) {
                var marker = L.marker([item.lat, item.lng], { icon: makeIcon(item.type) });
                marker.bindPopup(buildPopup(item));
                marker.on('click', function () { showItemInfo(item); });
                marker.addTo(layerGroups[item.type]);
                markerMap[item.id] = marker;

                // Show GPS accuracy circle for point items
                if (item.gps_accuracy && BOUNDARY_TYPES.indexOf(item.type) === -1) {
                    L.circle([item.lat, item.lng], {
                        radius: item.gps_accuracy,
                        color: typeColor(item.type),
                        fillColor: typeColor(item.type),
                        fillOpacity: 0.04,
                        weight: 1,
                        opacity: 0.25,
                        interactive: false,
                    }).addTo(layerGroups[item.type]);
                }
            }
            if (item.boundary) {
                renderBoundary(item);
            }
        });

        buildLayerToggles();
    }

    function buildPopup(item) {
        return [
            '<div class="map-popup">',
            '<strong>' + typeIcon(item.type) + ' ' + escHtml(item.name) + '</strong>',
            '<div class="map-popup-type">' + typeLabel(item.type) + '</div>',
            item.gps_accuracy ? '<div class="map-popup-acc">GPS ±' + Math.round(item.gps_accuracy) + 'm</div>' : '',
            '<div class="map-popup-actions">',
            '<a href="' + MAP_ITEM_URL + item.id + '" class="btn btn-primary btn-xs">View</a>',
            ALL_DRAWABLE.indexOf(item.type) >= 0
                ? ' <button class="btn btn-secondary btn-xs" onclick="window.mapDrawForItem(' + item.id + ')">' + (LINE_TYPES.indexOf(item.type) >= 0 ? 'Draw row' : 'Draw boundary') + '</button>'
                : '',
            '</div>',
            '</div>',
        ].join('');
    }

    function showItemInfo(item) {
        var panel = document.getElementById('itemInfoPanel');
        var content = document.getElementById('itemInfoContent');
        content.innerHTML = [
            '<p><strong>' + escHtml(item.name) + '</strong></p>',
            '<p class="text-muted">' + typeLabel(item.type) + '</p>',
            item.lat ? '<p class="text-sm">📍 ' + item.lat.toFixed(6) + ', ' + item.lng.toFixed(6) + '</p>' : '',
            item.gps_accuracy ? '<p class="text-sm">Accuracy: ±' + Math.round(item.gps_accuracy) + 'm</p>' : '',
            '<div style="margin-top:8px">',
            '<a href="' + MAP_ITEM_URL + item.id + '" class="btn btn-primary btn-sm">Open item</a>',
            ALL_DRAWABLE.indexOf(item.type) >= 0
                ? ' <button class="btn btn-secondary btn-sm" id="infoDrawBtn">' + (LINE_TYPES.indexOf(item.type) >= 0 ? 'Draw row' : 'Draw boundary') + '</button>'
                : '',
            '</div>',
        ].join('');
        panel.style.display = 'block';
        var drawBtn = document.getElementById('infoDrawBtn');
        if (drawBtn) {
            drawBtn.addEventListener('click', function () { window.mapDrawForItem(item.id); });
        }
    }

    function renderBoundary(item) {
        var isLine = item.boundary && item.boundary.type === 'LineString';
        var gj = L.geoJSON(item.boundary, {
            style: {
                color: typeColor(item.type),
                weight: isLine ? 5 : 2,
                opacity: isLine ? 0.85 : 0.8,
                fillColor: typeColor(item.type),
                fillOpacity: isLine ? 0 : 0.15,
                lineCap: isLine ? 'round' : 'butt',
            },
        });
        gj.bindTooltip(escHtml(item.name), { sticky: true });
        gj.on('click', function () { showItemInfo(item); });
        gj.addTo(layerGroups[item.type] || map);
        polygonMap[item.id] = gj;
    }

    // -------------------------------------------------------------------------
    // Layer toggles
    // -------------------------------------------------------------------------
    function buildLayerToggles() {
        var container = document.getElementById('layerToggles');
        container.innerHTML = '';

        // "Show / Hide all" toggle (re-created each load so it's always inside the container)
        var allLabel = document.createElement('label');
        allLabel.className = 'map-layer-toggle map-layer-toggle--all';
        allLabel.innerHTML = '<input type="checkbox" class="layer-toggle" data-type="all" checked> <strong>Show all</strong>';
        container.appendChild(allLabel);

        Object.keys(layerGroups).sort().forEach(function (type) {
            var count = allItems.filter(function (i) { return i.type === type; }).length;
            var label = document.createElement('label');
            label.className = 'map-layer-toggle';
            var dotClass = LINE_TYPES.indexOf(type) >= 0 ? 'layer-dot layer-dot--line' : 'layer-dot';
            label.innerHTML =
                '<input type="checkbox" class="layer-toggle" data-type="' + type + '" checked> ' +
                '<span class="' + dotClass + '" style="background:' + typeColor(type) + '"></span>' +
                typeLabel(type) + ' <span class="layer-count">(' + count + ')</span>';
            container.appendChild(label);
        });

        // Remove the old static "Show all" label from HTML if it still exists
        var staticAll = document.querySelector('.map-sidebar-section > .map-layer-toggle > input[data-type="all"]');
        if (staticAll) staticAll.closest('label').remove();

        container.addEventListener('change', function (e) {
            if (!e.target.classList.contains('layer-toggle')) return;
            var type = e.target.dataset.type;
            if (type === 'all') {
                var checked = e.target.checked;
                container.querySelectorAll('.layer-toggle:not([data-type="all"])').forEach(function (cb) {
                    cb.checked = checked;
                    toggleLayer(cb.dataset.type, checked);
                });
            } else {
                toggleLayer(type, e.target.checked);
                // Update "all" checkbox state
                var allCb = container.querySelector('.layer-toggle[data-type="all"]');
                var indiv = container.querySelectorAll('.layer-toggle:not([data-type="all"])');
                var allChecked = Array.from(indiv).every(function (cb) { return cb.checked; });
                if (allCb) allCb.checked = allChecked;
            }
        });
    }

    function toggleLayer(type, visible) {
        if (!layerGroups[type]) return;
        if (visible) { map.addLayer(layerGroups[type]); } else { map.removeLayer(layerGroups[type]); }
    }

    // -------------------------------------------------------------------------
    // Add Item — slide-up sheet
    // -------------------------------------------------------------------------
    var addItemMode     = false;
    var addItemMarker   = null;
    var addItemLat      = null;
    var addItemLng      = null;

    var addBtn          = document.getElementById('mapAddItem');
    var addSheet        = document.getElementById('mapAddSheet');
    var addSheetClose   = document.getElementById('mapAddSheetClose');
    var addSheetBackdrop= document.getElementById('mapAddSheetBackdrop');
    var addCoordsText   = document.getElementById('mapAddCoordsText');
    var addTypeSelect   = document.getElementById('mapAddType');
    var addNameInput    = document.getElementById('mapAddName');
    var addErrorDiv     = document.getElementById('mapAddError');
    var addSubmitBtn    = document.getElementById('mapAddSubmit');
    var addOpenFull     = document.getElementById('mapAddOpenFull');

    // -------------------------------------------------------------------------
    // Shared GPS helper — delegates to the unified RootedGPS service
    // -------------------------------------------------------------------------
    function detectGps(onSuccess, statusEl, btn) {
        if (!navigator.geolocation) {
            showGpsStatus(statusEl, '⚠️ Geolocation not supported by your browser.', 'error');
            return;
        }
        var origText = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = '⏳…'; }
        showGpsStatus(statusEl, '📡 Locating…', 'info');

        RootedGPS.get(function (pos) {
            if (btn) { btn.disabled = false; btn.textContent = origText; }
            if (!pos) {
                showGpsStatus(statusEl, '🔒 Location unavailable — check browser permissions.', 'error');
                return;
            }
            hideGpsStatus(statusEl);
            onSuccess(pos.lat, pos.lng, pos.accuracy);
        }, 15000); // accept a fix up to 15 seconds old (already warming since page load)
    }

    function showGpsStatus(el, msg, type) {
        if (!el) return;
        el.textContent = msg;
        el.className = 'map-gps-status map-gps-status--' + (type || 'info');
        el.style.display = 'block';
    }

    function hideGpsStatus(el) {
        if (!el) return;
        el.style.display = 'none';
    }

    // -------------------------------------------------------------------------
    // Floating "Locate Me" button — always visible on map
    // -------------------------------------------------------------------------
    (function addLocateMeBtn() {
        var locateBtn = document.createElement('button');
        locateBtn.className = 'map-locate-btn';
        locateBtn.title = 'Locate me';
        locateBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="8" stroke-opacity=".4"/></svg>';

        var locateStatusEl = document.createElement('div');
        locateStatusEl.id = 'locateMeStatus';
        locateStatusEl.style.cssText = 'position:absolute;bottom:80px;left:50%;transform:translateX(-50%);z-index:500;display:none;white-space:nowrap;pointer-events:none;';

        map.getContainer().appendChild(locateBtn);
        map.getContainer().appendChild(locateStatusEl);

        var locateMarker  = null;
        var locateCircle  = null;
        var locatePulse   = null;

        locateBtn.addEventListener('click', function () {
            locateBtn.classList.add('map-locate-btn--active');
            detectGps(function (lat, lng, accuracy) {
                locateBtn.classList.remove('map-locate-btn--active');

                // Remove previous
                if (locateMarker) map.removeLayer(locateMarker);
                if (locateCircle) map.removeLayer(locateCircle);
                if (locatePulse)  map.removeLayer(locatePulse);

                // Accuracy halo
                if (accuracy) {
                    locateCircle = L.circle([lat, lng], {
                        radius: accuracy, color: '#2d6a4f', fillColor: '#2d6a4f',
                        fillOpacity: 0.08, weight: 1.5, dashArray: '4 4',
                    }).addTo(map);
                }

                // Pulsing blue dot
                locateMarker = L.circleMarker([lat, lng], {
                    radius: 9, color: '#fff', weight: 3,
                    fillColor: '#2d6a4f', fillOpacity: 1,
                }).addTo(map);

                map.setView([lat, lng], Math.max(map.getZoom(), 17));
            }, locateStatusEl, locateBtn);
        });
    })();

    // GPS for Add Item sheet
    document.getElementById('mapAddGpsBtn').addEventListener('click', function () {
        var btn = this;
        var statusEl = document.getElementById('mapAddGpsStatus');
        detectGps(function (lat, lng, accuracy) {
            // Place / move the pin
            if (addItemMarker) map.removeLayer(addItemMarker);
            addItemMarker = L.marker([lat, lng], { icon: makeIcon('zone', 36), draggable: true }).addTo(map);
            addItemMarker.on('dragend', function (ev) {
                var ll = ev.target.getLatLng();
                addItemLat = ll.lat; addItemLng = ll.lng;
                addCoordsText.textContent = ll.lat.toFixed(6) + ', ' + ll.lng.toFixed(6);
            });
            addItemLat = lat; addItemLng = lng;
            addCoordsText.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6) +
                (accuracy ? '  ±' + Math.round(accuracy) + 'm' : '');
            map.setView([lat, lng], Math.max(map.getZoom(), 18));
            // Open sheet if it's not already open
            if (!addSheet.classList.contains('open')) {
                openAddSheet(lat, lng);
            }
            // Cancel pin-placement cursor mode — we already have a location
            if (addItemMode) {
                addItemMode = false;
                addBtn.textContent = '✕ Cancel';
                map.getContainer().style.cursor = '';
            }
        }, statusEl, btn);
    });

    // GPS for Land boundary panel — adds a point at current position
    document.getElementById('landGpsBtn').addEventListener('click', function () {
        var btn = this;
        var statusEl = document.getElementById('landGpsStatus');
        detectGps(function (lat, lng) {
            // Simulate a map click at that position
            var latlng = L.latLng(lat, lng);
            var landIdx = landDrawPoints.length;
            landDrawPoints.push([lat, lng]);
            var landDot = L.circleMarker([lat, lng], {
                radius: 7, color: '#2d5a27', fillColor: '#2d5a27', fillOpacity: 1, weight: 2,
            }).addTo(map);
            (function (pIdx, pMarker) {
                pMarker.on('click', function (ev) {
                    L.DomEvent.stop(ev);
                    var removed = landTempMarkers.splice(pIdx, landTempMarkers.length - pIdx);
                    removed.forEach(function (m) { map.removeLayer(m); });
                    landDrawPoints.splice(pIdx, landDrawPoints.length - pIdx);
                    if (landTempPoly) { map.removeLayer(landTempPoly); landTempPoly = null; }
                    if (landTempLine) { map.removeLayer(landTempLine); landTempLine = null; }
                    if (landDrawPoints.length >= 2) {
                        landTempLine = L.polyline(landDrawPoints, { color: '#2d5a27', dashArray: '6 3' }).addTo(map);
                    }
                    document.getElementById('landBoundaryStatus').textContent =
                        landDrawPoints.length + ' point(s) — double-click to finish.';
                });
            })(landIdx, landDot);
            landTempMarkers.push(landDot);
            if (landTempLine) map.removeLayer(landTempLine);
            if (landDrawPoints.length >= 2) {
                landTempLine = L.polyline(landDrawPoints, { color: '#2d5a27', dashArray: '6 3' }).addTo(map);
            }
            map.setView([lat, lng], Math.max(map.getZoom(), 18));
            document.getElementById('landBoundaryStatus').textContent =
                landDrawPoints.length + ' point(s) — tap GPS again or click map to continue.';
            showGpsStatus(statusEl, '✅ Point added at ' + lat.toFixed(5) + ', ' + lng.toFixed(5), 'success');
            setTimeout(function () { hideGpsStatus(statusEl); }, 2500);
        }, statusEl, btn);
    });

    // GPS for Zone boundary panel
    document.getElementById('zoneGpsBtn').addEventListener('click', function () {
        var btn = this;
        var statusEl = document.getElementById('zoneGpsStatus');
        detectGps(function (lat, lng) {
            var idx = drawnPoints.length;
            drawnPoints.push([lat, lng]);
            var dot = L.circleMarker([lat, lng], {
                radius: 7, color: '#e74c3c', fillColor: '#e74c3c', fillOpacity: 1, weight: 2,
            }).addTo(map);
            (function (pointIdx, dotMarker) {
                dotMarker.on('click', function (ev) {
                    L.DomEvent.stop(ev);
                    var removed = tempMarkers.splice(pointIdx, tempMarkers.length - pointIdx);
                    removed.forEach(function (m) { map.removeLayer(m); });
                    drawnPoints.splice(pointIdx, drawnPoints.length - pointIdx);
                    if (tempPolygon) { map.removeLayer(tempPolygon); tempPolygon = null; }
                    updateTempShape();
                    updateDrawStatus();
                });
            })(idx, dot);
            tempMarkers.push(dot);
            updateTempShape();
            map.setView([lat, lng], Math.max(map.getZoom(), 18));
            updateDrawStatus();
            showGpsStatus(statusEl, '✅ Point added at ' + lat.toFixed(5) + ', ' + lng.toFixed(5), 'success');
            setTimeout(function () { hideGpsStatus(statusEl); }, 2500);
        }, statusEl, btn);
    });

    addBtn.addEventListener('click', function () {
        if (addItemMode) {
            cancelAddItem();
        } else {
            enterAddItemMode();
        }
    });

    function enterAddItemMode() {
        // Close other draw modes
        if (drawingActive)  toggleDrawMode(false);
        if (landDrawActive) toggleLandDrawMode(false);

        addItemMode = true;
        addBtn.textContent = '✕ Cancel';
        addBtn.classList.add('btn-danger');
        addBtn.classList.remove('btn-primary');
        map.getContainer().style.cursor = 'crosshair';
        showToast('Tap the map to place the item pin');
    }

    function cancelAddItem() {
        addItemMode = false;
        addBtn.textContent = '+ Add Item';
        addBtn.classList.remove('btn-danger');
        addBtn.classList.add('btn-primary');
        map.getContainer().style.cursor = '';
        if (addItemMarker) { map.removeLayer(addItemMarker); addItemMarker = null; }
        closeAddSheet();
    }

    function openAddSheet(lat, lng) {
        addItemLat = lat;
        addItemLng = lng;
        addCoordsText.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        addTypeSelect.value = '';
        addNameInput.value  = '';
        addErrorDiv.style.display = 'none';
        addSheet.classList.add('open');
        addSheet.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(function () { addTypeSelect.focus(); }, 320);

        // Update "Open Full Form" link with pre-filled coords
        addOpenFull.href = addOpenFull.href.split('?')[0] + '?lat=' + lat + '&lng=' + lng;
    }

    function closeAddSheet() {
        addSheet.classList.remove('open');
        addSheet.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    addSheetClose.addEventListener('click', cancelAddItem);
    addSheetBackdrop.addEventListener('click', cancelAddItem);

    addSubmitBtn.addEventListener('click', function () {
        var type = addTypeSelect.value.trim();
        var name = addNameInput.value.trim();
        addErrorDiv.style.display = 'none';

        if (!type) { showSheetError('Please select an item type.'); addTypeSelect.focus(); return; }
        if (!name) { showSheetError('Please enter a name.'); addNameInput.focus(); return; }
        if (addItemLat === null) { showSheetError('Tap the map to set a location first.'); return; }

        addSubmitBtn.disabled = true;
        addSubmitBtn.textContent = 'Saving…';

        fetch(MAP_API_ITEMS_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: [
                '_token='      + encodeURIComponent(MAP_CSRF_TOKEN),
                'type='        + encodeURIComponent(type),
                'name='        + encodeURIComponent(name),
                'gps_lat='     + encodeURIComponent(addItemLat),
                'gps_lng='     + encodeURIComponent(addItemLng),
                'gps_source=map',
            ].join('&'),
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            addSubmitBtn.disabled = false;
            addSubmitBtn.textContent = 'Save Item';
            if (res.success) {
                cancelAddItem();
                loadItems();
                showToast('✅ ' + res.data.name + ' added to map');
            } else {
                var msg = res.message || 'Could not save item.';
                if (res.errors) {
                    msg = Object.values(res.errors).join(' ');
                }
                showSheetError(msg);
            }
        })
        .catch(function () {
            addSubmitBtn.disabled = false;
            addSubmitBtn.textContent = 'Save Item';
            showSheetError('Network error. Please try again.');
        });
    });

    function showSheetError(msg) {
        addErrorDiv.textContent = msg;
        addErrorDiv.style.display = 'block';
    }

    // Simple toast notification
    var toastEl = null;
    function showToast(msg) {
        if (toastEl) { clearTimeout(toastEl._timer); toastEl.remove(); }
        toastEl = document.createElement('div');
        toastEl.className = 'map-toast';
        toastEl.textContent = msg;
        document.body.appendChild(toastEl);
        toastEl._timer = setTimeout(function () { if (toastEl) { toastEl.remove(); toastEl = null; } }, 3500);
    }

    // -------------------------------------------------------------------------
    // Boundary drawing (accessible from item popup)
    // -------------------------------------------------------------------------
    var drawingActive = false;
    var drawnPoints   = [];
    var tempMarkers   = [];
    var tempPolyline  = null;
    var tempPolygon   = null;

    document.getElementById('mapDrawToggle') && document.getElementById('mapDrawToggle').addEventListener('click', function () {
        toggleDrawMode(!drawingActive);
    });

    document.getElementById('cancelDraw').addEventListener('click', function () { toggleDrawMode(false); });
    document.getElementById('clearBoundary').addEventListener('click', clearDraw);

    function toggleDrawMode(active) {
        drawingActive = active;
        document.getElementById('boundaryPanel').style.display = active ? 'block' : 'none';
        map.getContainer().style.cursor = active ? 'crosshair' : '';
        if (!active) clearDraw();
        if (active) { enterAddItemMode && cancelAddItem(); }
    }

    function clearDraw() {
        drawnPoints = [];
        tempMarkers.forEach(function (m) { map.removeLayer(m); });
        tempMarkers = [];
        if (tempPolyline) { map.removeLayer(tempPolyline); tempPolyline = null; }
        if (tempPolygon)  { map.removeLayer(tempPolygon);  tempPolygon  = null; }
        updateDrawStatus();
    }

    function updateDrawStatus() {
        var itemId = document.getElementById('boundaryItemSelect').value;
        var selectedItem = itemId && allItems.find(function (i) { return String(i.id) === String(itemId); });
        var isLineType = selectedItem && LINE_TYPES.indexOf(selectedItem.type) >= 0;
        document.getElementById('boundaryStatus').textContent =
            drawnPoints.length > 0
                ? drawnPoints.length + ' point(s) — ' + (isLineType ? 'save when done (min 2 points).' : 'double-click to finish (min 3 points). Click a point to remove it and all after.')
                : (isLineType ? 'Click to add points for the planting row (min 2).' : 'Click to add points (min 3), double-click to finish.');
    }

    function updateTempShape() {
        if (tempPolyline) map.removeLayer(tempPolyline);
        if (drawnPoints.length >= 2) {
            tempPolyline = L.polyline(drawnPoints, { color: '#e74c3c', dashArray: '5,5' }).addTo(map);
        }
    }

    function finishPolygon() {
        if (tempPolyline) { map.removeLayer(tempPolyline); tempPolyline = null; }
        if (tempPolygon)  { map.removeLayer(tempPolygon); }
        tempPolygon = L.polygon(drawnPoints, {
            color: '#e74c3c', fillColor: '#e74c3c', fillOpacity: 0.2, weight: 2,
        }).addTo(map);
        document.getElementById('boundaryStatus').textContent =
            'Polygon complete (' + drawnPoints.length + ' points). Select an item and save.';
        map.getContainer().style.cursor = '';
    }

    document.getElementById('saveBoundary').addEventListener('click', function () {
        var itemId = document.getElementById('boundaryItemSelect').value;
        if (!itemId) { alert('Please select an item to assign the boundary to.'); return; }

        var selectedItem = allItems.find(function (i) { return String(i.id) === String(itemId); });
        var isLineType = selectedItem && LINE_TYPES.indexOf(selectedItem.type) >= 0;

        var geojson;
        if (isLineType) {
            if (drawnPoints.length < 2) {
                alert('Draw at least 2 points for a planting row.'); return;
            }
            geojson = {
                type: 'LineString',
                coordinates: drawnPoints.map(function (p) { return [p[1], p[0]]; }),
            };
        } else {
            if (drawnPoints.length < 3 && !tempPolygon) {
                alert('Draw a polygon first (at least 3 points, double-click to finish).'); return;
            }
            if (!tempPolygon && drawnPoints.length >= 3) finishPolygon();
            geojson = {
                type: 'Polygon',
                coordinates: [drawnPoints.map(function (p) { return [p[1], p[0]]; })],
            };
            geojson.coordinates[0].push(geojson.coordinates[0][0]);
        }

        var status = document.getElementById('boundaryStatus');
        status.textContent = 'Saving…';

        fetch(MAP_BOUNDARY_URL + itemId, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=' + encodeURIComponent(MAP_CSRF_TOKEN) +
                  '&geojson=' + encodeURIComponent(JSON.stringify(geojson)),
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                status.textContent = '✅ ' + res.message;
                toggleDrawMode(false);
                loadItems();
            } else {
                status.textContent = '❌ ' + res.message;
            }
        })
        .catch(function () { status.textContent = '❌ Network error. Try again.'; });
    });

    window.mapDrawForItem = function (id) {
        toggleDrawMode(true);
        document.getElementById('boundaryItemSelect').value = id;
    };

    // -------------------------------------------------------------------------
    // Populate boundary item select
    // -------------------------------------------------------------------------
    function populateBoundarySelect(items) {
        var sel = document.getElementById('boundaryItemSelect');
        sel.innerHTML = '<option value="">— pick item —</option>';
        var candidates = items.filter(function (i) { return ALL_DRAWABLE.indexOf(i.type) >= 0; });
        candidates.sort(function (a, b) { return a.type.localeCompare(b.type) || a.name.localeCompare(b.name); });
        candidates.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = typeLabel(item.type) + ': ' + item.name;
            sel.appendChild(opt);
        });
    }

    // -------------------------------------------------------------------------
    // Land boundary — render + draw + save
    // -------------------------------------------------------------------------
    var landBoundaryLayer = null;

    function renderLandBoundary(geojson) {
        if (landBoundaryLayer) { map.removeLayer(landBoundaryLayer); }
        if (!geojson) return;
        landBoundaryLayer = L.geoJSON(geojson, {
            style: {
                color: '#2d5a27', weight: 3, opacity: 1,
                dashArray: '8 4',
                fillColor: '#2d5a27', fillOpacity: 0.05,
            },
        });
        landBoundaryLayer.bindTooltip(MAP_LAND_NAME + ' — Land Boundary', { sticky: false });
        landBoundaryLayer.addTo(map);
    }

    if (MAP_LAND_BOUNDARY) {
        renderLandBoundary(MAP_LAND_BOUNDARY);
        if (landBoundaryLayer) {
            map.fitBounds(landBoundaryLayer.getBounds(), { padding: [40, 40] });
        }
    }

    var landDrawActive  = false;
    var landDrawPoints  = [];
    var landTempMarkers = [];
    var landTempLine    = null;
    var landTempPoly    = null;

    document.getElementById('mapDrawLandToggle').addEventListener('click', function () {
        toggleLandDrawMode(!landDrawActive);
    });
    document.getElementById('cancelLandDraw').addEventListener('click', function () { toggleLandDrawMode(false); });
    document.getElementById('clearLandBoundary').addEventListener('click', clearLandDraw);

    function toggleLandDrawMode(active) {
        landDrawActive = active;
        document.getElementById('landBoundaryPanel').style.display = active ? 'block' : 'none';
        document.getElementById('mapDrawLandToggle').textContent = active ? '✕ Cancel Land Draw' : '🗺 Set Land Boundary';
        map.getContainer().style.cursor = active ? 'crosshair' : '';
        if (active) { toggleDrawMode(false); cancelAddItem(); }
        if (!active) clearLandDraw();
    }

    function clearLandDraw() {
        landDrawPoints = [];
        landTempMarkers.forEach(function (m) { map.removeLayer(m); });
        landTempMarkers = [];
        if (landTempLine) { map.removeLayer(landTempLine); landTempLine = null; }
        if (landTempPoly) { map.removeLayer(landTempPoly); landTempPoly = null; }
        document.getElementById('landBoundaryStatus').textContent = '';
    }

    function finishLandPolygon() {
        if (landTempLine) { map.removeLayer(landTempLine); landTempLine = null; }
        if (landTempPoly) map.removeLayer(landTempPoly);
        landTempPoly = L.polygon(landDrawPoints, {
            color: '#2d5a27', fillColor: '#2d5a27', fillOpacity: 0.08, weight: 3, dashArray: '8 4',
        }).addTo(map);
        document.getElementById('landBoundaryStatus').textContent =
            'Polygon complete (' + landDrawPoints.length + ' points). Click Save.';
        map.getContainer().style.cursor = '';
    }

    document.getElementById('saveLandBoundary').addEventListener('click', function () {
        if (landDrawPoints.length < 3 && !landTempPoly) {
            document.getElementById('landBoundaryStatus').textContent = 'Draw the boundary first (min 3 points, double-click to finish).';
            return;
        }
        if (!landTempPoly && landDrawPoints.length >= 3) finishLandPolygon();

        var geojson = {
            type: 'Polygon',
            coordinates: [landDrawPoints.map(function (p) { return [p[1], p[0]]; })],
        };
        geojson.coordinates[0].push(geojson.coordinates[0][0]);

        var status = document.getElementById('landBoundaryStatus');
        status.textContent = 'Saving…';

        fetch(MAP_LAND_BOUNDARY_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=' + encodeURIComponent(MAP_CSRF_TOKEN) +
                  '&geojson=' + encodeURIComponent(JSON.stringify(geojson)),
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                status.textContent = '✅ ' + res.message;
                renderLandBoundary(geojson);
                var notice = document.getElementById('landBoundaryNotice');
                if (notice) notice.style.display = 'none';
                toggleLandDrawMode(false);
            } else {
                status.textContent = '❌ ' + res.message;
            }
        })
        .catch(function () { status.textContent = '❌ Network error.'; });
    });

    var delLandBtn = document.getElementById('deleteLandBoundary');
    if (delLandBtn) {
        delLandBtn.addEventListener('click', function () {
            if (!confirm('Remove the land boundary?')) return;
            fetch(MAP_LAND_BOUNDARY_URL + '/delete', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_token=' + encodeURIComponent(MAP_CSRF_TOKEN),
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    if (landBoundaryLayer) { map.removeLayer(landBoundaryLayer); landBoundaryLayer = null; }
                    document.getElementById('landBoundaryPanel').style.display = 'none';
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // Map click handler — unified (Add Item / boundary drawing)
    // -------------------------------------------------------------------------
    map.on('click', function (e) {
        // Add Item mode: place/move the pin, then open sheet
        if (addItemMode) {
            if (addItemMarker) map.removeLayer(addItemMarker);
            addItemMarker = L.marker([e.latlng.lat, e.latlng.lng], {
                icon: makeIcon('zone', 36),
                draggable: true,
            }).addTo(map);
            addItemMarker.on('dragend', function (ev) {
                var ll = ev.target.getLatLng();
                addItemLat = ll.lat;
                addItemLng = ll.lng;
                addCoordsText.textContent = ll.lat.toFixed(6) + ', ' + ll.lng.toFixed(6);
            });
            openAddSheet(e.latlng.lat, e.latlng.lng);
            map.getContainer().style.cursor = '';
            addItemMode = false; // one tap is enough; can drag pin after
            addBtn.textContent = '✕ Cancel';
            return;
        }

        // Zone boundary drawing
        if (drawingActive) {
            drawnPoints.push([e.latlng.lat, e.latlng.lng]);

            var idx = drawnPoints.length - 1;
            var dot = L.circleMarker([e.latlng.lat, e.latlng.lng], {
                radius: 7, color: '#e74c3c', fillColor: '#e74c3c', fillOpacity: 1,
                weight: 2,
            }).addTo(map);

            // Click on existing point: remove it and all points after it
            (function (pointIdx, dotMarker) {
                dotMarker.on('click', function (ev) {
                    L.DomEvent.stop(ev);
                    // Remove this point and all subsequent from the visual list
                    var removed = tempMarkers.splice(pointIdx, tempMarkers.length - pointIdx);
                    removed.forEach(function (m) { map.removeLayer(m); });
                    drawnPoints.splice(pointIdx, drawnPoints.length - pointIdx);
                    if (tempPolygon) { map.removeLayer(tempPolygon); tempPolygon = null; }
                    updateTempShape();
                    updateDrawStatus();
                });
            })(idx, dot);

            tempMarkers.push(dot);
            updateTempShape();
            updateDrawStatus();
            return;
        }

        // Land boundary drawing
        if (landDrawActive) {
            landDrawPoints.push([e.latlng.lat, e.latlng.lng]);
            var landIdx = landDrawPoints.length - 1;
            var landDot = L.circleMarker([e.latlng.lat, e.latlng.lng], {
                radius: 7, color: '#2d5a27', fillColor: '#2d5a27', fillOpacity: 1, weight: 2,
            }).addTo(map);

            // Click on land boundary point: remove it and all after
            (function (pIdx, pMarker) {
                pMarker.on('click', function (ev) {
                    L.DomEvent.stop(ev);
                    var removed = landTempMarkers.splice(pIdx, landTempMarkers.length - pIdx);
                    removed.forEach(function (m) { map.removeLayer(m); });
                    landDrawPoints.splice(pIdx, landDrawPoints.length - pIdx);
                    if (landTempPoly) { map.removeLayer(landTempPoly); landTempPoly = null; }
                    if (landTempLine) { map.removeLayer(landTempLine); landTempLine = null; }
                    if (landDrawPoints.length >= 2) {
                        landTempLine = L.polyline(landDrawPoints, { color: '#2d5a27', dashArray: '6 3' }).addTo(map);
                    }
                    document.getElementById('landBoundaryStatus').textContent =
                        landDrawPoints.length + ' point(s) — double-click to finish.';
                });
            })(landIdx, landDot);

            landTempMarkers.push(landDot);
            if (landTempLine) map.removeLayer(landTempLine);
            if (landDrawPoints.length >= 2) {
                landTempLine = L.polyline(landDrawPoints, { color: '#2d5a27', dashArray: '6 3' }).addTo(map);
            }
            document.getElementById('landBoundaryStatus').textContent =
                landDrawPoints.length + ' point(s) — double-click to finish. Click a point to remove it.';
            return;
        }

        // Direct click on map: only place a pin when "+ Add Item" mode is active.
        // (Accidental taps no longer open the sheet unexpectedly.)
    });

    map.on('dblclick', function (e) {
        L.DomEvent.stop(e);
        if (drawingActive && drawnPoints.length >= 3) { finishPolygon(); return; }
        if (landDrawActive && landDrawPoints.length >= 3) { finishLandPolygon(); }
    });

    // -------------------------------------------------------------------------
    // Satellite toggle button
    // -------------------------------------------------------------------------
    var satBtn = document.createElement('button');
    satBtn.className = 'btn btn-secondary btn-sm';
    satBtn.textContent = '🗺 Map';
    satBtn.style.cssText = 'position:absolute;top:8px;right:8px;z-index:999;';
    document.getElementById('map').appendChild(satBtn);
    satBtn.addEventListener('click', function () {
        useSatellite = !useSatellite;
        if (useSatellite) {
            map.removeLayer(plainOsmLayer);
            satelliteLayer.addTo(map); osmLayer.addTo(map);
            satBtn.textContent = '🗺 Map';
        } else {
            map.removeLayer(satelliteLayer); map.removeLayer(osmLayer);
            plainOsmLayer.addTo(map);
            satBtn.textContent = '🛰 Satellite';
        }
    });

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------
    loadItems();

}());
