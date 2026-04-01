/* Rooted — Mini map for item create/edit forms */
(function () {
    'use strict';

    var latInput = document.getElementById('gpsLat');
    var lngInput = document.getElementById('gpsLng');
    var mapDiv   = document.getElementById('miniMap');

    if (!latInput || !lngInput || !mapDiv) return;

    var initialLat = parseFloat(latInput.value) || (window.MINI_MAP_LAT || 41.9);
    var initialLng = parseFloat(lngInput.value) || (window.MINI_MAP_LNG || 12.5);
    var hasCoords  = !!latInput.value && !!lngInput.value;

    var miniMap = L.map('miniMap').setView([initialLat, initialLng], hasCoords ? 16 : 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 21,
        attribution: '© OpenStreetMap',
    }).addTo(miniMap);

    var marker = null;

    if (hasCoords) {
        marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(miniMap);
        bindMarkerDrag(marker);
    }

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

    // Invalidate size after form layout shifts (tabs, collapsibles)
    setTimeout(function () { miniMap.invalidateSize(); }, 300);

}());
