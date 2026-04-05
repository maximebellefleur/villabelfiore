<?php
$typeEmoji = ['olive_tree'=>'🫒','tree'=>'🌳','vine'=>'🍇','almond_tree'=>'🌰','garden'=>'🌿','zone'=>'🛖','orchard'=>'🏕','bed'=>'🌱','mobile_coop'=>'🐓','building'=>'🏠','water_point'=>'💧'];
$typeColor = ['olive_tree'=>'#2d6a4f','almond_tree'=>'#92400e','vine'=>'#6d28d9','tree'=>'#166534','garden'=>'#0369a1','bed'=>'#0369a1','orchard'=>'#c2410c','zone'=>'#4338ca','mobile_coop'=>'#991b1b','building'=>'#374151','water_point'=>'#0284c7'];
$categories = [
    'identification_photo' => 'ID / Main',
    'yearly_refresh_north' => 'North',
    'yearly_refresh_south' => 'South',
    'yearly_refresh_east'  => 'East',
    'yearly_refresh_west'  => 'West',
    'harvest_photo'        => 'Harvest',
    'general_attachment'   => 'General',
];
?>
<div class="qp-page">

<div class="qp-header">
    <a href="<?= url('/dashboard') ?>" class="qp-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <h1 class="qp-title">📷 Quick Photos</h1>
    <button class="qp-sort-btn" id="qpSortBtn" title="Sort by distance">📍 Nearest first</button>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (empty($items)): ?>
<div class="qp-empty"><div>🌱</div><p>No items found.</p></div>
<?php else: ?>

<div class="qp-list" id="qpList">
<?php foreach ($items as $item):
    $emoji = $typeEmoji[$item['type']] ?? '📦';
    $color = $typeColor[$item['type']] ?? '#2d6a4f';
    $hasGps = !empty($item['gps_lat']) && !empty($item['gps_lng']);
    $uploadUrl = url('/items/' . (int)$item['id'] . '/attachments');
?>
<div class="qp-card" id="qpCard_<?= (int)$item['id'] ?>"
     data-lat="<?= $hasGps ? e($item['gps_lat']) : '' ?>"
     data-lng="<?= $hasGps ? e($item['gps_lng']) : '' ?>">

    <div class="qp-card-head">
        <div class="qp-card-icon" style="background:<?= $color ?>18;color:<?= $color ?>"><?= $emoji ?></div>
        <div class="qp-card-info">
            <div class="qp-card-name"><?= e($item['name']) ?></div>
            <div class="qp-card-meta">
                <span style="color:<?= $color ?>;font-weight:700;font-size:.72rem;text-transform:uppercase"><?= ucwords(str_replace('_',' ',$item['type'])) ?></span>
                <span class="qp-card-dist" style="display:none"></span>
            </div>
        </div>
    </div>

    <!-- Category select FIRST -->
    <div class="qp-category-wrap">
        <label class="qp-category-label">Category</label>
        <select class="qp-category-select" id="qpCat_<?= (int)$item['id'] ?>">
            <?php foreach ($categories as $key => $label): ?>
            <option value="<?= e($key) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Upload buttons: camera + gallery -->
    <div class="qp-upload-btns" id="zone_<?= (int)$item['id'] ?>">
        <label class="qp-choose-btn qp-choose-camera">
            <span class="qp-choose-icon">📸</span>
            <span class="qp-choose-text">Camera</span>
            <input type="file" accept="image/*" capture="environment"
                   class="qp-file-input"
                   data-item="<?= (int)$item['id'] ?>"
                   data-upload-url="<?= e($uploadUrl) ?>">
        </label>
        <label class="qp-choose-btn qp-choose-gallery">
            <span class="qp-choose-icon">🖼️</span>
            <span class="qp-choose-text">Gallery</span>
            <input type="file" accept="image/*"
                   class="qp-file-input"
                   data-item="<?= (int)$item['id'] ?>"
                   data-upload-url="<?= e($uploadUrl) ?>">
        </label>
    </div>

    <!-- Progress -->
    <div class="qp-upload-progress" id="qpProg_<?= (int)$item['id'] ?>" style="display:none">
        <div class="qp-spinner"></div>
        <span id="qpProgText_<?= (int)$item['id'] ?>">Compressing…</span>
    </div>

    <div class="qp-card-error" id="qpErr_<?= (int)$item['id'] ?>"></div>
    <div class="qp-card-success" id="qpOk_<?= (int)$item['id'] ?>" style="display:none">✅ Photo uploaded!</div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div>

