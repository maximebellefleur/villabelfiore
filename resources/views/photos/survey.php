<?php
$itemType  = $item['type'] ?? '';
$itemName  = $item['name'] ?? '';
$itemId    = (int)$item['id'];
$csrfToken = \App\Support\CSRF::getToken();

$hasBudding = in_array($itemType, ['olive_tree', 'almond_tree']);

$stages = [
    [
        'id'      => 'compass',
        'label'   => 'Compass Survey',
        'emoji'   => '🧭',
        'hint'    => 'Stand by your tree. Take one photo per direction — camera facing the tree.',
        'type'    => 'compass',
    ],
    [
        'id'          => 'budding',
        'label'       => 'Budding',
        'emoji'       => '🌸',
        'hint'        => 'Take 3 photos of bud development.',
        'type'        => 'photo_text',
        'count'       => 3,
        'cat'         => 'budding_photo',
        'action_type' => 'survey_budding',
        'action_label'=> 'Survey — Budding',
        'scale'       => $hasBudding,
        'placeholder' => 'Describe bud density, development stage, any concerns…',
    ],
    [
        'id'          => 'fruits',
        'label'       => 'Fruits',
        'emoji'       => '🍋',
        'hint'        => 'Take 3 photos of the current fruit set.',
        'type'        => 'photo_text',
        'count'       => 3,
        'cat'         => 'fruits_photo',
        'action_type' => 'survey_fruits',
        'action_label'=> 'Survey — Fruits',
        'scale'       => false,
        'placeholder' => 'Note fruit size, colour, quantity, drop or any damage observed…',
    ],
    [
        'id'          => 'health',
        'label'       => 'Health Check',
        'emoji'       => '🏥',
        'hint'        => 'Take 2 photos of any health observations.',
        'type'        => 'photo_text',
        'count'       => 2,
        'cat'         => 'health_photo',
        'action_type' => 'survey_health',
        'action_label'=> 'Survey — Health',
        'scale'       => false,
        'placeholder' => 'Note pests, disease, nutrient deficiencies, or treatments needed…',
    ],
];
?>
<style>
/* ── Survey page ──────────────────────────────────────── */
.sv-page { max-width:520px;margin:0 auto;padding-bottom:40px; }
.sv-header { display:flex;align-items:center;gap:12px;margin-bottom:20px; }
.sv-back { color:var(--color-text-muted);text-decoration:none;display:flex;align-items:center; }
.sv-title { font-size:1.1rem;font-weight:800;flex:1; }
.sv-badge { font-size:.75rem;font-weight:700;color:var(--color-primary);background:var(--color-primary-soft);padding:2px 10px;border-radius:999px; }

/* Progress */
.sv-progress { display:flex;gap:6px;margin-bottom:24px;align-items:center; }
.sv-dot { height:6px;border-radius:3px;background:var(--color-border);transition:all .25s;flex:1; }
.sv-dot.sv-done { background:var(--color-primary); }
.sv-dot.sv-active { background:var(--color-accent);flex:2; }

/* Stage panels */
.sv-stage { display:none; }
.sv-stage.sv-active { display:block; }
.sv-stage-head { text-align:center;margin-bottom:20px; }
.sv-emoji { font-size:2.8rem;display:block;margin-bottom:6px; }
.sv-stage-title { font-size:1.15rem;font-weight:800;margin:0 0 4px; }
.sv-hint { font-size:.84rem;color:var(--color-text-muted);margin:0; }

/* ── Compass grid ────────────────────────────────────── */
.sv-compass { display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px; }
.sv-dir-slot {
    aspect-ratio:1;border-radius:14px;border:2px dashed var(--color-border);
    background:var(--color-surface);cursor:pointer;overflow:hidden;position:relative;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:6px;transition:border-color .15s;
}
.sv-dir-slot:hover { border-color:var(--color-primary); }
.sv-dir-slot.sv-filled { border:2px solid var(--color-primary); }
.sv-dir-slot img { width:100%;height:100%;object-fit:cover;position:absolute;inset:0; }
.sv-dir-overlay {
    position:absolute;bottom:0;left:0;right:0;
    background:rgba(0,0,0,.55);color:#fff;
    font-size:.7rem;font-weight:700;text-align:center;padding:5px 4px;
    letter-spacing:.04em;
}
.sv-dir-icon { font-size:1.8rem;pointer-events:none; }
.sv-dir-label { font-size:.72rem;font-weight:700;color:var(--color-text-muted);pointer-events:none; }
.sv-dir-slot.sv-filled .sv-dir-icon,
.sv-dir-slot.sv-filled .sv-dir-label { display:none; }
.sv-dir-remove {
    position:absolute;top:6px;right:6px;
    width:24px;height:24px;border-radius:50%;
    background:rgba(0,0,0,.55);color:#fff;
    border:none;cursor:pointer;font-size:.75rem;
    display:none;align-items:center;justify-content:center;z-index:2;
}
.sv-dir-slot.sv-filled .sv-dir-remove { display:flex; }

