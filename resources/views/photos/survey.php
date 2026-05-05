<?php
$itemType  = $item['type'] ?? '';
$itemName  = $item['name'] ?? '';
$itemId    = (int)$item['id'];
$csrfToken = \App\Support\CSRF::getToken();

$stages = [
    [
        'id'          => 'compass',
        'label'       => 'Compass Survey',
        'emoji'       => '🧭',
        'hint'        => 'Take one photo per direction, standing by the tree and pointing the camera at it.',
        'type'        => 'compass',
        'action_type' => 'survey_compass',
    ],
    [
        'id'          => 'budding',
        'label'       => 'Budding',
        'emoji'       => '🌸',
        'hint'        => 'Take 3 photos and rate bud development.',
        'type'        => 'photo_text',
        'count'       => 3,
        'cat'         => 'budding_photo',
        'action_type' => 'survey_budding',
        'scale_label' => 'Budding Intensity',
        'placeholder' => 'Describe bud density, development stage, any concerns…',
    ],
    [
        'id'          => 'fruits',
        'label'       => 'Fruits',
        'emoji'       => '🍋',
        'hint'        => 'Take 3 photos and rate the fruit set.',
        'type'        => 'photo_text',
        'count'       => 3,
        'cat'         => 'fruits_photo',
        'action_type' => 'survey_fruits',
        'scale_label' => 'Fruit Set',
        'placeholder' => 'Note fruit size, colour, quantity, drop or damage observed…',
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
        'scale_label' => null,
        'placeholder' => 'Note pests, disease, nutrient issues, or treatments needed…',
    ],
];

// First undone stage index
$firstUndoneIdx = null;
foreach ($stages as $i => $s) {
    if (empty($doneMap[$s['id']])) { $firstUndoneIdx = $i; break; }
}
?>
<style>
/* ── Survey ──────────────────────────────────────────── */
.sv-page { max-width:520px;margin:0 auto;padding-bottom:60px; }
.sv-header { display:flex;align-items:center;gap:12px;margin-bottom:20px; }
.sv-back { color:var(--color-text-muted);text-decoration:none;display:flex;align-items:center; }
.sv-title { font-size:1.05rem;font-weight:800;flex:1; }
.sv-badge { font-size:.72rem;font-weight:700;color:var(--color-primary);background:var(--color-primary-soft);padding:2px 10px;border-radius:999px;white-space:nowrap; }

/* Progress strip */
.sv-progress { display:flex;gap:6px;margin-bottom:24px;align-items:center; }
.sv-dot { height:6px;border-radius:3px;background:var(--color-border);transition:all .25s;flex:1;min-width:0; }
.sv-dot.sv-done   { background:var(--color-primary); }
.sv-dot.sv-active { background:var(--color-accent);flex:2; }

/* Done stage card */
.sv-done-card {
    display:flex;align-items:center;gap:10px;
    padding:12px 14px;background:var(--color-surface);
    border:1.5px solid var(--color-primary);border-radius:12px;
    margin-bottom:10px;
}
.sv-done-card-icon { font-size:1.4rem;flex-shrink:0; }
.sv-done-card-label { font-size:.9rem;font-weight:700;flex:1; }
.sv-done-card-date { font-size:.78rem;color:var(--color-primary);font-weight:600; }
.sv-done-check { font-size:1.1rem;color:var(--color-primary); }

/* Stage panel */
.sv-stage { display:none; }
.sv-stage.sv-active { display:block; }
.sv-stage-head { text-align:center;margin-bottom:18px; }
.sv-emoji { font-size:2.6rem;display:block;margin-bottom:6px; }
.sv-stage-title { font-size:1.1rem;font-weight:800;margin:0 0 4px; }
.sv-hint { font-size:.83rem;color:var(--color-text-muted);margin:0; }