<script>
(function() {
    var CSRF = <?= json_encode(\App\Support\CSRF::getToken()) ?>;

    // Distance sort
    document.getElementById('qpSortBtn').addEventListener('click', function() {
        var btn = this;
        btn.textContent = '📡 Locating…';
        RootedGPS.get(function(pos) {
            if (!pos) { btn.textContent = '📍 Nearest first'; return; }
            var list = document.getElementById('qpList');
            var cards = Array.from(list.children);
            cards.forEach(function(c) {
                var lat = parseFloat(c.dataset.lat), lng = parseFloat(c.dataset.lng);
                c._dist = (lat && lng) ? haversineM(pos.lat, pos.lng, lat, lng) : Infinity;
                var distEl = c.querySelector('.qp-card-dist');
                if (distEl && c._dist < Infinity) {
                    distEl.textContent = '📍 ' + fmtDist(c._dist);
                    distEl.style.display = 'inline';
                }
            });
            cards.sort(function(a,b){return a._dist-b._dist;});
            cards.forEach(function(c){list.appendChild(c);});
            btn.textContent = '✅ Sorted';
        }, 15000);
    });

    // Auto-sort if GPS already warm
    var last = RootedGPS.last();
    if (last) document.getElementById('qpSortBtn').click();

    function haversineM(lat1,lon1,lat2,lon2){var R=6371000,d1=(lat2-lat1)*Math.PI/180,d2=(lon2-lon1)*Math.PI/180,a=Math.sin(d1/2)*Math.sin(d1/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(d2/2)*Math.sin(d2/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}
    function fmtDist(m){return m<1000?Math.round(m)+' m':(m/1000).toFixed(1)+' km';}

    // Compression
    function compressImage(file, callback) {
        if (file.type.indexOf('image/') !== 0) { callback(file); return; }
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var MAX = 1920, w = img.width, h = img.height;
                var ratio = Math.min(MAX/w, MAX/h, 1);
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(w*ratio); canvas.height = Math.round(h*ratio);
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function(blob) {
                    if (!blob) { callback(file); return; }
                    callback(new File([blob], file.name.replace(/\.[^.]+$/,'')+'.jpg', {type:'image/jpeg',lastModified:Date.now()}));
                }, 'image/jpeg', 0.82);
            };
            img.onerror = function(){callback(file);};
            img.src = e.target.result;
        };
        reader.onerror = function(){callback(file);};
        reader.readAsDataURL(file);
    }

    // Shared upload handler
    function handleFileInput(input) {
        var file = input.files[0];
        if (!file) return;
        var itemId  = input.dataset.item;
        var url     = input.dataset.uploadUrl;
        var zone    = document.getElementById('zone_' + itemId);
        var progEl  = document.getElementById('qpProg_' + itemId);
        var progTxt = document.getElementById('qpProgText_' + itemId);
        var errEl   = document.getElementById('qpErr_' + itemId);
        var okEl    = document.getElementById('qpOk_' + itemId);
        var catEl   = document.getElementById('qpCat_' + itemId);

        errEl.textContent = ''; errEl.style.display = 'none';
        okEl.style.display = 'none';
        zone.style.opacity = '0.5';
        zone.style.pointerEvents = 'none';
        progEl.style.display = 'flex';
        progTxt.textContent = 'Compressing…';

        compressImage(file, function(compressed) {
            progTxt.textContent = 'Uploading…';
            var fd = new FormData();
            fd.append('file', compressed);
            fd.append('category', catEl ? catEl.value : 'general_attachment');
            fd.append('_token', CSRF);
            fd.append('_ajax', '1');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.addEventListener('load', function() {
                progEl.style.display = 'none';
                zone.style.opacity = '';
                zone.style.pointerEvents = '';
                var res; try { res = JSON.parse(xhr.responseText); } catch(ex){}
                if (xhr.status === 200 && res && res.success) {
                    okEl.style.display = 'block';
                    setTimeout(function(){ okEl.style.display = 'none'; }, 3000);
                } else {
                    var msg = (res && (res.error || res.message))
                        ? (res.error || res.message)
                        : ('Upload failed (HTTP ' + xhr.status + '): ' + xhr.responseText.substring(0, 120).replace(/<[^>]+>/g,'').trim());
                    errEl.textContent = msg + ' ✕';
                    errEl.style.display = 'block';
                    errEl.onclick = function(){ errEl.style.display='none'; };
                }
                zone.querySelectorAll('input[type=file]').forEach(function(i){ i.value=''; });
            });
            xhr.addEventListener('error', function() {
                progEl.style.display = 'none';
                zone.style.opacity = '';
                zone.style.pointerEvents = '';
                errEl.textContent = 'Network error — try again. ✕';
                errEl.style.display = 'block';
                errEl.onclick = function(){ errEl.style.display='none'; };
                zone.querySelectorAll('input[type=file]').forEach(function(i){ i.value=''; });
            });
            xhr.send(fd);
        });
    }

    // Wire up all file inputs — change event + window-focus fallback for capture inputs
    document.querySelectorAll('.qp-file-input').forEach(function(input) {
        input.addEventListener('change', function() { handleFileInput(input); });

        // Android bug: capture="environment" often doesn't fire 'change' after confirm.
        // When the input is clicked, watch for window regaining focus (camera app closing)
        // and check manually if a file was selected.
        if (input.hasAttribute('capture')) {
            input.addEventListener('click', function() {
                var before = input.files.length;
                function onFocus() {
                    window.removeEventListener('focus', onFocus);
                    setTimeout(function() {
                        if (input.files.length !== before) {
                            handleFileInput(input);
                        }
                    }, 400);
                }
                window.addEventListener('focus', onFocus);
            });
        }
    });
}());
</script>

