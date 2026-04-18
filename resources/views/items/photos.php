<?php
$categories = [
    'identification_photo' => ['label' => 'ID / Main',   'icon' => '🪪'],
    'yearly_refresh_north' => ['label' => 'North',        'icon' => '⬆️'],
    'yearly_refresh_south' => ['label' => 'South',        'icon' => '⬇️'],
    'yearly_refresh_east'  => ['label' => 'East',         'icon' => '➡️'],
    'yearly_refresh_west'  => ['label' => 'West',         'icon' => '⬅️'],
    'harvest_photo'        => ['label' => 'Harvest',      'icon' => '🌾'],
    'treatment_photo'      => ['label' => 'Treatment',    'icon' => '💊'],
    'general_attachment'   => ['label' => 'General',      'icon' => '📎'],
];
// Custom categories in use (passed from controller)
foreach (($customCategories ?? []) as $cc) {
    if (!isset($categories[$cc])) {
        $categories[$cc] = ['label' => $cc, 'icon' => '🏷️'];
    }
}
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

<!-- ── UPLOAD BUTTONS ── -->
<div class="photos-upload-card">
    <div class="photos-upload-controls" id="photosUploadZone">
        <select class="photos-cat-select" id="photosCatSelect">
            <?php foreach ($categories as $key => $cat): ?>
            <option value="<?= e($key) ?>"><?= $cat['icon'] ?> <?= e($cat['label']) ?></option>
            <?php endforeach; ?>
            <option value="__custom__">🏷️ Other…</option>
        </select>
        <label class="photos-add-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="12" cy="12" r="3.5"/><circle cx="16.5" cy="7.5" r="1"/></svg>
            Add Photos
            <input type="file" id="photosFileInput" accept="image/*,application/pdf" multiple style="display:none">
        </label>
    </div>
    <!-- Custom category input (shown when Other… is selected) -->
    <div id="photosCustomCatRow" style="display:none;padding:0 var(--spacing-3) var(--spacing-2)">
        <input type="text" id="photosCustomCat" class="photos-cat-select"
               style="border-radius:var(--radius-pill)"
               placeholder="Category name (e.g. Infestation)" maxlength="80">
    </div>
    <!-- Caption row (always visible) -->
    <div style="padding:0 var(--spacing-3) var(--spacing-2)">
        <input type="text" id="photosCaptionInput" class="photos-cat-select"
               style="border-style:dashed;border-radius:var(--radius-pill)"
               placeholder="Optional caption / legend…" maxlength="500">
    </div>
    <!-- Progress bar -->
    <div class="photos-progress-wrap" id="photosProgress" style="display:none">
        <div class="photos-progress-bar-outer">
            <div class="photos-progress-bar-fill" id="photosProgressFill" style="width:0%"></div>
        </div>
        <span class="photos-progress-label" id="photosProgressText">Compressing…</span>
    </div>
    <div class="photos-upload-success" id="photosSuccess" style="display:none">
        ✅ Photo saved!
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
    $catKey   = $att['category'] ?? 'general_attachment';
    $catLabel = $categories[$catKey]['label'] ?? ucwords(str_replace('_',' ',$catKey));
    $catIcon  = $categories[$catKey]['icon'] ?? '📎';
    $dateStr  = date('M Y', strtotime($att['uploaded_at']));
    $attId    = (int)$att['id'];
