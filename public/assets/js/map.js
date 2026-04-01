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

    function typeColor(type) {
        return (TYPE_CONFIG[type] || { color: '#888' }).color;
    }
    function typeLabel(type) {
        return (TYPE_CONFIG[type] || { label: type }).label;
    }
    function typeIcon(type) {
        return (TYPE_CONFIG[type] || { icon: '📍' }).icon;
    }

    // -------------------------------------------------------------------------
    // Build map
    // -------------------------------------------------------------------------
    var map = L.map('map', { zoomControl: true }).setView(
        [MAP_DEFAULT_LAT || 41.9, MAP_DEFAULT_LNG || 12.5],
        15
    );

    // Satellite on by default
    var satelliteLayer = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 20, attribution: 'Tiles &copy; Esri &mdash; Source: Esri, DigitalGlobe, GeoEye, i-cubed, USDA FSA, USGS, AEX, Getmapping, Aerogrid, IGN, IGP, swisstopo' }
    ).addTo(map);

    // OSM road overlay (labels on top of satellite)
    var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 21,
        opacity: 0.35,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    var useSatellite = true;

    // Layer groups by type
    var layerGroups = {};
    var allItems    = [];
    var markerMap   = {};  // id → marker
    var polygonMap  = {};  // id → polygon layer

    // -------------------------------------------------------------------------
    // Custom marker icon
    // -------------------------------------------------------------------------
    function makeIcon(type, size) {
        size = size || 32;
        var color = typeColor(type);
        var emoji = typeIcon(type);
        var svg = [
            '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 40 40">',
            '<circle cx="20" cy="20" r="18" fill="' + color + '" stroke="#fff" stroke-width="3"/>',
            '<text x="20" y="26" font-size="16" text-anchor="middle">' + emoji + '</text>',
            '</svg>',
        ].join('');
        return L.divIcon({
            html: svg,
            className: '',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
            popupAnchor: [0, -(size / 2)],
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
        // Clear existing layers
        Object.keys(layerGroups).forEach(function (t) { map.removeLayer(layerGroups[t]); });
        layerGroups = {};
        markerMap   = {};
        polygonMap  = {};

        // Group items by type
        items.forEach(function (item) {
            if (!layerGroups[item.type]) {
                layerGroups[item.type] = L.layerGroup().addTo(map);
            }

            // Render GPS pin
            if (item.lat && item.lng) {
                var marker = L.marker([item.lat, item.lng], { icon: makeIcon(item.type) });
                marker.bindPopup(buildPopup(item));
                marker.on('click', function () { showItemInfo(item); });
                marker.addTo(layerGroups[item.type]);
                markerMap[item.id] = marker;
            }

            // Render boundary polygon if present
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
            item.gps_accuracy
                ? '<div class="map-popup-acc">GPS ±' + Math.round(item.gps_accuracy) + 'm</div>'
                : '',
            '<div class="map-popup-actions">',
            '<a href="' + MAP_ITEM_URL + item.id + '" class="btn btn-primary btn-xs">View</a>',
            BOUNDARY_TYPES.indexOf(item.type) >= 0
                ? ' <button class="btn btn-secondary btn-xs" onclick="window.mapDrawForItem(' + item.id + ')">Draw boundary</button>'
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
            BOUNDARY_TYPES.indexOf(item.type) >= 0
                ? ' <button class="btn btn-secondary btn-sm" id="infoDrawBtn">Draw boundary</button>'
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
        var gj = L.geoJSON(item.boundary, {
            style: {
                color: typeColor(item.type),
                weight: 2,
                opacity: 0.8,
                fillColor: typeColor(item.type),
                fillOpacity: 0.15,
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
        Object.keys(layerGroups).sort().forEach(function (type) {
            var count = allItems.filter(function (i) { return i.type === type; }).length;
            var label = document.createElement('label');
            label.className = 'map-layer-toggle';
            label.innerHTML =
                '<input type="checkbox" class="layer-toggle" data-type="' + type + '" checked> ' +
                '<span class="layer-dot" style="background:' + typeColor(type) + '"></span>' +
                typeLabel(type) + ' <span class="layer-count">(' + count + ')</span>';
            container.appendChild(label);
        });

        container.addEventListener('change', function (e) {
            if (!e.target.classList.contains('layer-toggle')) return;
            var type = e.target.dataset.type;
            if (type === 'all') {
                var checked = e.target.checked;
                container.querySelectorAll('.layer-toggle[data-type]').forEach(function (cb) {
                    if (cb.dataset.type !== 'all') {
                        cb.checked = checked;
                        toggleLayer(cb.dataset.type, checked);
                    }
                });
            } else {
                toggleLayer(type, e.target.checked);
            }
        });
    }

    function toggleLayer(type, visible) {
        if (!layerGroups[type]) return;
        if (visible) {
            map.addLayer(layerGroups[type]);
        } else {
            map.removeLayer(layerGroups[type]);
        }
    }

    // -------------------------------------------------------------------------
    // My Location button
    // -------------------------------------------------------------------------
    document.getElementById('mapLocateMe').addEventListener('click', function () {
        if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
        navigator.geolocation.getCurrentPosition(function (pos) {
            map.setView([pos.coords.latitude, pos.coords.longitude], 17);
            L.circle([pos.coords.latitude, pos.coords.longitude], {
                radius: pos.coords.accuracy,
                color: '#3c8dbc',
                fillOpacity: 0.1,
            }).addTo(map).bindPopup('You are here (±' + Math.round(pos.coords.accuracy) + 'm)').openPopup();
        }, function () {
            alert('Could not determine your location.');
        });
    });

    // -------------------------------------------------------------------------
    // Boundary drawing
    // -------------------------------------------------------------------------
    var drawingActive = false;
    var drawnPoints   = [];
    var tempMarkers   = [];
    var tempPolyline  = null;
    var tempPolygon   = null;

    document.getElementById('mapDrawToggle').addEventListener('click', function () {
        toggleDrawMode(!drawingActive);
    });

    document.getElementById('cancelDraw').addEventListener('click', function () {
        toggleDrawMode(false);
    });

    document.getElementById('clearBoundary').addEventListener('click', clearDraw);

    function toggleDrawMode(active) {
        drawingActive = active;
        document.getElementById('boundaryPanel').style.display = active ? 'block' : 'none';
        document.getElementById('mapDrawToggle').textContent   = active ? '✕ Stop Drawing' : '✏️ Draw Boundary';
        map.getContainer().style.cursor = active ? 'crosshair' : '';
        if (!active) clearDraw();
    }

    function clearDraw() {
        drawnPoints = [];
        tempMarkers.forEach(function (m) { map.removeLayer(m); });
        tempMarkers = [];
        if (tempPolyline) { map.removeLayer(tempPolyline); tempPolyline = null; }
        if (tempPolygon)  { map.removeLayer(tempPolygon);  tempPolygon  = null; }
        document.getElementById('boundaryStatus').textContent = '';
    }

    map.on('click', function (e) {
        if (!drawingActive) return;
        drawnPoints.push([e.latlng.lat, e.latlng.lng]);

        var dot = L.circleMarker([e.latlng.lat, e.latlng.lng], {
            radius: 5, color: '#e74c3c', fillColor: '#e74c3c', fillOpacity: 1,
        }).addTo(map);
        tempMarkers.push(dot);

        updateTempShape();
        document.getElementById('boundaryStatus').textContent =
            drawnPoints.length + ' point(s). Double-click last point to finish.';
    });

    map.on('dblclick', function (e) {
        if (!drawingActive || drawnPoints.length < 3) return;
        L.DomEvent.stop(e);
        finishPolygon();
    });

    function updateTempShape() {
        if (tempPolyline) map.removeLayer(tempPolyline);
        if (drawnPoints.length >= 2) {
            tempPolyline = L.polyline(drawnPoints, { color: '#e74c3c', dashArray: '5,5' }).addTo(map);
        }
    }

    function finishPolygon() {
        if (tempPolyline) { map.removeLayer(tempPolyline); tempPolyline = null; }
        if (tempPolygon)  { map.removeLayer(tempPolygon);  }
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
        if (drawnPoints.length < 3 && !tempPolygon) {
            alert('Draw a polygon first (at least 3 points, double-click to finish).'); return;
        }
        if (!tempPolygon && drawnPoints.length >= 3) finishPolygon();

        var geojson = {
            type: 'Polygon',
            coordinates: [drawnPoints.map(function (p) { return [p[1], p[0]]; })],
        };
        geojson.coordinates[0].push(geojson.coordinates[0][0]); // close ring

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
                    loadItems(); // refresh
                } else {
                    status.textContent = '❌ ' + res.message;
                }
            })
            .catch(function () {
                status.textContent = '❌ Network error. Try again.';
            });
    });

    // Expose so popup buttons can call it
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
        var candidates = items.filter(function (i) {
            return BOUNDARY_TYPES.indexOf(i.type) >= 0;
        });
        candidates.sort(function (a, b) { return a.type.localeCompare(b.type) || a.name.localeCompare(b.name); });
        candidates.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = typeLabel(item.type) + ': ' + item.name;
            sel.appendChild(opt);
        });
    }

    // -------------------------------------------------------------------------
    // Satellite toggle (double-click map title area)
    // -------------------------------------------------------------------------
    var satBtn = document.createElement('button');
    satBtn.className = 'btn btn-secondary btn-sm';
    satBtn.textContent = '🗺 Map';
    satBtn.style.cssText = 'position:absolute;top:8px;right:8px;z-index:999;';
    document.getElementById('map').appendChild(satBtn);
    satBtn.addEventListener('click', function () {
        useSatellite = !useSatellite;
        if (useSatellite) {
            satelliteLayer.addTo(map);
            osmLayer.addTo(map);
            satBtn.textContent = '🗺 Map';
        } else {
            map.removeLayer(satelliteLayer);
            map.removeLayer(osmLayer);
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