/* Background upload banner */
.sv-bg-status {
    display:none;
    padding:8px 12px;background:var(--color-primary-soft);border-radius:8px;
    font-size:.8rem;color:var(--color-primary);font-weight:600;
    margin-bottom:16px;text-align:center;
}
.sv-bg-status.sv-visible { display:block; }

/* ── Photo grid (stages 2-4) ─────────────────────────── */
.sv-photo-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px; }
.sv-photo-slot {
    aspect-ratio:1;border-radius:12px;border:2px dashed var(--color-border);
    background:var(--color-surface);cursor:pointer;overflow:hidden;position:relative;
    display:flex;align-items:center;justify-content:center;transition:border-color .15s;
}
.sv-photo-slot:hover { border-color:var(--color-primary); }
.sv-photo-slot.sv-filled { border:2px solid var(--color-primary); }
.sv-photo-slot img { width:100%;height:100%;object-fit:cover; }
.sv-slot-plus { font-size:2rem;color:var(--color-border);pointer-events:none; }
.sv-slot-remove {
    position:absolute;top:5px;right:5px;width:24px;height:24px;border-radius:50%;
    background:rgba(0,0,0,.5);color:#fff;border:none;cursor:pointer;
    font-size:.75rem;display:flex;align-items:center;justify-content:center;
}
.sv-photo-counter { font-size:.78rem;font-weight:700;color:var(--color-accent);text-align:center;margin-bottom:12px; }

