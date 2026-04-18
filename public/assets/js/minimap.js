/* Rooted — Mini map for item create/edit forms and item show page */
(function () {
    'use strict';

    var mapDiv    = document.getElementById('miniMap');
    if (!mapDiv) return;

    var readOnly  = !!window.MINI_MAP_READONLY;
    var latInput  = document.getElementById('gpsLat');
    var lngInput  = document.getElementById('gpsLng');

    // In read-only mode (show page) use window variables; edit mode uses inputs
    var initialLat = readOnly
        ? (window.MINI_MAP_LAT || 41.9)
        : (parseFloat(latInput && latInput.value) || window.MINI_MAP_LAT || 41.9);
    var initialLng = readOnly
        ? (window.MINI_MAP_LNG || 12.5)
        : (parseFloat(lngInput && lngInput.value) || window.MINI_MAP_LNG || 12.5);
    var hasCoords  = readOnly ? true : (!!latInput && !!latInput.value && !!lngInput && !!lngInput.value);

    if (!readOnly && (!latInput || !lngInput)) return;

    var miniMap = L.map('miniMap', { zoomControl: true, dragging: !readOnly, scrollWheelZoom: !readOnly, maxZoom: 22 })
                   .setView([initialLat, initialLng], hasCoords ? 16 : 5);

    // Satellite by default — Google Maps (same source as main map, zoom 21)
    L.tileLayer(
        'https://mt{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
        { subdomains: ['0','1','2','3'], maxZoom: 22, maxNativeZoom: 21, attribution: 'Map data &copy; Google' }
    ).addTo(miniMap);

    // Road labels on top
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        opacity: 0.35,
        attribution: '© OpenStreetMap',
    }).addTo(miniMap);

    var marker = null;

    if (hasCoords) {
        marker = L.marker([initialLat, initialLng], { draggable: !readOnly }).addTo(miniMap);
        if (!readOnly) bindMarkerDrag(marker);
    }

    if (readOnly) return; // Show page — no editing needed

    // Click map to place / move marker
    miniMap.on('click', function (e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;
        setCoords(lat, lng);

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(miniMap);
            bindMarkerDrag(marker);
        }

        // Update source to manual if not already device
        var src = document.getElementById('gpsSource');
        if (src && src.value !== 'device') src.value = 'manual';
    });

    // If fields are typed in, move the marker
    function syncFromFields() {
        var lat = parseFloat(latInput.value);
        var lng = parseFloat(lngInput.value);
        if (isNaN(lat) || isNaN(lng)) return;
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(miniMap);
            bindMarkerDrag(marker);
        }
        miniMap.setView([lat, lng], 16);
    }

    latInput.addEventListener('change', syncFromFields);
    lngInput.addEventListener('change', syncFromFields);

    function bindMarkerDrag(m) {
        m.on('dragend', function () {
            var pos = m.getLatLng();
            setCoords(pos.lat, pos.lng);
            var src = document.getElementById('gpsSource');
            if (src && src.value !== 'device') src.value = 'manual';
        });
    }

    function setCoords(lat, lng) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
    }

    // Expose for other scripts on the same page (e.g. boundary walk in edit.php)
    window.miniMapLeaflet = miniMap;

    // Invalidate size after form layout shifts (tabs, collapsibles)
    setTimeout(function () { miniMap.invalidateSize(); }, 300);

}());
