<?php
$itemType  = $item['type'] ?? '';
$itemName  = $item['name'] ?? '';
$itemId    = (int)$item['id'];
$csrfToken = \App\Support\CSRF::getToken();

$stages = [];
$stages[] = ['id'=>'general',  'label'=>'General Overview', 'emoji'=>'📷', 'count'=>4, 'hint'=>'Take 4 photos for a general overview of the item'];
if ($hasBudding) {
    $stages[] = ['id'=>'budding',  'label'=>'Budding',       'emoji'=>'🌸', 'count'=>3, 'hint'=>'Take 3 photos around the tree showing bud development'];
}
$stages[] = ['id'=>'health',   'label'=>'Health Check',     'emoji'=>'🏥', 'count'=>2, 'hint'=>'Take 2 photos of the main health observations'];
?>
<style>
.survey-page { max-width: 560px; margin: 0 auto; }
.survey-header { display:flex;align-items:center;gap:12px;margin-bottom:var(--spacing-4); }
.survey-back { color:var(--color-text-muted);text-decoration:none;display:flex;align-items:center; }
.survey-title { font-size:1.1rem;font-weight:800;flex:1; }
.survey-item-badge { font-size:.75rem;font-weight:700;color:var(--color-primary);background:var(--color-primary-soft);padding:2px 10px;border-radius:999px; }

/* Progress bar */
.survey-progress { display:flex;gap:4px;margin-bottom:var(--spacing-5); }
.survey-stage-dot { flex:1;height:4px;border-radius:2px;background:var(--color-border);transition:background .2s; }
.survey-stage-dot.done { background:var(--color-primary); }
.survey-stage-dot.active { background:var(--color-accent); }

/* Stage card */
.survey-stage { display:none; }
.survey-stage.active { display:block; }
.survey-stage-header { text-align:center;margin-bottom:var(--spacing-4); }
.survey-stage-emoji { font-size:2.5rem;display:block;margin-bottom:6px; }
.survey-stage-title { font-size:1.2rem;font-weight:800;margin:0 0 4px; }
.survey-stage-hint  { font-size:.85rem;color:var(--color-text-muted); }

