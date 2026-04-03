<?php
$categories = [
    'identification_photo' => ['label' => 'ID / Main',  'icon' => '🪪'],
    'yearly_refresh_north' => ['label' => 'North',       'icon' => '⬆️'],
    'yearly_refresh_south' => ['label' => 'South',       'icon' => '⬇️'],
    'yearly_refresh_east'  => ['label' => 'East',        'icon' => '➡️'],
    'yearly_refresh_west'  => ['label' => 'West',        'icon' => '⬅️'],
    'harvest_photo'        => ['label' => 'Harvest',     'icon' => '🌾'],
    'general_attachment'   => ['label' => 'General',     'icon' => '📎'],
];
$uploadUrl = url('/items/' . (int)$item['id'] . '/attachments');
$csrf      = e(\App\Support\CSRF::getToken());

// All attachments ordered newest first
$gallery = [];
foreach ($attachments as $att) { $gallery[$att['id']] = $att; }
$gallery = array_values($gallery);
?>

<div class="photos-page">

<!-- Header -->
<div class="photos-header">
    <a href="<?= url('/items/' . (int)$item['id']) ?>" class="photos-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div>
        <h1 class="photos-title"><?= e($item['name']) ?></h1>
        <p class="photos-subtitle">Photos &amp; Attachments</p>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- ── BIG UPLOAD BUTTON ── -->
