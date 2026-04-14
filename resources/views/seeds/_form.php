<?php
$monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$plantMonths = !empty($seed['planting_months']) ? json_decode($seed['planting_months'], true) : [];
$harvMonths  = !empty($seed['harvest_months'])  ? json_decode($seed['harvest_months'], true)  : [];
$companions  = !empty($seed['companions'])  ? implode(', ', json_decode($seed['companions'],  true)) : '';
$antagonists = !empty($seed['antagonists']) ? implode(', ', json_decode($seed['antagonists'], true)) : '';
?>

<!-- ── AI Photo Identify ─────────────────────────────────────────────────── -->
<div id="aiIdentifyPanel" style="margin-bottom:var(--spacing-4);padding:var(--spacing-3) var(--spacing-4);background:rgba(45,90,39,.06);border:1px solid rgba(45,90,39,.2);border-radius:var(--radius);display:flex;flex-direction:column;gap:var(--spacing-2)">
    <div style="display:flex;align-items:center;gap:var(--spacing-2);flex-wrap:wrap">
        <span style="font-weight:600;font-size:0.9rem">🤖 Identify from Photo</span>
        <span class="text-muted text-sm">Take or choose a photo of your seeds / packet — AI will pre-fill the form.</span>
    </div>

    <!-- Hidden file input: accept=image/* lets mobile show camera+gallery picker -->
    <input type="file" id="aiPhotoInput" accept="image/*" capture="environment" style="display:none">

    <div style="display:flex;align-items:center;gap:var(--spacing-2);flex-wrap:wrap">
        <button type="button" id="aiPhotoBtn" class="btn btn-secondary"
                style="display:flex;align-items:center;gap:6px">
            <span style="font-size:1.1rem">📷</span> Take / Choose Photo
        </button>
        <span id="aiStatus" style="font-size:0.85rem;color:var(--color-text-muted)"></span>
    </div>

    <!-- Preview + progress -->
    <div id="aiPreviewWrap" style="display:none;align-items:center;gap:var(--spacing-3)">
        <img id="aiPreviewImg" style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid var(--color-border)" src="" alt="Preview">
        <div id="aiProgressBar" style="display:none;flex:1;height:6px;background:var(--color-bg);border-radius:3px;overflow:hidden">
            <div style="width:0%;height:100%;background:var(--color-primary);border-radius:3px;transition:width .3s" id="aiProgressFill"></div>
        </div>
    </div>
</div>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Identity</legend>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Name <span class="required">*</span></label>
            <input type="text" name="name" id="fName" class="form-input" required value="<?= e($seed['name'] ?? '') ?>" placeholder="e.g. Tomato">
        </div>
        <div class="form-group">
            <label class="form-label">Variety</label>
            <input type="text" name="variety" id="fVariety" class="form-input" value="<?= e($seed['variety'] ?? '') ?>" placeholder="e.g. Roma">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Botanical Family</label>
            <input type="text" name="botanical_family" id="fBotFamily" class="form-input" value="<?= e($seed['botanical_family'] ?? '') ?>" placeholder="e.g. Solanaceae">
        </div>
        <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" id="fType" class="form-input">
                <?php foreach (['vegetable'=>'🥦 Vegetable','herb'=>'🌿 Herb','fruit'=>'🍓 Fruit','flower'=>'🌸 Flower','other'=>'🌾 Other'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($seed['type'] ?? 'vegetable') === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</fieldset>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Growing Info</legend>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Sowing Type</label>
            <select name="sowing_type" id="fSowingType" class="form-input">
                <option value="direct"  <?= ($seed['sowing_type'] ?? '') === 'direct'  ? 'selected' : '' ?>>Direct sow</option>
                <option value="nursery" <?= ($seed['sowing_type'] ?? '') === 'nursery' ? 'selected' : '' ?>>Nursery / transplant</option>
                <option value="both"    <?= ($seed['sowing_type'] ?? '') === 'both'    ? 'selected' : '' ?>>Both</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Sun Exposure</label>
            <input type="text" name="sun_exposure" id="fSunExposure" class="form-input" value="<?= e($seed['sun_exposure'] ?? '') ?>" placeholder="e.g. Full sun">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Days to Germinate</label>
            <input type="number" name="days_to_germinate" id="fDaysGerm" class="form-input" min="1" max="365" value="<?= e($seed['days_to_germinate'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Days to Maturity</label>
            <input type="number" name="days_to_maturity" id="fDaysMat" class="form-input" min="1" max="730" value="<?= e($seed['days_to_maturity'] ?? '') ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Spacing (cm)</label>
            <input type="number" name="spacing_cm" id="fSpacing" class="form-input" min="1" value="<?= e($seed['spacing_cm'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Row Spacing (cm)</label>
            <input type="number" name="row_spacing_cm" id="fRowSpacing" class="form-input" min="1" value="<?= e($seed['row_spacing_cm'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Sowing Depth (mm)</label>
            <input type="number" name="sowing_depth_mm" id="fSowDepth" class="form-input" min="0" value="<?= e($seed['sowing_depth_mm'] ?? '') ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Soil Notes</label>
        <input type="text" name="soil_notes" id="fSoilNotes" class="form-input" value="<?= e($seed['soil_notes'] ?? '') ?>" placeholder="e.g. Well-drained, slightly acidic">
    </div>
    <div class="form-group">
        <label class="form-label">
            <input type="checkbox" name="frost_hardy" id="fFrostHardy" value="1" <?= !empty($seed['frost_hardy']) ? 'checked' : '' ?>>
            Frost Hardy
        </label>
    </div>
</fieldset>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Planting Calendar</legend>
    <div class="form-group">
        <label class="form-label">Sowing / Planting Months</label>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px" id="plantMonthsWrap">
            <?php foreach ($monthNames as $i => $mn): $m = $i + 1; ?>
            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;padding:4px 8px;border:1px solid var(--color-border);border-radius:6px;font-size:0.85rem;<?= in_array($m, $plantMonths) ? 'background:var(--color-primary);color:#fff;border-color:var(--color-primary)' : 'background:var(--color-bg)' ?>">
                <input type="checkbox" name="planting_months[]" value="<?= $m ?>" <?= in_array($m, $plantMonths) ? 'checked' : '' ?> style="display:none" onchange="this.parentElement.style.background=this.checked?'var(--color-primary)':'var(--color-bg)';this.parentElement.style.color=this.checked?'#fff':'';this.parentElement.style.borderColor=this.checked?'var(--color-primary)':'var(--color-border)'">
                <?= substr($mn, 0, 3) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="form-group" style="margin-top:var(--spacing-2)">
        <label class="form-label">Harvest Months</label>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px">
            <?php foreach ($monthNames as $i => $mn): $m = $i + 1; ?>
            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;padding:4px 8px;border:1px solid var(--color-border);border-radius:6px;font-size:0.85rem;<?= in_array($m, $harvMonths) ? 'background:#b45309;color:#fff;border-color:#b45309' : 'background:var(--color-bg)' ?>">
                <input type="checkbox" name="harvest_months[]" value="<?= $m ?>" <?= in_array($m, $harvMonths) ? 'checked' : '' ?> style="display:none" onchange="this.parentElement.style.background=this.checked?'#b45309':'var(--color-bg)';this.parentElement.style.color=this.checked?'#fff':'';this.parentElement.style.borderColor=this.checked?'#b45309':'var(--color-border)'">
                <?= substr($mn, 0, 3) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
</fieldset>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Companions & Antagonists</legend>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Good Companions</label>
            <input type="text" name="companions" id="fCompanions" class="form-input" value="<?= e($companions) ?>" placeholder="Basil, Carrot, Marigold (comma-separated)">
        </div>
        <div class="form-group">
            <label class="form-label">Antagonists</label>
            <input type="text" name="antagonists" id="fAntagonists" class="form-input" value="<?= e($antagonists) ?>" placeholder="Fennel, Brassicas (comma-separated)">
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Yield per Plant (kg)</label>
        <input type="number" step="0.01" name="yield_per_plant_kg" id="fYield" class="form-input" value="<?= e($seed['yield_per_plant_kg'] ?? '') ?>" placeholder="e.g. 2.5">
    </div>
</fieldset>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Stock</legend>
    <div class="form-group">
        <label class="form-label">
            <input type="checkbox" name="stock_enabled" value="1" <?= !empty($seed['stock_enabled']) ? 'checked' : '' ?>>
            Track stock for this seed
        </label>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Current Quantity</label>
            <input type="number" step="0.001" name="stock_qty" class="form-input" min="0" value="<?= e($seed['stock_qty'] ?? '0') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Unit</label>
            <select name="stock_unit" class="form-input">
                <option value="seeds"   <?= ($seed['stock_unit'] ?? '') === 'seeds'   ? 'selected' : '' ?>>Seeds (count)</option>
                <option value="grams"   <?= ($seed['stock_unit'] ?? '') === 'grams'   ? 'selected' : '' ?>>Grams</option>
                <option value="packets" <?= ($seed['stock_unit'] ?? '') === 'packets' ? 'selected' : '' ?>>Packets</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Low-Stock Alert Below</label>
            <input type="number" step="0.001" name="stock_low_threshold" class="form-input" min="0" value="<?= e($seed['stock_low_threshold'] ?? '') ?>" placeholder="e.g. 10">
        </div>
    </div>
</fieldset>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Notes</legend>
    <div class="form-group">
        <textarea name="notes" id="fNotes" class="form-input" rows="4" placeholder="Extra growing tips, provenance, supplier…"><?= e($seed['notes'] ?? '') ?></textarea>
    </div>
</fieldset>

<!-- ── AI identify script ────────────────────────────────────────────────── -->
<script>
(function () {
    var btn       = document.getElementById('aiPhotoBtn');
    var fileInput = document.getElementById('aiPhotoInput');
    var status    = document.getElementById('aiStatus');
    var preview   = document.getElementById('aiPreviewWrap');
    var previewImg= document.getElementById('aiPreviewImg');
    var progBar   = document.getElementById('aiProgressBar');
    var progFill  = document.getElementById('aiProgressFill');

    if (!btn || !fileInput) return;

    btn.addEventListener('click', function () { fileInput.click(); });

    fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) return;

        // Show preview
        var objUrl = URL.createObjectURL(file);
        previewImg.src = objUrl;
        preview.style.display = 'flex';
        progBar.style.display  = 'block';
        progFill.style.width   = '15%';
        status.textContent     = 'Reading image…';
        btn.disabled = true;

        var reader = new FileReader();
        reader.onload = function (e) {
            var dataUrl   = e.target.result;                       // data:image/jpeg;base64,....
            var mimeMatch = dataUrl.match(/^data:([^;]+);base64,/);
            var mime      = mimeMatch ? mimeMatch[1] : 'image/jpeg';
            var b64       = dataUrl.split(',')[1];

            progFill.style.width = '35%';
            status.textContent   = 'Sending to AI… (this may take 10–30 s)';

            var body = new URLSearchParams();
            body.append('_token', '<?= e(\App\Support\CSRF::getToken()) ?>');
            body.append('image_data', b64);
            body.append('image_mime', mime);

            fetch('<?= url('/api/ai/identify-seed') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            })
            .then(function (res) {
                progFill.style.width = '80%';
                return res.json();
            })
            .then(function (data) {
                progFill.style.width = '100%';
                btn.disabled = false;

                if (!data.ok) {
                    status.textContent = '❌ ' + (data.error || 'AI error');
                    status.style.color = '#dc3545';
                    progBar.style.display = 'none';
                    return;
                }

                fillForm(data.fields);
                status.textContent = '✅ Form pre-filled from photo — review and adjust as needed.';
                status.style.color = 'var(--color-primary)';
                progBar.style.display = 'none';
            })
            .catch(function (err) {
                btn.disabled = false;
                progBar.style.display = 'none';
                status.textContent = '❌ Network error: ' + err.message;
                status.style.color = '#dc3545';
            });
        };
        reader.readAsDataURL(file);
    });

    function setVal(id, val) {
        var el = document.getElementById(id);
        if (!el || val === null || val === undefined) return;
        if (el.tagName === 'SELECT') {
            for (var i = 0; i < el.options.length; i++) {
                if (el.options[i].value === String(val)) { el.selectedIndex = i; break; }
            }
        } else if (el.type === 'checkbox') {
            el.checked = !!val;
        } else if (el.value === '' || el.value === '0') {
            // Only fill if currently empty (don't overwrite user edits)
            el.value = val !== null ? val : '';
        } else {
            el.value = val !== null ? val : '';
        }
    }

    function fillForm(f) {
        setVal('fName',        f.name);
        setVal('fVariety',     f.variety);
        setVal('fBotFamily',   f.botanical_family);
        setVal('fType',        f.type);
        setVal('fSowingType',  f.sowing_type);
        setVal('fDaysGerm',    f.days_to_germinate);
        setVal('fDaysMat',     f.days_to_maturity);
        setVal('fSpacing',     f.spacing_cm);
        setVal('fRowSpacing',  f.row_spacing_cm);
        setVal('fSowDepth',    f.sowing_depth_mm);
        setVal('fSunExposure', f.sun_exposure);
        setVal('fFrostHardy',  f.frost_hardy);
        setVal('fCompanions',  f.companions);
        setVal('fAntagonists', f.antagonists);
        setVal('fYield',       f.yield_per_plant_kg);
        if (f.notes) {
            var notesEl = document.getElementById('fNotes');
            if (notesEl && !notesEl.value.trim()) notesEl.value = f.notes;
        }
        // Scroll to name field so user can review
        var nameEl = document.getElementById('fName');
        if (nameEl) nameEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}());
</script>