/* Compass grid */
.sv-compass { display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px; }
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
    font-size:.68rem;font-weight:700;text-align:center;padding:5px 4px;letter-spacing:.04em;
}
.sv-dir-icon  { font-size:1.8rem;pointer-events:none; }
.sv-dir-label { font-size:.7rem;font-weight:700;color:var(--color-text-muted);pointer-events:none; }
.sv-dir-slot.sv-filled .sv-dir-icon,
.sv-dir-slot.sv-filled .sv-dir-label { display:none; }
.sv-dir-remove {
    position:absolute;top:6px;right:6px;width:24px;height:24px;border-radius:50%;
    background:rgba(0,0,0,.55);color:#fff;border:none;cursor:pointer;font-size:.75rem;
    display:none;align-items:center;justify-content:center;z-index:2;
}
.sv-dir-slot.sv-filled .sv-dir-remove { display:flex; }

/* Photo grid */
.sv-photo-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:12px; }
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
.sv-photo-counter { font-size:.78rem;font-weight:700;color:var(--color-accent);text-align:center;margin-bottom:14px; }

/* Scale */
.sv-scale-wrap { margin-bottom:16px; }
.sv-scale-hdr { display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px; }
.sv-scale-lbl { font-size:.85rem;font-weight:700; }
.sv-scale-val { font-size:1.3rem;font-weight:900;color:var(--color-primary); }
.sv-scale-desc { font-size:.75rem;color:var(--color-text-muted);text-align:center;margin-top:4px; }
.sv-scale-slider { -webkit-appearance:none;width:100%;height:8px;border-radius:4px;background:linear-gradient(to right,#ef4444,#f59e0b,#22c55e);outline:none;cursor:pointer; }
.sv-scale-slider::-webkit-slider-thumb { -webkit-appearance:none;width:28px;height:28px;border-radius:50%;background:var(--color-primary);cursor:pointer;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.2); }

/* Notes */
.sv-notes-lbl { font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;display:block; }
.sv-notes { width:100%;border:1.5px solid var(--color-border);border-radius:10px;padding:10px 12px;font-size:.9rem;font-family:inherit;resize:vertical;min-height:80px;background:var(--color-surface);color:var(--color-text);transition:border-color .15s;margin-bottom:16px;box-sizing:border-box; }
.sv-notes:focus { outline:none;border-color:var(--color-primary); }

/* Actions */
.sv-actions { display:flex;flex-direction:column;gap:8px; }
.sv-upload-prog { display:none;text-align:center;padding:8px;font-size:.84rem;color:var(--color-text-muted); }

/* Year-complete screen */
.sv-year-done { text-align:center;padding:40px 20px; }
.sv-year-done-icon { font-size:4rem;margin-bottom:12px; }
.sv-year-done-title { font-size:1.4rem;font-weight:800;margin-bottom:8px; }
.sv-year-done-text { color:var(--color-text-muted);margin-bottom:24px;line-height:1.6; }
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

<?php if ($allDone): ?>
<!-- ── All stages done for this year ── -->
<div class="sv-year-done">
    <div class="sv-year-done-icon">🌿</div>
    <h2 class="sv-year-done-title">Survey complete for <?= $year ?></h2>
    <p class="sv-year-done-text">All four stages have been submitted for <strong><?= e($itemName) ?></strong>.<br>See you next year!</p>
    <a href="<?= url('/items/' . $itemId . '/photos') ?>" class="btn btn-primary btn-lg" style="display:block;text-align:center;margin-bottom:10px">View Photos</a>
    <a href="<?= url('/items/' . $itemId) ?>" class="btn btn-ghost btn-lg" style="display:block;text-align:center">Back to Item</a>
</div>

<?php else: ?>
<!-- ── Progress dots ── -->
<div class="sv-progress" id="svProgress">
    <?php foreach ($stages as $i => $s):
        $isDone   = !empty($doneMap[$s['id']]);
        $isActive = ($i === $firstUndoneIdx);
        $cls = $isDone ? 'sv-done' : ($isActive ? 'sv-active' : '');
    ?>
    <div class="sv-dot <?= $cls ?>" id="svDot_<?= $s['id'] ?>"></div>
    <?php endforeach; ?>
</div>

<!-- Hidden file input -->
<input type="file" id="svFileInput" accept="image/*" capture="environment" style="display:none">

<?php foreach ($stages as $si => $stage):
    $isDone   = !empty($doneMap[$stage['id']]);
    $isActive = ($si === $firstUndoneIdx);
    $isLast   = ($si === count($stages) - 1);
?>

<?php if ($isDone): ?>
<!-- Done card for <?= $stage['id'] ?> -->
<div class="sv-done-card">
    <span class="sv-done-card-icon"><?= $stage['emoji'] ?></span>
    <span class="sv-done-card-label"><?= e($stage['label']) ?></span>
    <span class="sv-done-card-date">✅ <?= e($doneMap[$stage['id']]) ?></span>
</div>

<?php elseif ($isActive): ?>
<!-- Active stage: <?= $stage['id'] ?> -->
<div class="sv-stage sv-active" id="svStage_<?= $stage['id'] ?>">
    <div class="sv-stage-head">
        <span class="sv-emoji"><?= $stage['emoji'] ?></span>
        <h2 class="sv-stage-title"><?= e($stage['label']) ?></h2>
        <p class="sv-hint"><?= e($stage['hint']) ?></p>
    </div>

    <?php if ($stage['type'] === 'compass'): ?>
    <!-- Compass grid -->
    <div class="sv-compass" id="svCompassGrid">
        <?php foreach ([['south','⬇️','SOUTH'],['east','➡️','EAST'],['north','⬆️','NORTH'],['west','⬅️','WEST']] as [$key,$arrow,$lbl]): ?>
        <div class="sv-dir-slot" id="svDir_<?= $key ?>" onclick="svPickDir('<?= $key ?>')">
            <span class="sv-dir-icon"><?= $arrow ?></span>
            <span class="sv-dir-label"><?= $lbl ?></span>
            <div class="sv-dir-overlay" id="svDirLbl_<?= $key ?>" style="display:none"><?= $arrow ?> <?= $lbl ?></div>
            <button type="button" class="sv-dir-remove" onclick="svRemoveDir('<?= $key ?>',event)">✕</button>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="sv-photo-counter" id="svCompassCounter">0 / 4 photos</div>
    <div class="sv-actions">
        <div class="sv-upload-prog" id="svProg_compass">Uploading…</div>
        <button class="btn btn-primary btn-lg" id="svCompassBtn" onclick="svSubmitCompass(<?= $si ?>)" disabled>
            📤 Upload & <?= $isLast ? 'Finish' : 'Continue' ?>
        </button>
    </div>

    <?php else: ?>
    <!-- Photo + text stage -->
    <div class="sv-photo-grid" id="svGrid_<?= $stage['id'] ?>">
        <?php for ($p = 0; $p < $stage['count']; $p++): ?>
        <div class="sv-photo-slot" id="svSlot_<?= $stage['id'] ?>_<?= $p ?>" onclick="svPickPhoto('<?= $stage['id'] ?>',<?= $p ?>)">
            <span class="sv-slot-plus">+</span>
        </div>
        <?php endfor; ?>
    </div>
    <div class="sv-photo-counter" id="svCounter_<?= $stage['id'] ?>">0 / <?= $stage['count'] ?> photos</div>

    <?php if (!empty($stage['scale_label'])): ?>
    <div class="sv-scale-wrap">
        <div class="sv-scale-hdr">
            <span class="sv-scale-lbl"><?= e($stage['scale_label']) ?></span>
            <span class="sv-scale-val" id="svScaleVal_<?= $stage['id'] ?>">5</span>
        </div>
        <input type="range" class="sv-scale-slider" id="svScale_<?= $stage['id'] ?>"
               min="1" max="10" value="5"
               oninput="svUpdateScale('<?= $stage['id'] ?>',this.value)">
        <div class="sv-scale-desc" id="svScaleDesc_<?= $stage['id'] ?>">Moderate</div>
    </div>
    <?php endif; ?>

    <label class="sv-notes-lbl" for="svNotes_<?= $stage['id'] ?>">Notes</label>
    <textarea class="sv-notes" id="svNotes_<?= $stage['id'] ?>"
              placeholder="<?= e($stage['placeholder']) ?>"></textarea>

    <div class="sv-actions">
        <div class="sv-upload-prog" id="svProg_<?= $stage['id'] ?>">Uploading…</div>
        <button class="btn btn-primary btn-lg"
                data-last="<?= $isLast ? '1' : '0' ?>"
                onclick="svSubmitStage('<?= $stage['id'] ?>',<?= $stage['count'] ?>,<?= $si ?>,'<?= $stage['cat'] ?>','<?= $stage['action_type'] ?>',<?= !empty($stage['scale_label']) ? 'true' : 'false' ?>,<?= $isLast ? 'true' : 'false' ?>)">
            📤 Upload & <?= $isLast ? 'Finish' : 'Continue' ?>
        </button>
    </div>
    <?php endif; ?>
</div><!-- /sv-stage -->

<?php else: ?>
<!-- Upcoming (not done, not active yet) — hidden -->
<div class="sv-stage" id="svStage_<?= $stage['id'] ?>">
    <div class="sv-stage-head">
        <span class="sv-emoji"><?= $stage['emoji'] ?></span>
        <h2 class="sv-stage-title"><?= e($stage['label']) ?></h2>
        <p class="sv-hint"><?= e($stage['hint']) ?></p>
    </div>

    <?php if ($stage['type'] === 'compass'): ?>
    <div class="sv-compass" id="svCompassGrid">
        <?php foreach ([['south','⬇️','SOUTH'],['east','➡️','EAST'],['north','⬆️','NORTH'],['west','⬅️','WEST']] as [$key,$arrow,$lbl]): ?>
        <div class="sv-dir-slot" id="svDir_<?= $key ?>" onclick="svPickDir('<?= $key ?>')">
            <span class="sv-dir-icon"><?= $arrow ?></span>
            <span class="sv-dir-label"><?= $lbl ?></span>
            <div class="sv-dir-overlay" id="svDirLbl_<?= $key ?>" style="display:none"><?= $arrow ?> <?= $lbl ?></div>
            <button type="button" class="sv-dir-remove" onclick="svRemoveDir('<?= $key ?>',event)">✕</button>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="sv-photo-counter" id="svCompassCounter">0 / 4 photos</div>
    <div class="sv-actions">
        <div class="sv-upload-prog" id="svProg_compass">Uploading…</div>
        <button class="btn btn-primary btn-lg" id="svCompassBtn" onclick="svSubmitCompass(<?= $si ?>)" disabled>
            📤 Upload & <?= $isLast ? 'Finish' : 'Continue' ?>
        </button>
    </div>

    <?php else: ?>
    <div class="sv-photo-grid" id="svGrid_<?= $stage['id'] ?>">
        <?php for ($p = 0; $p < $stage['count']; $p++): ?>
        <div class="sv-photo-slot" id="svSlot_<?= $stage['id'] ?>_<?= $p ?>" onclick="svPickPhoto('<?= $stage['id'] ?>',<?= $p ?>)">
            <span class="sv-slot-plus">+</span>
        </div>
        <?php endfor; ?>
    </div>
    <div class="sv-photo-counter" id="svCounter_<?= $stage['id'] ?>">0 / <?= $stage['count'] ?> photos</div>

    <?php if (!empty($stage['scale_label'])): ?>
    <div class="sv-scale-wrap">
        <div class="sv-scale-hdr">
            <span class="sv-scale-lbl"><?= e($stage['scale_label']) ?></span>
            <span class="sv-scale-val" id="svScaleVal_<?= $stage['id'] ?>">5</span>
        </div>
        <input type="range" class="sv-scale-slider" id="svScale_<?= $stage['id'] ?>"
               min="1" max="10" value="5"
               oninput="svUpdateScale('<?= $stage['id'] ?>',this.value)">
        <div class="sv-scale-desc" id="svScaleDesc_<?= $stage['id'] ?>">Moderate</div>
    </div>
    <?php endif; ?>

    <label class="sv-notes-lbl" for="svNotes_<?= $stage['id'] ?>">Notes</label>
    <textarea class="sv-notes" id="svNotes_<?= $stage['id'] ?>"
              placeholder="<?= e($stage['placeholder']) ?>"></textarea>

    <div class="sv-actions">
        <div class="sv-upload-prog" id="svProg_<?= $stage['id'] ?>">Uploading…</div>
        <button class="btn btn-primary btn-lg"
                onclick="svSubmitStage('<?= $stage['id'] ?>',<?= $stage['count'] ?>,<?= $si ?>,'<?= $stage['cat'] ?>','<?= $stage['action_type'] ?>',<?= !empty($stage['scale_label']) ? 'true' : 'false' ?>,<?= $isLast ? 'true' : 'false' ?>)">
            📤 Upload & <?= $isLast ? 'Finish' : 'Continue' ?>
        </button>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endforeach; ?>

<!-- Completion screen (shown by JS after last stage) -->
<div id="svCompletionScreen" style="display:none;text-align:center;padding:40px 20px">
    <div style="font-size:4rem;margin-bottom:12px">🌿</div>
    <h2 style="font-size:1.4rem;font-weight:800;margin-bottom:8px">Survey complete for <?= $year ?>!</h2>
    <p style="color:var(--color-text-muted);margin-bottom:24px">All stages submitted for <strong><?= e($itemName) ?></strong>. See you next year!</p>
    <a href="<?= url('/items/' . $itemId . '/photos') ?>" class="btn btn-primary btn-lg" style="display:block;text-align:center;margin-bottom:10px">View Photos</a>
    <a href="<?= url('/items/' . $itemId) ?>" class="btn btn-ghost btn-lg" style="display:block;text-align:center">Back to Item</a>
</div>

<?php endif; ?>

</div><!-- /.sv-page -->

<script>
(function() {
    var CSRF       = <?= json_encode($csrfToken) ?>;
    var ITEM_ID    = <?= $itemId ?>;
    var YEAR       = <?= $year ?>;
    var BASE       = window.APP_BASE || '';
    var TEMP_URL   = BASE + '/items/' + ITEM_ID + '/survey/temp';
    var STAGE_URL  = BASE + '/items/' + ITEM_ID + '/survey/stage';

    var STAGES     = <?= json_encode(array_values($stages)) ?>;

    var SCALE_DESC = {
        1:'Almost no buds / fruit',2:'Very sparse',3:'Low density',4:'Below average',
        5:'Moderate',6:'Above average',7:'Good density',8:'Very good',
        9:'Excellent',10:'Exceptional!'
    };

    // State: compassPhotos for preview, compassTempPromises for upload tracking
    var compassPhotos       = { south:null, east:null, north:null, west:null };
    var compassTempPromises = { south:null, east:null, north:null, west:null };
    var stagePhotos         = {}; // for preview slot tracking
    var stageTempPromises   = {}; // stage id → array of Promise<tempId>
    STAGES.forEach(function(s) {
        if (s.type === 'photo_text') {
            stagePhotos[s.id]       = [];
            stageTempPromises[s.id] = [];
        }
    });

    var _pendingDir  = null;
    var _pendingSlot = null;
    var fileInput    = document.getElementById('svFileInput');

    // ── Scale ────────────────────────────────────────────────────────────────
    function svUpdateScale(id, v) {
        var val  = document.getElementById('svScaleVal_'  + id);
        var desc = document.getElementById('svScaleDesc_' + id);
        if (val)  val.textContent  = v;
        if (desc) desc.textContent = SCALE_DESC[v] || '';
    }
    window.svUpdateScale = svUpdateScale;

    // ── Temp upload ───────────────────────────────────────────────────────────
    function uploadTemp(file) {
        return new Promise(function(resolve, reject) {
            compress(file, function(blob) {
                var fd = new FormData();
                fd.append('_token', CSRF);
                fd.append('file',   blob, file.name);
                fetch(TEMP_URL, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(d) { d.ok ? resolve(d.temp_id) : reject(new Error(d.error || 'Temp upload failed')); })
                    .catch(reject);
            });
        });
    }

    // ── File input routing ────────────────────────────────────────────────────
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            var file = this.files[0];
            if (_pendingDir !== null) {
                var dir = _pendingDir; _pendingDir = null;
                compassPhotos[dir] = file;
                renderDirSlot(dir, file);
                updateCompassCounter();
                compassTempPromises[dir] = uploadTemp(file);
            } else if (_pendingSlot !== null) {
                var info = _pendingSlot; _pendingSlot = null;
                stagePhotos[info.id][info.idx] = file;
                var slot = document.getElementById('svSlot_' + info.id + '_' + info.idx);
                if (slot) {
                    var objUrl = URL.createObjectURL(file);
                    slot.innerHTML = '<img src="' + objUrl + '" alt=""><button class="sv-slot-remove" onclick="svRemovePhoto(\'' + info.id + '\',' + info.idx + ',event)">✕</button>';
                    slot.classList.add('sv-filled');
                    slot.onclick = null;
                }
                stageTempPromises[info.id][info.idx] = uploadTemp(file);
                updatePhotoCounter(info.id);
            }
            this.value = '';
        });
    }

    // ── Compass ───────────────────────────────────────────────────────────────
    var DIR_META = {
        south:{arrow:'⬇️',label:'SOUTH'}, east:{arrow:'➡️',label:'EAST'},
        north:{arrow:'⬆️',label:'NORTH'}, west:{arrow:'⬅️',label:'WEST'}
    };

    function renderDirSlot(dir, file) {
        var slot = document.getElementById('svDir_' + dir);
        if (!slot) return;
        var m   = DIR_META[dir];
        var url = URL.createObjectURL(file);
        slot.innerHTML =
            '<img src="' + url + '" alt="">' +
            '<div class="sv-dir-overlay" id="svDirLbl_' + dir + '">' + m.arrow + ' ' + m.label + '</div>' +
            '<button type="button" class="sv-dir-remove" onclick="svRemoveDir(\'' + dir + '\',event)">✕</button>';
        slot.classList.add('sv-filled');
        slot.onclick = null;
    }

    function svPickDir(dir) {
        _pendingDir = dir; _pendingSlot = null;
        if (fileInput) { fileInput.value = ''; fileInput.click(); }
    }
    window.svPickDir = svPickDir;

    function svRemoveDir(dir, e) {
        e.stopPropagation();
        compassPhotos[dir]       = null;
        compassTempPromises[dir] = null;
        var m    = DIR_META[dir];
        var slot = document.getElementById('svDir_' + dir);
        if (!slot) return;
        slot.innerHTML =
            '<span class="sv-dir-icon">' + m.arrow + '</span>' +
            '<span class="sv-dir-label">' + m.label + '</span>' +
            '<div class="sv-dir-overlay" id="svDirLbl_' + dir + '" style="display:none">' + m.arrow + ' ' + m.label + '</div>' +
            '<button type="button" class="sv-dir-remove" onclick="svRemoveDir(\'' + dir + '\',event)">✕</button>';
        slot.classList.remove('sv-filled');
        slot.onclick = function(){ svPickDir(dir); };
        updateCompassCounter();
    }
    window.svRemoveDir = svRemoveDir;

    function updateCompassCounter() {
        var count = Object.values(compassPhotos).filter(Boolean).length;
        var el  = document.getElementById('svCompassCounter');
        var btn = document.getElementById('svCompassBtn');
        if (el)  el.textContent = count + ' / 4 photos';
        if (btn) btn.disabled   = (count < 4);
    }

    function svSubmitCompass(stageIdx) {
        var btn  = document.getElementById('svCompassBtn');
        var prog = document.getElementById('svProg_compass');
        if (btn)  btn.disabled = true;
        if (prog) prog.style.display = 'block';

        var DIRS = ['south','east','north','west'];
        var promisePairs = DIRS
            .filter(function(d) { return compassTempPromises[d]; })
            .map(function(d) {
                return compassTempPromises[d].then(function(tempId) {
                    return tempId ? { temp_id: tempId, direction: d } : null;
                }).catch(function() { return null; });
            });

        Promise.all(promisePairs)
            .then(function(pairs) {
                var photos = pairs.filter(Boolean);
                return fetch(STAGE_URL, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                    body:    JSON.stringify({ stage: 'compass', year: YEAR, photos: photos, notes: '', scale_value: null })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.ok) svAdvance(stageIdx);
                else throw new Error(d.error || 'Save failed');
            })
            .catch(function(err) {
                if (prog) prog.style.display = 'none';
                if (btn)  btn.disabled = false;
                alert('Upload error: ' + (err.message || err));
            });
    }
    window.svSubmitCompass = svSubmitCompass;

    // ── Photo stages ──────────────────────────────────────────────────────────
    function svPickPhoto(id, idx) {
        _pendingDir = null; _pendingSlot = { id:id, idx:idx };
        if (fileInput) { fileInput.value = ''; fileInput.click(); }
    }
    window.svPickPhoto = svPickPhoto;

    function svRemovePhoto(id, idx, e) {
        e.stopPropagation();
        stagePhotos[id][idx]       = null;
        stageTempPromises[id][idx] = null;
        var slot = document.getElementById('svSlot_' + id + '_' + idx);
        if (slot) {
            slot.innerHTML = '<span class="sv-slot-plus">+</span>';
            slot.classList.remove('sv-filled');
            slot.onclick = function(){ svPickPhoto(id, idx); };
        }
        updatePhotoCounter(id);
    }
    window.svRemovePhoto = svRemovePhoto;

    function updatePhotoCounter(id) {
        var stage = STAGES.find(function(s){ return s.id === id; });
        var files = (stagePhotos[id] || []).filter(Boolean);
        var el    = document.getElementById('svCounter_' + id);
        if (el && stage) el.textContent = files.length + ' / ' + stage.count + ' photos';
    }

    function svSubmitStage(id, required, stageIdx, cat, actionType, hasScale, isLast) {
        var tempPromises = (stageTempPromises[id] || []).filter(Boolean);
        var notes   = (document.getElementById('svNotes_' + id) || {}).value || '';
        var stageEl = document.getElementById('svStage_' + id);
        var prog    = document.getElementById('svProg_' + id);
        var btn     = stageEl ? stageEl.querySelector('.btn-primary') : null;

        if (tempPromises.length === 0 && !notes.trim()) {
            if (!confirm('No photos or notes for this stage. Submit it as completed anyway?')) return;
        }

        if (prog) prog.style.display = 'block';
        if (btn)  btn.disabled = true;

        var scaleValue = null;
        if (hasScale) {
            var scaleEl = document.getElementById('svScale_' + id);
            scaleValue  = scaleEl ? parseInt(scaleEl.value, 10) : 5;
        }

        Promise.all(tempPromises.map(function(p) { return p.catch(function(){ return null; }); }))
            .then(function(tempIds) {
                var photos = tempIds.filter(Boolean).map(function(tid) { return { temp_id: tid }; });
                return fetch(STAGE_URL, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                    body:    JSON.stringify({
                        stage:       actionType.replace('survey_', ''),
                        year:        YEAR,
                        photos:      photos,
                        scale_value: scaleValue,
                        notes:       notes.trim() || ''
                    })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.ok) svAdvance(stageIdx);
                else throw new Error(d.error || 'Save failed');
            })
            .catch(function(err) {
                if (prog) prog.style.display = 'none';
                if (btn)  btn.disabled = false;
                alert('Error: ' + (err.message || err));
            });
    }
    window.svSubmitStage = svSubmitStage;

    // ── Advance ───────────────────────────────────────────────────────────────
    function svAdvance(stageIdx) {
        var dot = document.getElementById('svDot_' + STAGES[stageIdx].id);
        if (dot) { dot.classList.remove('sv-active'); dot.classList.add('sv-done'); }
        var cur = document.getElementById('svStage_' + STAGES[stageIdx].id);
        if (cur) cur.classList.remove('sv-active');

        var next = null;
        for (var i = stageIdx + 1; i < STAGES.length; i++) {
            var nextEl = document.getElementById('svStage_' + STAGES[i].id);
            if (nextEl) { next = { idx: i, el: nextEl, stage: STAGES[i] }; break; }
        }

        if (!next) {
            var compl = document.getElementById('svCompletionScreen');
            if (compl) compl.style.display = 'block';
            return;
        }

        next.el.classList.add('sv-active');
        var nextDot = document.getElementById('svDot_' + next.stage.id);
        if (nextDot) { nextDot.classList.remove('sv-done'); nextDot.classList.add('sv-active'); }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── Compress ──────────────────────────────────────────────────────────────
    function compress(file, cb) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var MAX = 1600, Q = 0.80;
                var ratio  = Math.min(1, MAX / Math.max(img.width, img.height));
                var canvas = document.createElement('canvas');
                canvas.width  = Math.round(img.width  * ratio);
                canvas.height = Math.round(img.height * ratio);
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function(blob){ cb(blob || file); }, 'image/jpeg', Q);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
})();
</script>
