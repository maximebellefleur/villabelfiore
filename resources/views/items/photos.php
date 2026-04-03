<?php
$photoSlots = [
    'identification_photo'  => ['label' => 'ID Photo',    'icon' => '🪪', 'desc' => 'Main identification shot'],
    'yearly_refresh_north'  => ['label' => 'North',       'icon' => '⬆️', 'desc' => 'Facing north'],
    'yearly_refresh_south'  => ['label' => 'South',       'icon' => '⬇️', 'desc' => 'Facing south'],
    'yearly_refresh_east'   => ['label' => 'East',        'icon' => '➡️', 'desc' => 'Facing east'],
    'yearly_refresh_west'   => ['label' => 'West',        'icon' => '⬅️', 'desc' => 'Facing west'],
    'harvest_photo'         => ['label' => 'Harvest',     'icon' => '🌾', 'desc' => 'Harvest photo'],
    'general_attachment'    => ['label' => 'General',     'icon' => '📎', 'desc' => 'Any other photo'],
];
$uploadUrl  = url('/items/' . (int)$item['id'] . '/attachments');
$csrfToken  = e(\App\Support\CSRF::getToken());
?>
<div class="photos-page">

<div class="photos-header">
    <a href="<?= url('/items/' . (int)$item['id']) ?>" class="photos-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div class="photos-header-info">
        <h1 class="photos-title"><?= e($item['name']) ?></h1>
        <p class="photos-subtitle">Photos &amp; Attachments</p>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Upload hint banner -->
<div class="photos-hint">
    <span class="photos-hint-icon">📸</span>
    <span>Tap any card to upload or replace a photo — it uploads instantly from your camera or gallery.</span>
</div>

<div class="photos-grid">
<?php foreach ($photoSlots as $catKey => $slot):
    $att = $byCategory[$catKey] ?? null;
    $hasPhoto = $att && str_starts_with($att['mime_type'] ?? '', 'image/');
    $inputId  = 'upload_' . $catKey;
?>
<div class="photo-card <?= $hasPhoto ? 'photo-card--filled' : 'photo-card--empty' ?>" id="card_<?= $catKey ?>">

    <!-- Clickable upload zone -->
    <label class="photo-card-zone" for="<?= $inputId ?>" title="Tap to upload <?= e($slot['label']) ?>">
        <?php if ($hasPhoto): ?>
            <img src="<?= url('/attachments/' . (int)$att['id'] . '/download') ?>"
                 class="photo-card-img" loading="lazy" alt="<?= e($slot['label']) ?>">
            <div class="photo-card-overlay">
                <span class="photo-card-overlay-icon">🔄</span>
                <span>Replace</span>
            </div>
        <?php else: ?>
            <div class="photo-card-placeholder">
                <span class="photo-card-add-icon">+</span>
                <span class="photo-card-add-label">Add Photo</span>
            </div>
        <?php endif; ?>

        <!-- Progress overlay (hidden by default) -->
        <div class="photo-card-progress" id="progress_<?= $catKey ?>">
            <div class="photo-card-spinner"></div>
            <span>Uploading…</span>
        </div>
    </label>

    <!-- Slot label -->
    <div class="photo-card-footer">
        <span class="photo-card-icon"><?= $slot['icon'] ?></span>
        <div class="photo-card-meta">
            <span class="photo-card-label"><?= e($slot['label']) ?></span>
            <?php if ($hasPhoto): ?>
            <span class="photo-card-date"><?= date('d M Y', strtotime($att['uploaded_at'])) ?></span>
            <?php else: ?>
            <span class="photo-card-date text-muted">No photo</span>
            <?php endif; ?>
        </div>
        <?php if ($hasPhoto): ?>
        <form method="POST" action="<?= url('/attachments/' . (int)$att['id'] . '/trash') ?>" class="photo-card-del-form">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <button type="submit" class="photo-card-del" onclick="return confirm('Remove this photo?')" title="Remove">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Hidden file input — triggers AJAX upload -->
    <input type="file" id="<?= $inputId ?>" name="file" accept="image/*,application/pdf"
           class="photo-file-input" capture="environment"
           data-category="<?= e($catKey) ?>" data-item="<?= (int)$item['id'] ?>">

    <!-- Error message area -->
    <div class="photo-card-error" id="err_<?= $catKey ?>"></div>
</div>
<?php endforeach; ?>
</div>

</div><!-- .photos-page -->