/* Budding scale */
.sv-scale-wrap { margin-bottom:16px; }
.sv-scale-label { font-size:.85rem;font-weight:700;margin-bottom:8px;text-align:center; }
.sv-scale-slider { -webkit-appearance:none;width:100%;height:8px;border-radius:4px;background:linear-gradient(to right,#ef4444,#f59e0b,#22c55e);outline:none;cursor:pointer; }
.sv-scale-slider::-webkit-slider-thumb { -webkit-appearance:none;width:28px;height:28px;border-radius:50%;background:var(--color-primary);cursor:pointer;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.2); }
.sv-scale-value { text-align:center;font-size:1.5rem;font-weight:900;color:var(--color-primary);margin-top:4px; }
.sv-scale-desc { text-align:center;font-size:.78rem;color:var(--color-text-muted); }

/* Notes textarea */
.sv-notes { width:100%;border:1.5px solid var(--color-border);border-radius:10px;padding:10px 12px;font-size:.9rem;font-family:inherit;resize:vertical;min-height:90px;background:var(--color-surface);color:var(--color-text);transition:border-color .15s;margin-bottom:16px;box-sizing:border-box; }
.sv-notes:focus { outline:none;border-color:var(--color-primary); }
.sv-notes-label { font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;display:block; }

/* Actions */
.sv-actions { display:flex;flex-direction:column;gap:8px; }
.sv-upload-progress { display:none;text-align:center;padding:8px;font-size:.84rem;color:var(--color-text-muted); }
.sv-skip { background:none;border:1.5px solid var(--color-border);color:var(--color-text-muted);border-radius:var(--radius-pill,999px);padding:10px;font-size:.85rem;font-weight:600;cursor:pointer;transition:background .12s;font-family:inherit; }
.sv-skip:hover { background:var(--color-surface); }

/* Done */
.sv-done { text-align:center;padding:40px 20px; }
.sv-done-icon { font-size:4rem;margin-bottom:12px; }
.sv-done-title { font-size:1.4rem;font-weight:800;margin-bottom:8px; }
.sv-done-text { color:var(--color-text-muted);margin-bottom:24px; }
</style>

<div class="sv-page">

<div class="sv-header">
    <a href="<?= url('/items/' . $itemId) ?>" class="sv-back">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <span class="sv-title"><?= e($itemName) ?></span>
    <span class="sv-badge"><?= e(ucwords(str_replace('_', ' ', $itemType))) ?></span>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Progress dots -->
<div class="sv-progress" id="svProgress">
    <?php foreach ($stages as $i => $s): ?>
    <div class="sv-dot <?= $i === 0 ? 'sv-active' : '' ?>" id="svDot_<?= $s['id'] ?>"></div>
    <?php endforeach; ?>
</div>

<!-- Background upload status banner -->
<div class="sv-bg-status" id="svBgStatus">📡 Uploading compass photos in background…</div>

<!-- Shared hidden file input -->
<input type="file" id="svFileInput" accept="image/*" capture="environment" style="display:none">

<!-- ── STAGE 1: Compass ── -->
<div class="sv-stage sv-active" id="svStage_compass">
    <div class="sv-stage-head">
        <span class="sv-emoji">🧭</span>
        <h2 class="sv-stage-title">Compass Survey</h2>
        <p class="sv-hint">Tap each direction to take one photo facing the tree.</p>
    </div>
    <div class="sv-compass" id="svCompassGrid">
        <?php
        $dirs = [
            ['key'=>'south','arrow'=>'⬇️','label'=>'SOUTH'],
            ['key'=>'east', 'arrow'=>'➡️','label'=>'EAST'],
            ['key'=>'north','arrow'=>'⬆️','label'=>'NORTH'],
            ['key'=>'west', 'arrow'=>'⬅️','label'=>'WEST'],
        ];
        foreach ($dirs as $d): ?>
        <div class="sv-dir-slot" id="svDir_<?= $d['key'] ?>" onclick="svPickDir('<?= $d['key'] ?>')">
            <span class="sv-dir-icon"><?= $d['arrow'] ?></span>
            <span class="sv-dir-label"><?= $d['label'] ?></span>
            <div class="sv-dir-overlay" id="svDirLabel_<?= $d['key'] ?>" style="display:none"><?= $d['arrow'] ?> <?= $d['label'] ?></div>
            <button type="button" class="sv-dir-remove" onclick="svRemoveDir('<?= $d['key'] ?>',event)">✕</button>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="font-size:.78rem;font-weight:700;color:var(--color-accent);text-align:center;margin-bottom:16px" id="svCompassCounter">0 / 4 photos</div>
    <div class="sv-actions">
        <div class="sv-upload-progress" id="svUploadProgress_compass">Uploading…</div>
        <button class="btn btn-primary btn-lg" id="svCompassContinue" onclick="svCompassContinue()" disabled>Continue →</button>
    </div>
</div>

<!-- ── STAGES 2-4: Budding / Fruits / Health ── -->
<?php foreach ($stages as $si => $stage):
    if ($stage['type'] !== 'photo_text') continue;
    $isLast = ($si === count($stages) - 1);
?>
<div class="sv-stage" id="svStage_<?= $stage['id'] ?>">
    <div class="sv-stage-head">
        <span class="sv-emoji"><?= $stage['emoji'] ?></span>
        <h2 class="sv-stage-title"><?= e($stage['label']) ?></h2>
        <p class="sv-hint"><?= e($stage['hint']) ?></p>
        <div class="sv-photo-counter" id="svCounter_<?= $stage['id'] ?>">0 / <?= $stage['count'] ?> photos</div>
    </div>

    <div class="sv-photo-grid" id="svGrid_<?= $stage['id'] ?>">
        <?php for ($p = 0; $p < $stage['count']; $p++): ?>
        <div class="sv-photo-slot" id="svSlot_<?= $stage['id'] ?>_<?= $p ?>" onclick="svPickPhoto('<?= $stage['id'] ?>',<?= $p ?>)">
            <span class="sv-slot-plus">+</span>
        </div>
        <?php endfor; ?>
    </div>

    <?php if (!empty($stage['scale'])): ?>
    <div class="sv-scale-wrap">
        <div class="sv-scale-label">Budding Intensity</div>
        <input type="range" class="sv-scale-slider" id="svScale_<?= $stage['id'] ?>" min="1" max="10" value="5"
               oninput="svUpdateScale('<?= $stage['id'] ?>', this.value)">
        <div class="sv-scale-value" id="svScaleVal_<?= $stage['id'] ?>">5</div>
        <div class="sv-scale-desc" id="svScaleDesc_<?= $stage['id'] ?>">Moderate bud development</div>
    </div>
    <?php endif; ?>

    <label class="sv-notes-label" for="svNotes_<?= $stage['id'] ?>">Notes</label>
    <textarea class="sv-notes" id="svNotes_<?= $stage['id'] ?>"
              placeholder="<?= e($stage['placeholder']) ?>"></textarea>

    <div class="sv-actions">
        <div class="sv-upload-progress" id="svUploadProgress_<?= $stage['id'] ?>">Uploading…</div>
        <button class="btn btn-primary btn-lg"
                onclick="svUploadStage('<?= $stage['id'] ?>', <?= $stage['count'] ?>, <?= $si ?>, <?= $isLast ? 'true' : 'false' ?>, '<?= $stage['cat'] ?>', '<?= $stage['action_type'] ?>', '<?= addslashes($stage['action_label']) ?>', <?= !empty($stage['scale']) ? 'true' : 'false' ?>)">
            <?= $isLast ? '📤 Upload & Finish' : '📤 Upload & Continue' ?>
        </button>
        <?php if (!$isLast): ?>
        <button class="sv-skip" onclick="svAdvance(<?= $si ?>)">Skip this stage</button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Done -->
<div class="sv-done" id="svDone" style="display:none">
    <div class="sv-done-icon">✅</div>
    <h2 class="sv-done-title">Survey Complete!</h2>
    <p class="sv-done-text">All photos and observations saved for <strong><?= e($itemName) ?></strong>.</p>
    <a href="<?= url('/items/' . $itemId . '/photos') ?>" class="btn btn-primary btn-lg" style="display:block;text-align:center;margin-bottom:10px">View Photos</a>
    <a href="<?= url('/items/' . $itemId) ?>" class="btn btn-ghost btn-lg" style="display:block;text-align:center">Back to Item</a>
</div>

</div><!-- /.sv-page -->

<script>
(function() {
    var CSRF       = <?= json_encode($csrfToken) ?>;
    var ITEM_ID    = <?= $itemId ?>;
    var BASE       = window.APP_BASE || '';
    var UPLOAD_URL = BASE + '/items/' + ITEM_ID + '/attachments';
    var ACTIONS_URL= BASE + '/items/' + ITEM_ID + '/actions';

    var stages = <?= json_encode(array_values($stages)) ?>;

    // Compass state
    var compassPhotos = { south: null, east: null, north: null, west: null };
    var _pendingDir  = null;

    // Photo stages state: { stageId: [File|null, ...] }
    var stagePhotos = {};
    stages.forEach(function(s) {
        if (s.type === 'photo_text') stagePhotos[s.id] = [];
    });

    var _pendingSlot = null; // { stageId, idx }

    var fileInput = document.getElementById('svFileInput');

    // ── Scale descriptions ────────────────────────────────────────────────────
    var SCALE_DESC = {1:'Almost no buds',2:'Very sparse budding',3:'Low bud density',4:'Below average budding',5:'Moderate bud development',6:'Above average budding',7:'Good bud density',8:'Very good budding',9:'Excellent bud development',10:'Exceptional — super well loaded!'};

    function svUpdateScale(stageId, v) {
        var el = document.getElementById('svScaleVal_' + stageId);
        var de = document.getElementById('svScaleDesc_' + stageId);
        if (el) el.textContent = v;
        if (de) de.textContent = SCALE_DESC[v] || '';
    }
    window.svUpdateScale = svUpdateScale;

    // ── File input routing ───────────────────────────────────────────────────
    fileInput.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;
        var file = this.files[0];

        if (_pendingDir !== null) {
            // Compass direction
            var dir = _pendingDir;
            _pendingDir = null;
            compassPhotos[dir] = file;
            var slot = document.getElementById('svDir_' + dir);
            var url  = URL.createObjectURL(file);
            var overlay = document.getElementById('svDirLabel_' + dir);
            // Clear slot children except button, add img
            var btn = slot.querySelector('.sv-dir-remove');
            slot.innerHTML = '';
            var img = document.createElement('img');
            img.src = url;
            slot.appendChild(img);
            if (overlay) { overlay.style.display = 'block'; slot.appendChild(overlay); }
            slot.appendChild(btn);
            slot.classList.add('sv-filled');
            updateCompassCounter();
        } else if (_pendingSlot !== null) {
            // Photo stage slot
            var info = _pendingSlot;
            _pendingSlot = null;
            stagePhotos[info.stageId][info.idx] = file;
            var slotEl = document.getElementById('svSlot_' + info.stageId + '_' + info.idx);
            if (slotEl) {
                slotEl.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt=""><button class="sv-slot-remove" onclick="svRemovePhoto(\'' + info.stageId + '\',' + info.idx + ',event)">✕</button>';
                slotEl.classList.add('sv-filled');
                slotEl.onclick = null;
            }
            updatePhotoCounter(info.stageId);
        }

        this.value = '';
    });

    // ── Compass ───────────────────────────────────────────────────────────────
    function svPickDir(dir) {
        _pendingDir  = dir;
        _pendingSlot = null;
        fileInput.value = '';
        fileInput.click();
    }
    window.svPickDir = svPickDir;

    function svRemoveDir(dir, e) {
        e.stopPropagation();
        compassPhotos[dir] = null;
        var slot = document.getElementById('svDir_' + dir);
        var ARROWS = { south:'⬇️', east:'➡️', north:'⬆️', west:'⬅️' };
        var LABELS = { south:'SOUTH', east:'EAST', north:'NORTH', west:'WEST' };
        slot.innerHTML =
            '<span class="sv-dir-icon">' + ARROWS[dir] + '</span>' +
            '<span class="sv-dir-label">' + LABELS[dir] + '</span>' +
            '<div class="sv-dir-overlay" id="svDirLabel_' + dir + '" style="display:none">' + ARROWS[dir] + ' ' + LABELS[dir] + '</div>' +
            '<button type="button" class="sv-dir-remove" onclick="svRemoveDir(\'' + dir + '\',event)">✕</button>';
        slot.classList.remove('sv-filled');
        slot.onclick = function(){ svPickDir(dir); };
        updateCompassCounter();
    }
    window.svRemoveDir = svRemoveDir;

    function updateCompassCounter() {
        var count = Object.values(compassPhotos).filter(Boolean).length;
        var el = document.getElementById('svCompassCounter');
        if (el) el.textContent = count + ' / 4 photos';
        var btn = document.getElementById('svCompassContinue');
        if (btn) btn.disabled = count < 4;
    }

    function svCompassContinue() {
        // Disable button, start background upload immediately, advance UI
        var btn = document.getElementById('svCompassContinue');
        if (btn) btn.disabled = true;

        startCompassUpload(); // non-blocking background upload
        svAdvance(0);         // immediately move to next stage
    }
    window.svCompassContinue = svCompassContinue;

    function startCompassUpload() {
        var banner = document.getElementById('svBgStatus');
        if (banner) banner.classList.add('sv-visible');

        var DIRS  = ['south', 'east', 'north', 'west'];
        var CATS  = { south:'yearly_refresh_south', east:'yearly_refresh_east', north:'yearly_refresh_north', west:'yearly_refresh_west' };
        var queue = DIRS.filter(function(d){ return compassPhotos[d]; });
        var total = queue.length;
        var done  = 0;

        function uploadNext() {
            if (!queue.length) {
                if (banner) {
                    banner.textContent = '✅ Compass photos saved!';
                    setTimeout(function(){ banner.classList.remove('sv-visible'); }, 3000);
                }
                return;
            }
            var dir  = queue.shift();
            var file = compassPhotos[dir];
            compressAndUpload(file, CATS[dir], 'yearly ' + dir + ' photo', function(ok) {
                done++;
                if (banner) banner.textContent = '📡 Compass photos: ' + done + ' / ' + total + ' uploaded…';
                uploadNext();
            });
        }
        uploadNext();
    }

    // ── Photo stages ─────────────────────────────────────────────────────────
    function svPickPhoto(stageId, idx) {
        _pendingDir  = null;
        _pendingSlot = { stageId: stageId, idx: idx };
        fileInput.value = '';
        fileInput.click();
    }
    window.svPickPhoto = svPickPhoto;

    function svRemovePhoto(stageId, idx, e) {
        e.stopPropagation();
        stagePhotos[stageId][idx] = null;
        var slot = document.getElementById('svSlot_' + stageId + '_' + idx);
        if (slot) {
            slot.innerHTML = '<span class="sv-slot-plus">+</span>';
            slot.classList.remove('sv-filled');
            slot.onclick = function(){ svPickPhoto(stageId, idx); };
        }
        updatePhotoCounter(stageId);
    }
    window.svRemovePhoto = svRemovePhoto;

    function updatePhotoCounter(stageId) {
        var files = (stagePhotos[stageId] || []).filter(Boolean);
        var stage = stages.find(function(s){ return s.id === stageId; });
        var el = document.getElementById('svCounter_' + stageId);
        if (el && stage) el.textContent = files.length + ' / ' + stage.count + ' photos';
    }

    function svUploadStage(stageId, required, stageIdx, isLast, cat, actionType, actionLabel, hasScale) {
        var files    = (stagePhotos[stageId] || []).filter(Boolean);
        var notes    = (document.getElementById('svNotes_' + stageId) || {}).value || '';
        var progEl   = document.getElementById('svUploadProgress_' + stageId);
        var stageEl  = document.getElementById('svStage_' + stageId);
        var uploadBtn = stageEl ? stageEl.querySelector('.btn-primary') : null;

        if (files.length === 0 && notes.trim() === '') {
            if (!confirm('Nothing entered for this stage. Skip and continue?')) return;
            svAdvance(stageIdx);
            return;
        }

        if (progEl) progEl.style.display = 'block';
        if (uploadBtn) { uploadBtn.disabled = true; uploadBtn.textContent = 'Uploading…'; }

        var promises = files.map(function(file, i) {
            return new Promise(function(resolve) {
                compressAndUpload(file, cat, stageId + ' photo ' + (i + 1), resolve);
            });
        });

        // Log scale if present
        if (hasScale) {
            var scaleEl = document.getElementById('svScale_' + stageId);
            var scaleVal = scaleEl ? scaleEl.value : '5';
            var scaleText = 'Budding scale: ' + scaleVal + '/10 — ' + (SCALE_DESC[scaleVal] || '');
            var fullNote  = scaleText + (notes.trim() ? '\n' + notes.trim() : '');
            promises.push(postNote(actionType, actionLabel, fullNote));
        } else if (notes.trim()) {
            promises.push(postNote(actionType, actionLabel, notes.trim()));
        }

        Promise.all(promises).then(function() {
            if (progEl) progEl.style.display = 'none';
            svAdvance(stageIdx);
        }).catch(function() {
            if (progEl) progEl.style.display = 'none';
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.textContent = isLast ? '📤 Upload & Finish' : '📤 Upload & Continue';
            }
            alert('Upload error. Please try again.');
        });
    }
    window.svUploadStage = svUploadStage;

    // ── Advance stage ─────────────────────────────────────────────────────────
    function svAdvance(stageIdx) {
        // Mark done
        var dot = document.getElementById('svDot_' + stages[stageIdx].id);
        if (dot) { dot.classList.remove('sv-active'); dot.classList.add('sv-done'); }
        var cur = document.getElementById('svStage_' + stages[stageIdx].id);
        if (cur) cur.classList.remove('sv-active');

        var next = stages[stageIdx + 1];
        if (!next) {
            document.getElementById('svDone').style.display = 'block';
            return;
        }
        var nextEl = document.getElementById('svStage_' + next.id);
        if (nextEl) nextEl.classList.add('sv-active');
        var nextDot = document.getElementById('svDot_' + next.id);
        if (nextDot) { nextDot.classList.remove('sv-done'); nextDot.classList.add('sv-active'); }
    }
    window.svAdvance = svAdvance;

    // ── Upload helpers ────────────────────────────────────────────────────────
    function compressAndUpload(file, category, caption, callback) {
        compress(file, function(blob) {
            var fd = new FormData();
            fd.append('_token',   CSRF);
            fd.append('file',     blob, file.name);
            fd.append('category', category);
            fd.append('caption',  caption);
            fd.append('_ajax',    '1');

            fetch(UPLOAD_URL, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) { callback(!!d.success); })
                .catch(function()  { callback(false); });
        });
    }

    function postNote(actionType, actionLabel, text) {
        if (!text || !text.trim()) return Promise.resolve();
        var body = new URLSearchParams();
        body.set('_token',            CSRF);
        body.set('action_type',       actionType);
        body.set('custom_action_label', actionLabel);
        body.set('description',       text.trim());
        return fetch(ACTIONS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function(r){ return r.ok ? r.json() : {success:false}; })
          .catch(function(){ return {success:false}; });
    }

    function compress(file, cb) {
        var MAX = 1600, QUALITY = 0.80;
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var w = img.width, h = img.height;
                var ratio = Math.min(1, MAX / Math.max(w, h));
                var canvas = document.createElement('canvas');
                canvas.width  = Math.round(w * ratio);
                canvas.height = Math.round(h * ratio);
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function(blob) { cb(blob || file); }, 'image/jpeg', QUALITY);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
})();
</script>