<div class="photos-upload-card">
    <label class="photos-upload-zone" id="photosUploadZone">
        <div class="photos-upload-icon">📷</div>
        <div class="photos-upload-text">Add Photo</div>
        <div class="photos-upload-progress" id="photosProgress" style="display:none">
            <div class="photos-spinner"></div>
            <span id="photosProgressText">Compressing…</span>
        </div>
        <input type="file" id="photosFileInput" accept="image/*,application/pdf" capture="environment" style="display:none">
    </label>
    <div class="photos-upload-controls">
        <label class="photos-cat-label">Category</label>
        <select class="photos-cat-select" id="photosCatSelect">
            <?php foreach ($categories as $key => $cat): ?>
            <option value="<?= e($key) ?>"><?= $cat['icon'] ?> <?= e($cat['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="photos-upload-error" id="photosError"></div>
</div>

<!-- ── GALLERY ── -->
<?php if (!empty($gallery)): ?>
<div class="photos-gallery-head">
    <span><?= count($gallery) ?> photo<?= count($gallery) !== 1 ? 's' : '' ?></span>
</div>
<div class="photos-gallery">
<?php foreach ($gallery as $att):
    $isImg    = str_starts_with($att['mime_type'] ?? '', 'image/');
    $catLabel = $categories[$att['category'] ?? '']['label'] ?? ucwords(str_replace('_',' ',$att['category'] ?? ''));
    $catIcon  = $categories[$att['category'] ?? '']['icon'] ?? '📎';
    $dateStr  = date('M Y', strtotime($att['uploaded_at']));
?>
<div class="photos-gallery-item" id="gitem_<?= (int)$att['id'] ?>">
    <?php if ($isImg): ?>
    <div class="photos-gallery-img-wrap" onclick="openFullscreen('<?= url('/attachments/' . (int)$att['id'] . '/download') ?>')">
        <img src="<?= url('/attachments/' . (int)$att['id'] . '/download') ?>"
             class="photos-gallery-img" loading="lazy" alt="<?= e($catLabel) ?>">
        <div class="photos-gallery-zoom">🔍</div>
    </div>
    <?php else: ?>
    <div class="photos-gallery-file">📎</div>
    <?php endif; ?>
    <div class="photos-gallery-meta">
        <span class="photos-gallery-cat"><?= $catIcon ?> <?= e($catLabel) ?></span>
        <span class="photos-gallery-date"><?= $dateStr ?></span>
    </div>
    <form method="POST" action="<?= url('/attachments/' . (int)$att['id'] . '/trash') ?>"
          onsubmit="return confirm('Remove this photo?')">
        <input type="hidden" name="_token" value="<?= $csrf ?>">
        <button type="submit" class="photos-gallery-del" title="Delete">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
        </button>
    </form>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="photos-empty">No photos yet — tap the button above to add one.</div>
<?php endif; ?>

</div>

<!-- ── FULLSCREEN LIGHTBOX ── -->
<div class="photos-lightbox" id="photosLightbox" style="display:none" onclick="closeLightbox()">
    <button class="photos-lightbox-close" onclick="closeLightbox()">✕</button>
    <img class="photos-lightbox-img" id="photosLightboxImg" src="" alt="">
</div>

<script>
(function() {
    var uploadUrl = <?= json_encode($uploadUrl) ?>;
    var csrfToken = <?= json_encode(\App\Support\CSRF::getToken()) ?>;
    var zone      = document.getElementById('photosUploadZone');
    var fileInput = document.getElementById('photosFileInput');
    var catSelect = document.getElementById('photosCatSelect');
    var progEl    = document.getElementById('photosProgress');
    var progText  = document.getElementById('photosProgressText');
    var errorEl   = document.getElementById('photosError');

    zone.addEventListener('click', function(e) {
        if (e.target !== fileInput) fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        var file = fileInput.files[0];
        if (!file) return;
        errorEl.textContent = ''; errorEl.style.display = 'none';
        progEl.style.display = 'flex';
        progText.textContent = 'Compressing…';
        zone.style.pointerEvents = 'none';

        compressImage(file, function(compressed) {
            progText.textContent = 'Uploading…';
            var fd = new FormData();
            fd.append('file', compressed);
            fd.append('category', catSelect.value);
            fd.append('_token', csrfToken);
            fd.append('_ajax', '1');
            fd.append('_redirect', window.location.pathname);

            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var pct = Math.round(e.loaded/e.total*100);
                    progText.textContent = pct < 100 ? pct + '%…' : 'Saving…';
                }
            });
            xhr.addEventListener('load', function() {
                progEl.style.display = 'none';
                zone.style.pointerEvents = '';
                var res; try { res = JSON.parse(xhr.responseText); } catch(ex){}
                if (xhr.status === 200 && res && res.success) {
                    window.location.reload();
                } else {
                    var msg = (res && (res.error || res.message)) ? (res.error || res.message) : 'Upload failed — try again.';
                    showError(msg);
                }
                fileInput.value = '';
            });
            xhr.addEventListener('error', function() {
                progEl.style.display = 'none';
                zone.style.pointerEvents = '';
                showError('Network error — check connection and try again.');
                fileInput.value = '';
            });
            xhr.open('POST', uploadUrl, true);
            xhr.send(fd);
        });
    });

    function showError(msg) {
        errorEl.textContent = msg + '  ✕';
        errorEl.style.display = 'block';
        errorEl.onclick = function(){ errorEl.style.display = 'none'; };
    }

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
            img.onerror = function(){ callback(file); };
            img.src = e.target.result;
        };
        reader.onerror = function(){ callback(file); };
        reader.readAsDataURL(file);
    }
}());

function openFullscreen(url) {
    var lb = document.getElementById('photosLightbox');
    document.getElementById('photosLightboxImg').src = url;
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('photosLightbox').style.display = 'none';
    document.getElementById('photosLightboxImg').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeLightbox(); });
</script>

<style>
.photos-page { padding-bottom:calc(var(--bottom-nav-height,80px) + var(--spacing-5)); animation:fadeUp .3s ease-out; }
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}

