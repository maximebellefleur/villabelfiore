/* Rooted — Items JS (GPS detect, dynamic fields) */
$(function () {

    // GPS detection — if not already wired inline in view
    $('#detectGps').on('click', function () {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }
        var $btn = $(this);
        $btn.text('Detecting…').prop('disabled', true);

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                $('#gpsLat').val(pos.coords.latitude.toFixed(7));
                $('#gpsLng').val(pos.coords.longitude.toFixed(7));
                $('#gpsAccuracy').val(Math.round(pos.coords.accuracy));
                $('#gpsSource').val('device');
                $('#gpsStatus').text(
                    'Location detected. Accuracy: ' + Math.round(pos.coords.accuracy) + 'm. Please confirm before saving.'
                ).show();
                $btn.text('Re-detect').prop('disabled', false);
            },
            function () {
                $('#gpsStatus').text('Could not detect location. Please enter coordinates manually.').show();
                $btn.text('Detect Location').prop('disabled', false);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });

});