?>
<div class="photos-gallery-item" id="gitem_<?= $attId ?>">
    <?php if ($isImg): ?>
    <div class="photos-gallery-img-wrap" onclick="openFullscreen('<?= url('/attachments/' . $attId . '/download') ?>')">
        <img src="<?= url('/attachments/' . $attId . '/download') ?>"
             class="photos-gallery-img" loading="lazy" alt="<?= e($catLabel) ?>">
        <div class="photos-gallery-zoom">🔍</div>
    </div>
    <?php else: ?>
    <div class="photos-gallery-file">📎</div>
    <?php endif; ?>

    <!-- Category (tap to change) -->
    <div class="photos-gallery-meta">
        <span class="photos-gallery-cat photos-cat-trigger"
              data-att-id="<?= $attId ?>"
              data-current="<?= e($catKey) ?>"
              title="Tap to change category">
            <?= $catIcon ?> <?= e($catLabel) ?> ✎
        </span>
        <span class="photos-gallery-date"><?= $dateStr ?></span>
    </div>

    <!-- Caption (tap to edit) -->
    <?php $captionVal = $att['caption'] ?? ''; ?>
    <div class="photos-caption-wrap" id="captionWrap_<?= $attId ?>">
        <span class="photos-caption-text photos-caption-trigger"
              data-att-id="<?= $attId ?>"
              title="Tap to edit caption">
            <?= $captionVal ? e($captionVal) : '<span class="photos-caption-empty">+ add caption</span>' ?>
        </span>
    </div>
    <!-- Inline caption editor (hidden by default) -->
    <div class="photos-caption-edit" id="captionEdit_<?= $attId ?>" style="display:none">
        <input type="text" class="photos-caption-input-inline" data-att-id="<?= $attId ?>"
               value="<?= e($captionVal) ?>" placeholder="Caption…" maxlength="500">
        <div class="photos-caption-edit-btns">
            <button type="button" class="photos-caption-save" data-att-id="<?= $attId ?>">✓ Save</button>
            <button type="button" class="photos-caption-cancel" data-att-id="<?= $attId ?>">✕ Cancel</button>
        </div>
    </div>

    <!-- Inline category selector (hidden by default) -->
    <div class="photos-cat-edit" id="catEdit_<?= $attId ?>" style="display:none">
        <select class="photos-cat-inline-select" data-att-id="<?= $attId ?>">
            <?php foreach ($categories as $key => $cat): ?>
            <option value="<?= e($key) ?>" <?= ($key === $catKey) ? 'selected' : '' ?>>
                <?= $cat['icon'] ?> <?= e($cat['label']) ?>
            </option>
            <?php endforeach; ?>
            <option value="__custom__">🏷️ Other…</option>
        </select>
        <button type="button" class="photos-cat-cancel" data-att-id="<?= $attId ?>">✕</button>
    </div>

    <!-- Delete with inline Yes/No (no window.confirm — broken in PWA) -->
    <div class="photos-gallery-footer">
        <button type="button" class="photos-del-trigger" data-att-id="<?= $attId ?>" title="Delete">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
            Delete
        </button>
        <span class="photos-del-confirm" id="delConfirm_<?= $attId ?>" style="display:none">
            <form method="POST" action="<?= url('/attachments/' . $attId . '/trash') ?>" style="display:inline">
                <input type="hidden" name="_token" value="<?= $csrf ?>">
                <button type="submit" class="photos-del-yes">✓ Yes</button>
            </form>
            <button type="button" class="photos-del-no" data-att-id="<?= $attId ?>">✕ No</button>
        </span>
    </div>
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
    var uploadUrl  = <?= json_encode($uploadUrl) ?>;
    var csrfToken  = <?= json_encode(\App\Support\CSRF::getToken()) ?>;
    var zone        = document.getElementById('photosUploadZone');
    var catSelect   = document.getElementById('photosCatSelect');
    var customCatRow= document.getElementById('photosCustomCatRow');
    var customCatIn = document.getElementById('photosCustomCat');
    var captionIn   = document.getElementById('photosCaptionInput');
    var progEl      = document.getElementById('photosProgress');
    var fillEl      = document.getElementById('photosProgressFill');
    var progText    = document.getElementById('photosProgressText');
    var errorEl     = document.getElementById('photosError');
    var successEl   = document.getElementById('photosSuccess');

    // Show/hide custom category input
    catSelect.addEventListener('change', function() {
        customCatRow.style.display = catSelect.value === '__custom__' ? 'block' : 'none';
    });

    // Upload queue — sequential so server isn't overwhelmed
    var _queue      = [];
    var _queueTotal = 0;
    var _queueDone  = 0;

    function enqueueFiles(files) {
        if (!files || !files.length) return;
        _queue      = Array.from(files);
        _queueTotal = _queue.length;
        _queueDone  = 0;
        errorEl.textContent = ''; errorEl.style.display = 'none';
        successEl.style.display = 'none';
        zone.style.pointerEvents = 'none';
        zone.style.opacity = '0.5';
        uploadNext();
    }

    function uploadNext() {
        if (!_queue.length) {
            // All done
            fillEl.style.width = '100%';
            progText.textContent = _queueTotal > 1
                ? '✅ ' + _queueTotal + ' photos saved!'
                : '✅ Photo saved!';
            setTimeout(function() {
                progEl.style.display = 'none';
                zone.style.pointerEvents = '';
                zone.style.opacity = '';
                successEl.style.display = 'block';
                document.getElementById('photosFileInput').value = '';
                setTimeout(function() { window.location.reload(); }, 800);
            }, 400);
            return;
        }

        var file     = _queue.shift();
        _queueDone  += 1;
        var label    = _queueTotal > 1 ? _queueDone + ' / ' + _queueTotal + ' · ' : '';

        fillEl.style.width = '0%';
        progEl.style.display = 'block';
        progText.textContent = label + 'Compressing…';

        compressImage(file, function(compressed) {
            fillEl.style.width = '5%';
            progText.textContent = label + 'Uploading…';

            var fd = new FormData();
            fd.append('file', compressed);
            if (catSelect.value === '__custom__') {
                fd.append('category', '__custom__');
                fd.append('custom_category', customCatIn ? customCatIn.value.trim() : '');
            } else {
                fd.append('category', catSelect.value);
            }
            if (captionIn && captionIn.value.trim()) fd.append('caption', captionIn.value.trim());
            fd.append('_token', csrfToken);
            fd.append('_ajax', '1');
            fd.append('_redirect', window.location.pathname);

            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var pct = Math.min(95, Math.round(e.loaded / e.total * 90) + 5);
                    fillEl.style.width = pct + '%';
                    progText.textContent = label + (pct < 95 ? pct + '%' : 'Saving…');
                }
            });
            xhr.addEventListener('load', function() {
                var res; try { res = JSON.parse(xhr.responseText); } catch(ex){}
                if (xhr.status === 200 && res && res.success) {
                    uploadNext(); // proceed to next file
                } else {
                    progEl.style.display = 'none';
                    zone.style.pointerEvents = '';
                    zone.style.opacity = '';
                    var msg = (res && (res.error || res.message))
                        ? (res.error || res.message)
                        : ('Upload failed (HTTP ' + xhr.status + '): ' + xhr.responseText.substring(0,120).replace(/<[^>]+>/g,'').trim());
                    showError(msg);
                    document.getElementById('photosFileInput').value = '';
                }
            });
            xhr.addEventListener('error', function() {
                progEl.style.display = 'none';
                zone.style.pointerEvents = '';
                zone.style.opacity = '';
                showError('Network error — check connection and try again.');
                document.getElementById('photosFileInput').value = '';
            });
            xhr.open('POST', uploadUrl, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(fd);
        });
    }

    document.getElementById('photosFileInput').addEventListener('change', function() {
        enqueueFiles(this.files);
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

// ── Delete confirm (no window.confirm — broken in PWA)
document.querySelectorAll('.photos-del-trigger').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.attId;
        btn.style.display = 'none';
        document.getElementById('delConfirm_' + id).style.display = 'inline-flex';
    });
});
document.querySelectorAll('.photos-del-no').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.attId;
        document.getElementById('delConfirm_' + id).style.display = 'none';
        document.querySelector('.photos-del-trigger[data-att-id="' + id + '"]').style.display = '';
    });
});

