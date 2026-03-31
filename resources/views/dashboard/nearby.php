<div class="page-header">
    <h1 class="page-title">Nearby Items</h1>
</div>
<div id="nearbyContainer">
    <p class="text-muted">Allow location access to find items near you.</p>
    <button class="btn btn-primary" id="detectLocation">Use My Location</button>
    <div id="nearbyResults" style="display:none">
        <ul class="card-list" id="nearbyList"></ul>
    </div>
</div>
<script>
$('#detectLocation').on('click', function() {
    if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
        $.getJSON('/api/items/nearby', { lat: pos.coords.latitude, lng: pos.coords.longitude, radius: 1 }, function(res) {
            if (res.success && res.data.length) {
                var html = '';
                res.data.forEach(function(item) {
                    html += '<li class="card"><div class="card-body"><a href="/items/'+item.id+'">'+item.name+'</a><span class="badge">'+item.type+'</span><span class="text-muted text-sm">'+parseFloat(item.distance_km).toFixed(3)+' km</span></div></li>';
                });
                $('#nearbyList').html(html);
                $('#nearbyResults').show();
            } else {
                $('#nearbyResults').html('<p class="text-muted">No items found nearby.</p>').show();
            }
        });
    }, function() { alert('Could not get location.'); });
});
</script>