<script>
(function() {
    var uploadUrl  = <?= json_encode($uploadUrl) ?>;
    var csrfToken  = <?= json_encode(\App\Support\CSRF::getToken()) ?>;

    // Compress image using Canvas before upload
    // Resizes to max 1920px, converts to JPEG at 82% quality
    // Typical phone photo: 8MB → ~400KB
    function compressImage(file, callback) {
        var isImage = file.type.indexOf('image/') === 0;
        if (!isImage || file.type === 'image/gif') { callback(file); return; }

        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var MAX = 1920;
                var w = img.width, h = img.height;
                var ratio = Math.min(MAX / w, MAX / h, 1);
                var cw = Math.round(w * ratio), ch = Math.round(h * ratio);
                var canvas = document.createElement('canvas');
                canvas.width = cw; canvas.height = ch;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, cw, ch);
                canvas.toBlob(function(blob) {
                    if (!blob) { callback(file); return; }
                    var compressed = new File([blob],
                        file.name.replace(/\.[^.]+$/, '') + '.jpg',
                        { type: 'image/jpeg', lastModified: Date.now() });
                    callback(compressed);
                }, 'image/jpeg', 0.82);
            };
            img.onerror = function() { callback(file); };
            img.src = e.target.result;
        };
        reader.onerror = function() { callback(file); };
        reader.readAsDataURL(file);
    }

    function doUpload(file, catKey) {
        var card     = document.getElementById('card_' + catKey);
        var progress = document.getElementById('progress_' + catKey);
        var input    = card.querySelector('.photo-file-input');

        clearError(catKey);
        progress.style.display = 'flex';
        progress.querySelector('span').textContent = 'Compressing…';
        card.classList.add('photo-card--uploading');

        compressImage(file, function(compressed) {
            progress.querySelector('span').textContent = 'Uploading…';

            var fd = new FormData();
            fd.append('file', compressed);
            fd.append('category', catKey);
            fd.append('_token', csrfToken);
            fd.append('_ajax', '1');
            fd.append('_redirect', window.location.pathname);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl, true);

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var pct = Math.round(e.loaded / e.total * 100);
                    progress.querySelector('span').textContent = pct < 100 ? pct + '%…' : 'Processing…';
                }
            });

            xhr.addEventListener('load', function() {
                progress.style.display = 'none';
                card.classList.remove('photo-card--uploading');

                var res;
                try { res = JSON.parse(xhr.responseText); } catch(ex) { res = null; }

                if (xhr.status === 200 && res && res.success) {
                    var zone  = card.querySelector('.photo-card-zone');
                    var imgEl = card.querySelector('.photo-card-img');
                    if (imgEl) {
                        imgEl.src = res.data.url + '?v=' + Date.now();
                    } else {
                        var newImg = document.createElement('img');
                        newImg.src = res.data.url + '?v=' + Date.now();
                        newImg.className = 'photo-card-img';
                        newImg.alt = catKey;
                        var ph = zone.querySelector('.photo-card-placeholder');
                        if (ph) ph.replaceWith(newImg);
                        var ov = document.createElement('div');
                        ov.className = 'photo-card-overlay';
                        ov.innerHTML = '<span class="photo-card-overlay-icon">🔄</span><span>Replace</span>';
                        zone.appendChild(ov);
                        card.classList.remove('photo-card--empty');
                        card.classList.add('photo-card--filled');
                    }
                    showSuccess(catKey);
                } else {
                    var msg = (res && res.message) ? res.message : (res && res.error) ? res.error : 'Upload failed — please try again.';
                    showError(catKey, msg);
                }
                if (input) input.value = '';
            });

            xhr.addEventListener('error', function() {
                progress.style.display = 'none';
                card.classList.remove('photo-card--uploading');
                showError(catKey, 'Network error — check your connection and try again.');
                if (input) input.value = '';
            });

            xhr.send(fd);
        });
    }

    document.querySelectorAll('.photo-file-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var file = input.files[0];
            if (!file) return;
            doUpload(file, input.dataset.category);
        });
    });

    function clearError(catKey) {
        var errEl = document.getElementById('err_' + catKey);
        if (errEl) { errEl.innerHTML = ''; errEl.style.display = 'none'; }
    }

    function showError(catKey, msg) {
        var errEl = document.getElementById('err_' + catKey);
        if (!errEl) return;
        // Error stays until user dismisses it with ✕
        errEl.innerHTML = '<span>' + msg + '</span><button onclick="this.parentElement.style.display=\'none\'" style="margin-left:8px;background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;color:inherit;padding:0" title="Dismiss">✕</button>';
        errEl.style.display = 'flex';
        errEl.style.alignItems = 'center';
        errEl.style.justifyContent = 'space-between';
    }

    function showSuccess(catKey) {
        var card = document.getElementById('card_' + catKey);
        card.classList.add('photo-card--success-flash');
        setTimeout(function() { card.classList.remove('photo-card--success-flash'); }, 1200);
    }
}());
</script>

<style>
/* =========================================================
   Photos Page — Modern 2026 Card Upload UI
   ========================================================= */