// ── Category tap-to-edit
var CSRF_TOKEN = <?= json_encode(\App\Support\CSRF::getToken()) ?>;
document.querySelectorAll('.photos-cat-trigger').forEach(function(span) {
    span.addEventListener('click', function() {
        var id = span.dataset.attId;
        span.closest('.photos-gallery-meta').style.display = 'none';
        document.getElementById('catEdit_' + id).style.display = 'flex';
    });
});
document.querySelectorAll('.photos-cat-cancel').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.attId;
        document.getElementById('catEdit_' + id).style.display = 'none';
        document.querySelector('.photos-cat-trigger[data-att-id="' + id + '"]')
            .closest('.photos-gallery-meta').style.display = '';
    });
});
document.querySelectorAll('.photos-cat-inline-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var id  = sel.dataset.attId;
        var cat = sel.value;
        if (cat === '__custom__') return; // wait for text input
        saveCategory(id, cat, sel);
    });
});

function saveCategory(id, cat, sel) {
    var fd = new FormData();
    fd.append('category', cat);
    fd.append('_token', CSRF_TOKEN);
    fd.append('_ajax', '1');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.APP_BASE + '/attachments/' + id + '/category', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.addEventListener('load', function() {
        var res; try { res = JSON.parse(xhr.responseText); } catch(e){}
        var editEl = document.getElementById('catEdit_' + id);
        var metaEl = editEl.previousElementSibling; // .photos-gallery-meta (skip caption wrappers above it)
        // Walk backwards to find .photos-gallery-meta
        var node = editEl;
        while (node && !node.classList.contains('photos-gallery-meta')) node = node.previousElementSibling;
        if (res && res.success) {
            if (node) {
                node.querySelector('.photos-cat-trigger').innerHTML = cat + ' ✎';
                node.querySelector('.photos-cat-trigger').dataset.current = cat;
                node.style.display = '';
            }
        }
        editEl.style.display = 'none';
    });
    xhr.send(fd);
}