.photos-header { display:flex;align-items:center;gap:var(--spacing-3);margin-bottom:var(--spacing-5);padding-bottom:var(--spacing-4);border-bottom:1px solid var(--color-border); }
.photos-back { display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:var(--color-surface-raised);border:1px solid var(--color-border);color:var(--color-text);text-decoration:none;flex-shrink:0;transition:background .15s; }
.photos-back:hover { background:var(--color-primary);color:#fff;border-color:var(--color-primary); }
.photos-title { font-size:1.3rem;font-weight:700;margin:0 0 2px; }
.photos-subtitle { font-size:.8rem;color:var(--color-text-muted);margin:0; }

.photos-upload-card { background:var(--color-surface-raised);border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.07);overflow:hidden;margin-bottom:var(--spacing-5); }
.photos-upload-zone { display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:160px;cursor:pointer;gap:var(--spacing-2);background:linear-gradient(135deg,rgba(45,106,79,.04),rgba(45,106,79,.02));border-bottom:1px solid var(--color-border);transition:background .2s;position:relative;overflow:hidden; }
.photos-upload-zone:hover { background:linear-gradient(135deg,rgba(45,106,79,.1),rgba(45,106,79,.05)); }
.photos-upload-icon { font-size:2.8rem;transition:transform .2s; }
.photos-upload-zone:hover .photos-upload-icon { transform:scale(1.15); }
.photos-upload-text { font-size:1rem;font-weight:700;color:var(--color-primary); }
.photos-upload-progress { position:absolute;inset:0;background:rgba(255,255,255,.9);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:var(--spacing-2);font-size:.85rem;font-weight:700; }
.photos-spinner { width:36px;height:36px;border:3px solid var(--color-border);border-top-color:var(--color-primary);border-radius:50%;animation:spin .8s linear infinite; }
@keyframes spin{to{transform:rotate(360deg)}}
.photos-upload-controls { display:flex;align-items:center;gap:var(--spacing-3);padding:var(--spacing-3) var(--spacing-4); }
.photos-cat-label { font-size:.78rem;font-weight:700;color:var(--color-text-muted);white-space:nowrap; }
.photos-cat-select { flex:1;padding:10px 14px;border:1.5px solid var(--color-border);border-radius:var(--radius-pill);font-size:.88rem;font-family:inherit;background:var(--color-surface);cursor:pointer; }
.photos-cat-select:focus { outline:none;border-color:var(--color-primary); }
.photos-upload-error { display:none;padding:var(--spacing-2) var(--spacing-4);background:#fde8e8;color:#922b21;font-size:.78rem;cursor:pointer;border-top:1px solid #f5b7b1; }

.photos-gallery-head { font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted);margin-bottom:var(--spacing-3); }
.photos-empty { text-align:center;padding:var(--spacing-8) 0;color:var(--color-text-muted);font-style:italic; }
.photos-gallery { display:grid;grid-template-columns:repeat(2,1fr);gap:var(--spacing-2); }
@media(min-width:480px){.photos-gallery{grid-template-columns:repeat(3,1fr);}}
@media(min-width:700px){.photos-gallery{grid-template-columns:repeat(4,1fr);}}

.photos-gallery-item { background:var(--color-surface-raised);border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;flex-direction:column; }
.photos-gallery-img-wrap { position:relative;aspect-ratio:1;overflow:hidden;cursor:zoom-in; }
.photos-gallery-img { width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s; }
.photos-gallery-img-wrap:hover .photos-gallery-img { transform:scale(1.06); }
.photos-gallery-zoom { position:absolute;inset:0;background:rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;font-size:1.4rem;opacity:0;transition:opacity .2s; }
.photos-gallery-img-wrap:hover .photos-gallery-zoom { opacity:1; }
.photos-gallery-file { aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:2rem;background:var(--color-surface); }
.photos-gallery-meta { padding:6px 8px 2px;flex:1; }
.photos-gallery-cat { font-size:.72rem;font-weight:700;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.photos-gallery-date { font-size:.65rem;color:var(--color-text-muted);display:block; }
.photos-gallery-item form { padding:0 6px 6px;text-align:right; }
.photos-gallery-del { background:none;border:none;cursor:pointer;color:var(--color-text-muted);padding:4px;border-radius:6px;transition:color .15s,background .15s; }
.photos-gallery-del:hover { color:var(--color-danger,#c0392b);background:rgba(192,57,43,.08); }

.photos-lightbox { position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.92);display:flex;align-items:center;justify-content:center;cursor:zoom-out; }
.photos-lightbox-close { position:absolute;top:16px;right:20px;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.3rem;cursor:pointer;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:background .15s; }
.photos-lightbox-close:hover { background:rgba(255,255,255,.3); }
.photos-lightbox-img { max-width:94vw;max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 8px 40px rgba(0,0,0,.6);cursor:default; }
</style>