.photos-page {
    padding-bottom: calc(var(--bottom-nav-height, 80px) + var(--spacing-5));
}

.photos-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
    padding-bottom: var(--spacing-4);
    border-bottom: 1px solid var(--color-border);
}

.photos-back {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    text-decoration: none;
    flex-shrink: 0;
    transition: background 0.15s, box-shadow 0.15s;
}
.photos-back:hover {
    background: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary);
    text-decoration: none;
}

.photos-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 2px;
    color: var(--color-text);
}
.photos-subtitle {
    font-size: 0.8rem;
    color: var(--color-text-muted);
    margin: 0;
}

.photos-hint {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-3);
    background: rgba(45,90,39,0.06);
    border: 1px solid rgba(45,90,39,0.15);
    border-radius: 12px;
    padding: var(--spacing-3) var(--spacing-4);
    margin-bottom: var(--spacing-5);
    font-size: 0.85rem;
    color: var(--color-primary-dark, #1e3d1b);
    line-height: 1.4;
}
.photos-hint-icon { font-size: 1.3rem; flex-shrink: 0; margin-top: 1px; }

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: var(--spacing-3);
}

@media (max-width: 400px) {
    .photos-grid { grid-template-columns: repeat(2, 1fr); gap: var(--spacing-2); }
}
@media (min-width: 600px) {
    .photos-grid { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); }
}

/* Photo Card */
.photo-card {
    background: var(--color-surface-raised);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: box-shadow 0.2s, transform 0.15s;
    display: flex;
    flex-direction: column;
    position: relative;
}
.photo-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.14);
    transform: translateY(-2px);
}

.photo-card--success-flash {
    box-shadow: 0 0 0 3px var(--color-success, #27ae60), 0 6px 24px rgba(39,174,96,0.2);
}

/* Upload zone (the big tappable area) */
.photo-card-zone {
    position: relative;
    display: block;
    aspect-ratio: 1;
    cursor: pointer;
    overflow: hidden;
}

/* Filled state — shows the image */
.photo-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: opacity 0.2s, transform 0.3s;
}
.photo-card-zone:hover .photo-card-img {
    opacity: 0.72;
    transform: scale(1.04);
}

/* Overlay on hover for filled cards */
.photo-card-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.2s;
}
.photo-card-zone:hover .photo-card-overlay,
.photo-card--uploading .photo-card-overlay { opacity: 1; }
.photo-card-overlay-icon { font-size: 1.6rem; }

/* Empty state */
.photo-card-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    background: var(--color-surface);
    transition: background 0.2s;
}
.photo-card-zone:hover .photo-card-placeholder {
    background: rgba(45,90,39,0.06);
}
.photo-card-add-icon {
    font-size: 2.2rem;
    font-weight: 300;
    color: var(--color-primary);
    line-height: 1;
    opacity: 0.7;
    transition: opacity 0.2s, transform 0.2s;
}
.photo-card-zone:hover .photo-card-add-icon {
    opacity: 1;
    transform: scale(1.15);
}
.photo-card-add-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* Upload progress overlay */
.photo-card-progress {
    display: none;
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    z-index: 5;
    backdrop-filter: blur(2px);
}
.photo-card-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Card footer (label + actions) */
.photo-card-footer {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-2) var(--spacing-3);
    border-top: 1px solid var(--color-border);
    min-height: 48px;
}
.photo-card-icon { font-size: 1.1rem; flex-shrink: 0; }
.photo-card-meta { flex: 1; min-width: 0; }
.photo-card-label {
    font-size: 0.8rem;
    font-weight: 600;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.photo-card-date {
    font-size: 0.7rem;
    color: var(--color-text-muted);
    display: block;
}

.photo-card-del-form { margin: 0; }
.photo-card-del {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--color-text-muted);
    padding: 4px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    transition: color 0.15s, background 0.15s;
    flex-shrink: 0;
}
.photo-card-del:hover { color: var(--color-danger); background: rgba(192,57,43,0.08); }

/* Hidden file input */
.photo-file-input { display: none; }

/* Error message */
.photo-card-error {
    display: none;
    padding: var(--spacing-2) var(--spacing-3);
    background: #fde8e8;
    color: #922b21;
    font-size: 0.75rem;
    line-height: 1.4;
    border-top: 1px solid #f5b7b1;
    border-radius: 0 0 16px 16px;
}

/* iOS camera hint for empty cards */
.photo-card--empty .photo-card-zone::after {
    content: '';
    position: absolute;
    inset: 0;
    border: 2px dashed var(--color-border);
    border-radius: 0;
    pointer-events: none;
    transition: border-color 0.2s;
}
.photo-card--empty .photo-card-zone:hover::after {
    border-color: var(--color-primary);
    border-style: solid;
}
</style>