// ── Caption inline edit ─────────────────────────────────────────────────────
document.querySelectorAll('.photos-caption-trigger').forEach(function(span) {
    span.addEventListener('click', function() {
        var id = span.dataset.attId;
        document.getElementById('captionWrap_' + id).style.display = 'none';
        document.getElementById('captionEdit_' + id).style.display = 'flex';
        var inp = document.querySelector('.photos-caption-input-inline[data-att-id="' + id + '"]');
        if (inp) inp.focus();
    });
});
document.querySelectorAll('.photos-caption-cancel').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.attId;
        document.getElementById('captionEdit_' + id).style.display = 'none';
        document.getElementById('captionWrap_' + id).style.display = '';
    });
});
document.querySelectorAll('.photos-caption-save').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id  = btn.dataset.attId;
        var inp = document.querySelector('.photos-caption-input-inline[data-att-id="' + id + '"]');
        var val = inp ? inp.value.trim() : '';
        var fd  = new FormData();
        fd.append('caption', val);
        fd.append('_token', CSRF_TOKEN);
        fd.append('_ajax', '1');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.APP_BASE + '/attachments/' + id + '/caption', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.addEventListener('load', function() {
            var res; try { res = JSON.parse(xhr.responseText); } catch(e){}
            if (res && res.success) {
                var wrap = document.getElementById('captionWrap_' + id);
                var span = wrap.querySelector('.photos-caption-text');
                span.innerHTML = val
                    ? val.replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    : '<span class="photos-caption-empty">+ add caption</span>';
                document.getElementById('captionEdit_' + id).style.display = 'none';
                wrap.style.display = '';
            }
        });
        xhr.send(fd);
    });
});
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
.photos-upload-controls { display:flex;align-items:center;gap:var(--spacing-2);padding:var(--spacing-3);transition:opacity .2s; }
.photos-cat-select { flex:1;padding:10px 14px;border:1.5px solid var(--color-border);border-radius:var(--radius-pill);font-size:.88rem;font-family:inherit;background:var(--color-surface);cursor:pointer; }
.photos-cat-select:focus { outline:none;border-color:var(--color-primary); }
.photos-add-btn { display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:var(--radius-pill);background:var(--color-primary);color:#fff;font-size:.85rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:opacity .2s;flex-shrink:0; }
.photos-add-btn:active { opacity:.8; }
.photos-progress-wrap { padding:var(--spacing-2) var(--spacing-3) var(--spacing-3); }
.photos-progress-bar-outer { height:7px;background:var(--color-border);border-radius:4px;overflow:hidden;margin-bottom:5px; }
.photos-progress-bar-fill { height:100%;background:var(--color-primary);border-radius:4px;transition:width .25s ease; }
.photos-progress-label { font-size:.72rem;font-weight:700;color:var(--color-text-muted); }
.photos-upload-success { display:none;padding:var(--spacing-2) var(--spacing-4);background:#e8f5e1;color:#276749;font-size:.82rem;font-weight:700;border-top:1px solid #c3e6c8; }
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

/* Category tap-to-edit */
.photos-cat-trigger { cursor:pointer;text-decoration:none;transition:color .15s; }
.photos-cat-trigger:hover { color:var(--color-primary); }
.photos-cat-edit { display:flex;align-items:center;gap:4px;padding:4px 8px 4px; }
.photos-cat-inline-select { flex:1;padding:4px 8px;border:1.5px solid var(--color-primary);border-radius:var(--radius-pill);font-size:.72rem;font-family:inherit;background:var(--color-surface);cursor:pointer; }
.photos-cat-cancel { background:none;border:none;cursor:pointer;color:var(--color-text-muted);font-size:.85rem;padding:2px 4px;border-radius:4px;flex-shrink:0; }
.photos-cat-cancel:hover { color:var(--color-danger,#c0392b); }

/* Caption */
.photos-caption-wrap { padding:2px 8px 0; }
.photos-caption-text { font-size:.68rem;color:var(--color-text-muted);display:block;cursor:pointer;line-height:1.4;word-break:break-word;transition:color .15s; }
.photos-caption-text:hover { color:var(--color-primary); }
.photos-caption-empty { font-style:italic;opacity:.5; }
.photos-caption-edit { display:flex;flex-direction:column;gap:7px;padding:4px 8px 10px; }
.photos-caption-input-inline { width:100%;box-sizing:border-box;padding:8px 12px;border:1.5px solid var(--color-primary);border-radius:10px;font-size:.85rem;font-family:inherit;background:var(--color-surface); }
.photos-caption-edit-btns { display:flex;gap:6px; }
.photos-caption-save { flex:1;background:var(--color-primary);color:#fff;border:none;cursor:pointer;font-size:.82rem;font-weight:700;padding:8px 0;border-radius:var(--radius-pill); }
.photos-caption-cancel { background:rgba(0,0,0,.07);color:var(--color-text-muted);border:none;cursor:pointer;font-size:.82rem;font-weight:700;padding:8px 12px;border-radius:var(--radius-pill);transition:background .15s; }
.photos-caption-cancel:hover { background:rgba(0,0,0,.12); }

/* Gallery footer: delete */
.photos-gallery-footer { padding:4px 8px 8px;display:flex;align-items:center;gap:4px; }
.photos-del-trigger { display:inline-flex;align-items:center;gap:4px;background:none;border:none;cursor:pointer;color:var(--color-text-muted);font-size:.7rem;font-weight:600;padding:3px 6px;border-radius:6px;transition:color .15s,background .15s; }
.photos-del-trigger:hover { color:var(--color-danger,#c0392b);background:rgba(192,57,43,.08); }
.photos-del-confirm { display:inline-flex;align-items:center;gap:4px; }
.photos-del-yes { background:var(--color-danger,#c0392b);color:#fff;border:none;cursor:pointer;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);transition:opacity .15s; }
.photos-del-yes:hover { opacity:.85; }
.photos-del-no { background:rgba(0,0,0,.07);color:var(--color-text-muted);border:none;cursor:pointer;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);transition:background .15s; }
.photos-del-no:hover { background:rgba(0,0,0,.12); }

.photos-lightbox { position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.92);display:flex;align-items:center;justify-content:center;cursor:zoom-out; }
.photos-lightbox-close { position:absolute;top:16px;right:20px;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.3rem;cursor:pointer;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:background .15s; }
.photos-lightbox-close:hover { background:rgba(255,255,255,.3); }
.photos-lightbox-img { max-width:94vw;max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 8px 40px rgba(0,0,0,.6);cursor:default; }
</style>