<style>
.qp-page { padding-bottom: calc(var(--bottom-nav-height,80px) + var(--spacing-5)); animation: fadeUp .3s ease-out; }
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}

.qp-header {
    display:flex; align-items:center; gap:var(--spacing-3);
    margin-bottom:var(--spacing-4); padding-bottom:var(--spacing-4);
    border-bottom:1px solid var(--color-border);
}
.qp-back {
    display:flex; align-items:center; justify-content:center;
    width:40px; height:40px; border-radius:50%;
    background:var(--color-surface-raised); border:1px solid var(--color-border);
    color:var(--color-text); text-decoration:none; flex-shrink:0;
    transition:background .15s;
}
.qp-back:hover { background:var(--color-primary); color:#fff; border-color:var(--color-primary); }
.qp-title { font-size:1.2rem; font-weight:800; margin:0; flex:1; }
.qp-sort-btn {
    background:var(--color-surface-raised); border:1.5px solid var(--color-border);
    border-radius:var(--radius-pill); padding:6px 12px;
    font-size:.78rem; font-weight:700; cursor:pointer; white-space:nowrap;
    transition:border-color .15s, background .15s;
}
.qp-sort-btn:hover { border-color:var(--color-primary); background:var(--color-primary-soft); }
.qp-empty { text-align:center; padding:var(--spacing-10); color:var(--color-text-muted); font-size:1.5rem; }
.qp-list { display:flex; flex-direction:column; gap:var(--spacing-3); }

.qp-card {
    background:var(--color-surface-raised); border-radius:16px;
    box-shadow:0 2px 12px rgba(0,0,0,.07); overflow:hidden;
}
.qp-card-head {
    display:flex; align-items:center; gap:var(--spacing-3);
    padding:var(--spacing-3) var(--spacing-3) 0;
}
.qp-card-icon {
    width:44px; height:44px; border-radius:12px; font-size:1.5rem;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.qp-card-name { font-size:.95rem; font-weight:700; }
.qp-card-meta { display:flex; gap:var(--spacing-2); align-items:center; margin-top:2px; }
.qp-card-dist { font-size:.72rem; color:var(--color-text-muted); }

/* Upload buttons */
.qp-upload-btns {
    display:flex; gap:var(--spacing-2);
    margin:0 var(--spacing-3) var(--spacing-2);
}
.qp-choose-btn {
    flex:1; display:flex; align-items:center; justify-content:center;
    gap:var(--spacing-1); padding:12px 8px; border-radius:12px;
    border:2px dashed var(--color-primary);
    background:rgba(45,106,79,.06); color:var(--color-primary);
    font-size:.85rem; font-weight:700; cursor:pointer;
    transition:background .2s, opacity .2s; flex-direction:column;
}
.qp-choose-btn:active { background:rgba(45,106,79,.14); }
.qp-choose-icon { font-size:1.5rem; }
.qp-file-input { display:none; }
.qp-upload-progress {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:var(--spacing-2); font-size:.82rem; font-weight:700;
    padding:var(--spacing-3);
}
.qp-spinner {
    width:28px; height:28px; border:3px solid var(--color-border);
    border-top-color:var(--color-primary); border-radius:50%;
    animation:spin .8s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* Category */
.qp-category-wrap {
    display:flex; align-items:center; gap:var(--spacing-2);
    padding:0 var(--spacing-3) var(--spacing-3);
}
.qp-category-label { font-size:.75rem; font-weight:700; color:var(--color-text-muted); white-space:nowrap; }
.qp-category-select {
    flex:1; padding:8px 12px; border:1.5px solid var(--color-border);
    border-radius:var(--radius-pill); font-size:.82rem; font-family:inherit;
    background:var(--color-surface); color:var(--color-text); cursor:pointer;
}
.qp-category-select:focus { outline:none; border-color:var(--color-primary); }

/* Feedback */
.qp-card-error {
    display:none; margin:0 var(--spacing-3) var(--spacing-3);
    background:#fde8e8; color:#922b21; font-size:.78rem;
    padding:8px 12px; border-radius:8px; cursor:pointer; line-height:1.4;
}
.qp-card-success {
    margin:0 var(--spacing-3) var(--spacing-3);
    background:#e8f5e1; color:#276749; font-size:.82rem; font-weight:700;
    padding:8px 12px; border-radius:8px; text-align:center;
}
</style>