/* Photo grid */
.survey-photo-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:var(--spacing-4); }
.survey-photo-slot { aspect-ratio:1;border-radius:var(--radius-lg);border:2px dashed var(--color-border);background:var(--color-surface);display:flex;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;position:relative;transition:border-color .15s; }
.survey-photo-slot:hover { border-color:var(--color-primary); }
.survey-photo-slot.filled { border:2px solid var(--color-primary); }
.survey-photo-slot img { width:100%;height:100%;object-fit:cover; }
.survey-photo-slot .slot-plus { font-size:2rem;color:var(--color-border);pointer-events:none; }
.survey-photo-slot .slot-remove { position:absolute;top:4px;right:4px;width:22px;height:22px;border-radius:50%;background:rgba(0,0,0,.5);color:#fff;border:none;cursor:pointer;font-size:.8rem;display:flex;align-items:center;justify-content:center; }

/* Budding scale */
.survey-budding-scale { margin-bottom:var(--spacing-4); }
.survey-scale-label { font-size:.85rem;font-weight:700;margin-bottom:8px;text-align:center; }
.survey-scale-slider { -webkit-appearance:none;width:100%;height:8px;border-radius:4px;background:linear-gradient(to right,#ef4444,#f59e0b,#22c55e);outline:none;cursor:pointer; }
.survey-scale-slider::-webkit-slider-thumb { -webkit-appearance:none;width:28px;height:28px;border-radius:50%;background:var(--color-primary);cursor:pointer;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.2); }
.survey-scale-value { text-align:center;font-size:1.6rem;font-weight:900;color:var(--color-primary);margin-top:4px; }
.survey-scale-desc  { text-align:center;font-size:.78rem;color:var(--color-text-muted); }

/* Actions */
.survey-actions { display:flex;gap:8px;flex-direction:column; }
.survey-upload-btn { width:100%; }
.survey-skip-btn   { background:none;border:1.5px solid var(--color-border);color:var(--color-text-muted);border-radius:var(--radius-pill);padding:10px;font-size:.85rem;font-weight:600;cursor:pointer;transition:background .12s; }
.survey-skip-btn:hover { background:var(--color-surface); }
.survey-upload-progress { display:none;text-align:center;padding:8px;font-size:.85rem;color:var(--color-text-muted); }

/* Done screen */
.survey-done { text-align:center;padding:var(--spacing-8) var(--spacing-4); }
.survey-done-icon { font-size:4rem;margin-bottom:var(--spacing-3); }
.survey-done-title { font-size:1.4rem;font-weight:800;margin-bottom:var(--spacing-2); }
.survey-done-text  { color:var(--color-text-muted);margin-bottom:var(--spacing-5); }
</style>

<div class="survey-page">

<div class="survey-header">
    <a href="<?= url('/items/' . $itemId) ?>" class="survey-back">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <span class="survey-title"><?= e($itemName) ?></span>
    <span class="survey-item-badge"><?= ucwords(str_replace('_',' ',$itemType)) ?></span>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Progress dots -->
<div class="survey-progress" id="surveyProgress">
    <?php foreach ($stages as $i => $s): ?>
    <div class="survey-stage-dot <?= $i === 0 ? 'active' : '' ?>" id="dot_<?= $s['id'] ?>"></div>
    <?php endforeach; ?>
</div>

<!-- Hidden file input -->
<input type="file" id="surveyFileInput" accept="image/*" capture="environment" style="display:none">

<!-- Stages -->
<?php foreach ($stages as $si => $stage): ?>
<div class="survey-stage <?= $si === 0 ? 'active' : '' ?>" id="stage_<?= $stage['id'] ?>">
    <div class="survey-stage-header">
        <span class="survey-stage-emoji"><?= $stage['emoji'] ?></span>
        <h2 class="survey-stage-title"><?= e($stage['label']) ?></h2>
        <p class="survey-stage-hint"><?= e($stage['hint']) ?></p>
        <div id="stageCounter_<?= $stage['id'] ?>" style="font-size:.78rem;font-weight:700;color:var(--color-accent);margin-top:4px">
            0 / <?= $stage['count'] ?> photos
        </div>
    </div>

    <!-- Photo grid -->
    <div class="survey-photo-grid" id="grid_<?= $stage['id'] ?>">
        <?php for ($p = 0; $p < $stage['count']; $p++): ?>
        <div class="survey-photo-slot" id="slot_<?= $stage['id'] ?>_<?= $p ?>" onclick="pickPhoto('<?= $stage['id'] ?>', <?= $p ?>)">
            <span class="slot-plus">+</span>
        </div>
        <?php endfor; ?>
    </div>

    <?php if ($stage['id'] === 'budding'): ?>
    <!-- Budding scale 1-10 -->
    <div class="survey-budding-scale">
        <div class="survey-scale-label">Budding Scale</div>
        <input type="range" class="survey-scale-slider" id="buddingScale" min="1" max="10" value="5"
               oninput="updateScale(this.value)">
        <div class="survey-scale-value" id="scaleValue">5</div>
        <div class="survey-scale-desc" id="scaleDesc">Moderate bud development</div>
    </div>
    <?php endif; ?>

    <div class="survey-actions">
        <div class="survey-upload-progress" id="uploadProgress_<?= $stage['id'] ?>">Uploading…</div>
        <button class="btn btn-primary btn-lg survey-upload-btn" id="uploadBtn_<?= $stage['id'] ?>"
                onclick="uploadStage('<?= $stage['id'] ?>', <?= $stage['count'] ?>, <?= $si ?>, <?= $si === count($stages)-1 ? 'true' : 'false' ?>)">
            <?= $si < count($stages)-1 ? '📤 Upload & Continue' : '📤 Upload & Finish' ?>
        </button>
        <?php if ($si < count($stages)-1): ?>
        <button class="survey-skip-btn" onclick="skipStage('<?= $stage['id'] ?>', <?= $si ?>)">Skip this stage</button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Done screen -->
<div class="survey-done" id="surveyDone" style="display:none">
    <div class="survey-done-icon">✅</div>
    <h2 class="survey-done-title">Survey Complete!</h2>
    <p class="survey-done-text">All photos and observations have been saved for <strong><?= e($itemName) ?></strong>.</p>
    <a href="<?= url('/items/' . $itemId . '/photos') ?>" class="btn btn-primary btn-lg" style="display:block;text-align:center;margin-bottom:10px">View Photos</a>
    <a href="<?= url('/items/' . $itemId) ?>" class="btn btn-ghost btn-lg" style="display:block;text-align:center">Back to Item</a>
</div>

</div>

<script>
var CSRF      = '<?= e($csrfToken) ?>';
var ITEM_ID   = <?= $itemId ?>;
var BASE      = '<?= url('/') ?>';
var UPLOAD_URL= BASE + 'items/' + ITEM_ID + '/attachments';

var stages = <?= json_encode(array_values($stages)) ?>;
var photos  = {}; // { stageId: [File, File, ...] }
stages.forEach(function(s){ photos[s.id] = []; });

var activeStage = stages[0].id;
var _pendingSlot = null;

// ── File picker ──────────────────────────────────────────────────────────────
var fileInput = document.getElementById('surveyFileInput');
fileInput.addEventListener('change', function() {
    if (!this.files || !this.files[0] || _pendingSlot === null) return;
    var stageId = _pendingSlot.stage, slotIdx = _pendingSlot.idx;
    var file = this.files[0];
    photos[stageId][slotIdx] = file;

    var slot = document.getElementById('slot_' + stageId + '_' + slotIdx);
    var url  = URL.createObjectURL(file);
    slot.innerHTML = '<img src="'+url+'" alt=""><button class="slot-remove" onclick="removePhoto(\''+stageId+'\','+slotIdx+',event)">✕</button>';
    slot.classList.add('filled');
    updateCounter(stageId);
    _pendingSlot = null;
    this.value = '';
});

function pickPhoto(stageId, idx) {
    _pendingSlot = { stage: stageId, idx: idx };
    fileInput.click();
}
function removePhoto(stageId, idx, e) {
    e.stopPropagation();
    photos[stageId][idx] = null;
    var slot = document.getElementById('slot_' + stageId + '_' + idx);
    slot.innerHTML = '<span class="slot-plus">+</span>';
    slot.classList.remove('filled');
    slot.onclick = function(){ pickPhoto(stageId, idx); };
    updateCounter(stageId);
}
function updateCounter(stageId) {
    var count = photos[stageId].filter(Boolean).length;
    var s = stages.find(function(s){ return s.id === stageId; });
    var el = document.getElementById('stageCounter_' + stageId);
    if (el) el.textContent = count + ' / ' + s.count + ' photos';
}

// ── Budding scale ─────────────────────────────────────────────────────────────
var scaleDescs = {1:'Almost no buds',2:'Very sparse budding',3:'Low bud density',4:'Below average budding',5:'Moderate bud development',6:'Above average budding',7:'Good bud density',8:'Very good budding',9:'Excellent bud development',10:'Exceptional — super well loaded!'};
function updateScale(v) {
    document.getElementById('scaleValue').textContent = v;
    document.getElementById('scaleDesc').textContent  = scaleDescs[v] || '';
}

// ── Upload stage ──────────────────────────────────────────────────────────────
function uploadStage(stageId, required, stageIdx, isLast) {
    var files = photos[stageId].filter(Boolean);
    var progressEl = document.getElementById('uploadProgress_' + stageId);
    var uploadBtn  = document.getElementById('uploadBtn_' + stageId);

    if (files.length === 0) {
        if (!confirm('No photos taken for this stage. Skip and continue?')) return;
        advanceStage(stageIdx, isLast);
        return;
    }

    progressEl.style.display = 'block';
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading…';

    var promises = files.map(function(file, i) {
        var fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('attachment', file, file.name);
        fd.append('category', stageId + '_photo');
        fd.append('caption', stageId + ' photo ' + (i+1));
        return fetch(UPLOAD_URL, { method:'POST', body: fd })
            .then(function(r){ return r.json(); });
    });

    // If budding stage, also log the scale
    if (stageId === 'budding') {
        var scale = document.getElementById('buddingScale');
        if (scale) {
            var fd2 = new FormData();
            fd2.append('_token', CSRF);
            // Save budding scale as activity log note
            promises.push(
                fetch(BASE + 'items/' + ITEM_ID + '/activity', {
                    method: 'POST',
                    body: (function(){
                        var p = new URLSearchParams();
                        p.set('_token', CSRF);
                        p.set('action_type', 'survey_budding');
                        p.set('description', 'Budding scale: ' + scale.value + '/10 — ' + scaleDescs[scale.value]);
                        return p.toString();
                    })()
                }).then(function(r){ return r.ok ? r.json() : {success:false}; }).catch(function(){ return {success:false}; })
            );
        }
    }

    Promise.all(promises).then(function() {
        progressEl.style.display = 'none';
        advanceStage(stageIdx, isLast);
    }).catch(function(err) {
        progressEl.style.display = 'none';
        uploadBtn.disabled = false;
        uploadBtn.textContent = isLast ? '📤 Upload & Finish' : '📤 Upload & Continue';
        alert('Upload error. Please try again.');
    });
}

function skipStage(stageId, stageIdx) {
    advanceStage(stageIdx, stageIdx === stages.length - 1);
}

function advanceStage(stageIdx, isLast) {
    // Mark current dot done
    var dot = document.getElementById('dot_' + stages[stageIdx].id);
    if (dot) { dot.classList.remove('active'); dot.classList.add('done'); }
    // Hide current stage
    var curEl = document.getElementById('stage_' + stages[stageIdx].id);
    if (curEl) curEl.classList.remove('active');

    if (isLast) {
        document.getElementById('surveyDone').style.display = 'block';
        return;
    }
    // Show next
    var nextStage = stages[stageIdx + 1];
    var nextEl = document.getElementById('stage_' + nextStage.id);
    if (nextEl) nextEl.classList.add('active');
    var nextDot = document.getElementById('dot_' + nextStage.id);
    if (nextDot) nextDot.classList.add('active');
    activeStage = nextStage.id;
}
</script>
