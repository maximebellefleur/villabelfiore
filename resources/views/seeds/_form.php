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
        <span class="text-muted text-sm">Take or choose a photo of your seed packet — AI will pre-fill the form. Upload both sides for best results.</span>
    </div>

    <!-- Hidden file inputs -->
    <input type="file" id="aiPhotoInput"  accept="image/*" capture="environment" style="display:none">
    <input type="file" id="aiPhotoInput2" accept="image/*" capture="environment" style="display:none">

    <!-- Photo buttons row -->
    <div style="display:flex;align-items:center;gap:var(--spacing-2);flex-wrap:wrap">
        <button type="button" id="aiPhotoBtn" class="btn btn-secondary"
                style="display:flex;align-items:center;gap:6px">
            <span style="font-size:1.1rem">📷</span> Front of packet
        </button>
        <button type="button" id="aiPhotoBtn2" class="btn btn-secondary"
                style="display:flex;align-items:center;gap:6px">
            <span style="font-size:1.1rem">📷</span> Back of packet
        </button>
        <button type="button" id="aiRunBtn" class="btn btn-primary" style="display:none;align-items:center;gap:6px">
            <span>🔍</span> Identify
        </button>
    </div>

    <!-- Editable prompt (per-upload override — does NOT change the saved setting) -->
    <details id="aiPromptDetails" style="border:1px solid rgba(45,90,39,.2);border-radius:6px;padding:6px 10px;background:rgba(45,90,39,.03)">
        <summary style="font-size:.75rem;font-weight:600;cursor:pointer;color:var(--color-text-muted);user-select:none">
            ✏️ Extra instructions for this identification <span style="font-weight:400">(optional — overrides saved setting for this upload only)</span>
        </summary>
        <textarea id="aiPromptOverride" rows="3"
                  style="margin-top:6px;width:100%;border:1px solid var(--color-border);border-radius:4px;padding:6px 10px;font-size:.8rem;font-family:inherit;resize:vertical;background:var(--color-bg);color:var(--color-text);box-sizing:border-box"
                  placeholder="e.g. Answer in French. Prefer Italian variety names. Note soil pH in the notes field."><?= e($aiExtraPrompt ?? '') ?></textarea>
        <p style="font-size:.7rem;color:var(--color-text-muted);margin:4px 0 0">
            The base botanical prompt runs automatically. This text is appended on top.
            Change the saved default in <a href="<?= url('/settings') ?>#ai" style="color:var(--color-primary)">Settings → AI</a>.
        </p>
    </details>

    <!-- Previews -->
    <div style="display:flex;gap:var(--spacing-3);flex-wrap:wrap">
        <div id="aiPreviewWrap"  style="display:none;flex-direction:column;align-items:center;gap:4px">
            <img id="aiPreviewImg"  style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--color-primary)" src="" alt="Front">
            <span style="font-size:.68rem;color:var(--color-text-muted)">Front</span>
        </div>
        <div id="aiPreviewWrap2" style="display:none;flex-direction:column;align-items:center;gap:4px">
            <img id="aiPreviewImg2" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--color-primary)" src="" alt="Back">
            <span style="font-size:.68rem;color:var(--color-text-muted)">Back</span>
        </div>
    </div>

    <!-- Progress bar -->
    <div id="aiProgressBar" style="display:none;height:6px;background:var(--color-bg);border-radius:3px;overflow:hidden">
        <div style="width:0%;height:100%;background:var(--color-primary);border-radius:3px;transition:width .4s" id="aiProgressFill"></div>
    </div>

    <!-- Status line -->
    <div id="aiStatus" style="font-size:.85rem;color:var(--color-text-muted)"></div>

    <!-- ══ DEBUG PANEL — TEMPORARY (can be removed once AI is stable) ══════ -->
    <details id="aiDebugDetails" style="display:none;border:1px dashed #f59e0b;border-radius:6px;padding:8px 12px;background:#fffbeb">
        <summary style="font-size:.72rem;font-weight:700;color:#92400e;cursor:pointer;user-select:none">
            🐛 Debug — AI Status Log <span style="font-weight:400;color:var(--color-text-muted)">(temporary · can be removed later)</span>
        </summary>
        <div id="aiDebugLog" style="margin-top:8px;display:flex;flex-direction:column;gap:4px;font-size:.72rem;font-family:monospace"></div>
    </details>
    <!-- ══ END DEBUG PANEL ══════════════════════════════════════════════════ -->
</div>

<!-- ── Manual AI Prompt ──────────────────────────────────────────────────── -->
<div id="manualAiWrap" style="margin-bottom:var(--spacing-4)">
    <button type="button" id="manualAiToggle"
            style="background:none;border:1px dashed var(--color-border);border-radius:var(--radius);padding:7px 14px;font-size:.82rem;font-weight:600;cursor:pointer;color:var(--color-text-muted);display:flex;align-items:center;gap:6px;width:100%;justify-content:flex-start"
            onclick="toggleManualAi()">
        <span id="manualAiArrow" style="transition:transform .2s">▶</span>
        ✨ Generate with external AI — copy prompt &amp; paste result
    </button>

    <div id="manualAiPanel" style="display:none;margin-top:8px;border:1px solid rgba(99,102,241,.25);border-radius:var(--radius);padding:16px;background:rgba(99,102,241,.04)">

        <!-- Step 1: copy prompt -->
        <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#4f46e5;margin-bottom:8px">① Copy this prompt into ChatGPT, Gemini, or any AI</div>
        <textarea id="manualAiPrompt" rows="10" readonly
                  style="width:100%;border:1px solid var(--color-border);border-radius:6px;padding:10px;font-size:.78rem;font-family:monospace;background:var(--color-bg);color:var(--color-text);resize:vertical;box-sizing:border-box;line-height:1.5"></textarea>
        <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <button type="button" id="manualAiCopyBtn"
                    onclick="copyManualPrompt()"
                    style="background:#4f46e5;color:#fff;border:none;border-radius:var(--radius);padding:7px 16px;font-size:.82rem;font-weight:600;cursor:pointer">
                📋 Copy prompt
            </button>
            <span id="manualAiCopied" style="display:none;font-size:.8rem;color:#15803d;font-weight:600">✅ Copied to clipboard!</span>
        </div>

        <!-- Step 2: paste response -->
        <div id="manualAiStep2" style="margin-top:16px;border-top:1px solid rgba(99,102,241,.2);padding-top:16px">
            <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#4f46e5;margin-bottom:8px">② Paste the AI's JSON response here</div>
            <textarea id="manualAiPaste" rows="6" placeholder='Paste the JSON from your AI here, e.g. { "name": "Tomato", "variety": "Roma", ... }'
                      style="width:100%;border:1px solid var(--color-border);border-radius:6px;padding:10px;font-size:.78rem;font-family:monospace;background:var(--color-bg);color:var(--color-text);resize:vertical;box-sizing:border-box;line-height:1.5"></textarea>
            <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <button type="button"
                        onclick="applyManualAiJson()"
                        style="background:#15803d;color:#fff;border:none;border-radius:var(--radius);padding:7px 16px;font-size:.82rem;font-weight:600;cursor:pointer">
                    ✅ Pre-fill form from JSON
                </button>
                <span id="manualAiError" style="display:none;font-size:.8rem;color:#dc2626;font-weight:600"></span>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var PROMPT_TEMPLATE = [
        'You are a botanical and gardening expert. I want to add a seed to my seed bank.',
        'Return ONLY a valid JSON object — no explanation, no markdown, no code fences.',
        '',
        'Use this exact structure (all fields optional except "name"):',
        '',
        JSON.stringify({
            name: "Common plant name",
            variety: "Specific cultivar, or empty string",
            botanical_family: "e.g. Solanaceae",
            type: "vegetable",
            sowing_type: "direct",
            sun_exposure: "Full sun",
            days_to_germinate: 7,
            days_to_maturity: 75,
            spacing_cm: 40,
            row_spacing_cm: 60,
            sowing_depth_mm: 5,
            soil_notes: "Well-drained, rich soil",
            frost_hardy: false,
            planting_months: [3, 4, 5],
            harvest_months: [7, 8, 9],
            companions: "Basil, Marigold, Carrot",
            antagonists: "Fennel, Potato",
            yield_per_plant_kg: 2.0,
            notes: "Extra growing tips, history, provenance..."
        }, null, 2),
        '',
        'Field constraints:',
        '  type          → one of: vegetable | herb | fruit | flower | other',
        '  sowing_type   → one of: direct | nursery | both',
        '  planting_months & harvest_months → array of month numbers (1=Jan … 12=Dec)',
        '  companions & antagonists → comma-separated plant names as a single string',
        '  frost_hardy   → true or false',
        '',
        'The seed I want to add:',
        '[DESCRIBE YOUR SEED HERE — name, variety, any details you know]'
    ].join('\n');

    document.getElementById('manualAiPrompt').value = PROMPT_TEMPLATE;

    window.toggleManualAi = function () {
        var panel = document.getElementById('manualAiPanel');
        var arrow = document.getElementById('manualAiArrow');
        var open  = panel.style.display === 'none';
        panel.style.display = open ? 'block' : 'none';
        arrow.style.transform = open ? 'rotate(90deg)' : '';
    };

    window.copyManualPrompt = function () {
        var ta  = document.getElementById('manualAiPrompt');
        var msg = document.getElementById('manualAiCopied');
        ta.select();
        try { document.execCommand('copy'); } catch(e) { navigator.clipboard && navigator.clipboard.writeText(ta.value); }
        msg.style.display = 'inline';
        setTimeout(function () { msg.style.display = 'none'; }, 3000);
    };

    window.applyManualAiJson = function () {
        var raw = document.getElementById('manualAiPaste').value.trim();
        var err = document.getElementById('manualAiError');
        err.style.display = 'none';
        if (!raw) { err.textContent = 'Paste the JSON first.'; err.style.display = 'inline'; return; }

        // Strip markdown code fences if the AI wrapped the JSON
        raw = raw.replace(/^```(?:json)?\s*/i, '').replace(/\s*```$/, '').trim();

        // Extract first JSON object if there's surrounding text
        var match = raw.match(/\{[\s\S]*\}/);
        if (!match) { err.textContent = 'No JSON object found — make sure you copy the full response.'; err.style.display = 'inline'; return; }

        var data;
        try { data = JSON.parse(match[0]); }
        catch (e) { err.textContent = 'Invalid JSON: ' + e.message; err.style.display = 'inline'; return; }

        if (!window.seedFillForm) { err.textContent = 'Form not ready — refresh and try again.'; err.style.display = 'inline'; return; }

        window.seedFillForm(data);

        // Close panel
        document.getElementById('manualAiPanel').style.display = 'none';
        document.getElementById('manualAiArrow').style.transform = '';

        // Clear paste box for next time
        document.getElementById('manualAiPaste').value = '';
    };
}());
</script>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Identity</legend>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Name <span class="required">*</span></label>
            <input type="text" name="name" id="fName" class="form-input" required value="<?= e($seed['name'] ?? '') ?>" placeholder="e.g. Tomato">
            <div id="fNameDupe" style="display:none;margin-top:5px;padding:6px 10px;background:#fff3cd;border:1px solid #ffc107;border-radius:5px;font-size:0.8rem;color:#856404"></div>
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
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Display Color <small class="text-muted" style="font-weight:400">(used on planting chips)</small></label>
            <?php
            $_seedColor = $seed['color'] ?? null;
            if (!$_seedColor || !preg_match('/^#[0-9a-f]{6}$/i', $_seedColor)) {
                $_key = (int)($seed['id'] ?? 0) ?: ($seed['name'] ?? 'other');
                $_seedColor = \App\Support\GardenHelpers::defaultCatalogColor($_key);
            }
            ?>
            <div style="display:flex;align-items:center;gap:8px">
                <input type="color" name="color" id="fColor" value="<?= e($_seedColor) ?>" style="width:46px;height:36px;padding:0;border:1px solid var(--color-border);border-radius:6px;cursor:pointer;background:transparent">
                <input type="text" id="fColorHex" value="<?= e($_seedColor) ?>" pattern="^#[0-9a-fA-F]{6}$" maxlength="7" class="form-input" style="font-family:var(--font-mono);max-width:120px" oninput="document.getElementById('fColor').value = this.value;">
                <span style="font-size:.75rem;color:var(--color-text-muted)">Auto-set on new seeds — change anytime.</span>
            </div>
            <script>
            (function(){
                var c = document.getElementById('fColor'), h = document.getElementById('fColorHex');
                if (c && h) c.addEventListener('input', function(){ h.value = c.value; });
            })();
            </script>
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
    var btn        = document.getElementById('aiPhotoBtn');
    var btn2       = document.getElementById('aiPhotoBtn2');
    var runBtn     = document.getElementById('aiRunBtn');
    var fileInput  = document.getElementById('aiPhotoInput');
    var fileInput2 = document.getElementById('aiPhotoInput2');
    var status     = document.getElementById('aiStatus');
    var preview    = document.getElementById('aiPreviewWrap');
    var preview2   = document.getElementById('aiPreviewWrap2');
    var previewImg = document.getElementById('aiPreviewImg');
    var previewImg2= document.getElementById('aiPreviewImg2');
    var progBar    = document.getElementById('aiProgressBar');
    var progFill   = document.getElementById('aiProgressFill');
    var debugBox   = document.getElementById('aiDebugDetails');
    var debugLog   = document.getElementById('aiDebugLog');

    if (!btn || !fileInput) return;

    var images = { front: null, back: null }; // { b64, mime }

    // ── Debug helpers ──────────────────────────────────────────────────────
    function dbg(step, value) {
        debugBox.style.display = '';
        var row = document.createElement('div');
        row.style.cssText = 'display:flex;gap:6px;border-bottom:1px solid #fde68a;padding:3px 0';
        row.innerHTML = '<span style="color:#92400e;min-width:170px;flex-shrink:0">› ' + escH(step) + '</span>'
                      + '<span style="color:#374151;word-break:break-all">' + escH(String(value)) + '</span>';
        debugLog.appendChild(row);
    }
    function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function dbgClear() { debugLog.innerHTML = ''; }
    function renderDebugArray(arr) {
        if (!Array.isArray(arr)) return;
        arr.forEach(function(e){ dbg(e.step || '?', e.value !== undefined ? e.value : ''); });
    }

    // ── File pickers ───────────────────────────────────────────────────────
    btn.addEventListener('click',  function () { fileInput.click(); });
    btn2.addEventListener('click', function () { fileInput2.click(); });

    function loadImage(file, slot, previewEl, previewWrapEl, btnEl) {
        var objUrl = URL.createObjectURL(file);
        previewEl.src = objUrl;
        previewWrapEl.style.display = 'flex';
        setStatus('📷 Image ' + slot + ' loaded — compressing…', 'var(--color-text-muted)');
        var img = new Image();
        img.onload = function () {
            var MAX = 800, w = img.naturalWidth, h = img.naturalHeight;
            if (w > MAX || h > MAX) {
                if (w >= h) { h = Math.round(h * MAX / w); w = MAX; }
                else        { w = Math.round(w * MAX / h); h = MAX; }
            }
            var canvas = document.createElement('canvas');
            canvas.width = w; canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
            URL.revokeObjectURL(objUrl);
            canvas.toBlob(function (blob) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    images[slot] = { b64: e.target.result.split(',')[1], mime: 'image/jpeg' };
                    var kb = Math.round(blob.size / 1024);
                    setStatus('📷 Image ' + slot + ' ready (' + kb + ' KB). ' + (slot === 'front' ? 'Add a back photo or click Identify.' : 'Click Identify.'), 'var(--color-text-muted)');
                    runBtn.style.display = 'inline-flex';
                };
                reader.readAsDataURL(blob);
            }, 'image/jpeg', 0.72);
        };
        img.src = objUrl;
    }

    fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (file) loadImage(file, 'front', previewImg, preview, btn);
    });
    fileInput2.addEventListener('change', function () {
        var file = fileInput2.files && fileInput2.files[0];
        if (file) loadImage(file, 'back', previewImg2, preview2, btn2);
    });

    // ── Run identification ─────────────────────────────────────────────────
    runBtn.addEventListener('click', function () {
        if (!images.front) { setStatus('⚠ Please load at least the front photo first.', '#dc3545'); return; }

        dbgClear();
        dbg('start', new Date().toISOString());
        dbg('images_loaded', (images.front ? 'front ✓' : '') + (images.back ? ' + back ✓' : ''));

        setProgress(5);
        setStatus('📤 Sending images to AI…', 'var(--color-text-muted)');
        progBar.style.display = 'block';
        runBtn.disabled = true; btn.disabled = true; btn2.disabled = true;

        var promptOverride = (document.getElementById('aiPromptOverride') || {}).value || '';

        var body = new URLSearchParams();
        body.append('_token',               '<?= e(\App\Support\CSRF::getToken()) ?>');
        body.append('image_data',            images.front.b64);
        body.append('image_mime',            images.front.mime);
        body.append('extra_prompt_override', promptOverride);
        if (images.back) {
            body.append('image_data_2', images.back.b64);
            body.append('image_mime_2', images.back.mime);
        }

        dbg('extra_prompt', promptOverride ? promptOverride.slice(0, 80) + '…' : '(none)');
        dbg('posting_to', '<?= url('/api/ai/identify-seed') ?>');

        var resultReceived = false;
        var modelCount     = 0;
        var watchdog       = null;

        function enableButtons() {
            runBtn.disabled = false; btn.disabled = false; btn2.disabled = false;
            if (watchdog) { clearTimeout(watchdog); watchdog = null; }
        }

        // Safety net: if the server goes silent for 90 s, recover gracefully
        watchdog = setTimeout(function () {
            if (!resultReceived) {
                enableButtons();
                progBar.style.display = 'none';
                dbg('timeout', 'No response after 90 s');
                setStatus('⏱ Request timed out. Click Identify to try again.', '#dc3545');
            }
        }, 90000);

        function handleEvent(evt) {
            if (evt.type === 'log') {
                dbg(evt.step || '?', evt.value !== undefined ? String(evt.value) : '');

                if (evt.step === 'fetching_models') {
                    setStatus('🔍 Checking available Gemini models for your key…', 'var(--color-text-muted)');
                    setProgress(10);
                } else if (evt.step === 'models_available') {
                    setStatus('📋 Model list ready.', 'var(--color-text-muted)');
                    setProgress(20);
                } else if (evt.step === 'will_try') {
                    modelCount = String(evt.value).split('→').length;
                    setProgress(25);
                } else if (evt.step === 'trying_model') {
                    setStatus('🤖 ' + evt.value + '…', 'var(--color-text-muted)');
                    setProgress(Math.min(30 + modelCount * 12, 72));
                } else if (evt.step === 'model_skipped') {
                    setStatus('⚠ Skipped: ' + evt.value, '#f59e0b');
                } else if (evt.step === 'model_used') {
                    setStatus('⚙️ Parsing response from ' + evt.value + '…', 'var(--color-text-muted)');
                    setProgress(88);
                } else if (evt.step === 'all_failed') {
                    setStatus('⚠ All models tried — see log below.', '#dc3545');
                }
            } else if (evt.type === 'result') {
                resultReceived = true;
                setProgress(100);
                enableButtons();
                progBar.style.display = 'none';

                if (!evt.ok) {
                    dbg('error', evt.error || 'unknown');
                    setStatus('❌ ' + (evt.error || 'AI error') + ' — click Identify to try again.', '#dc3545');
                    return;
                }

                dbg('fields_received', Object.keys(evt.fields || {}).join(', '));
                fillForm(evt.fields);
                setStatus('✅ Form pre-filled — review and adjust as needed.', 'var(--color-primary)');
            }
        }

        fetch('<?= url('/api/ai/identify-seed') ?>', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(function (res) {
            var reader  = res.body.getReader();
            var decoder = new TextDecoder();
            var buffer  = '';

            function pump() {
                return reader.read().then(function (chunk) {
                    if (chunk.done) {
                        if (!resultReceived) {
                            enableButtons();
                            progBar.style.display = 'none';
                            dbg('stream_closed', 'Connection closed without a result');
                            setStatus('❌ Connection dropped. Click Identify to try again.', '#dc3545');
                        }
                        return;
                    }
                    buffer += decoder.decode(chunk.value, { stream: true });
                    var lines = buffer.split('\n');
                    buffer = lines.pop();
                    lines.forEach(function (line) {
                        line = line.trim();
                        if (!line.startsWith('data: ')) return;
                        try { handleEvent(JSON.parse(line.slice(6))); } catch (e) { /* malformed line */ }
                    });
                    return pump();
                });
            }
            return pump();
        })
        .catch(function (err) {
            enableButtons();
            progBar.style.display = 'none';
            dbg('fetch_error', err.message);
            setStatus('❌ Network error: ' + err.message + ' — click Identify to try again.', '#dc3545');
        });
    });

    function setStatus(msg, color) {
        status.textContent = msg;
        status.style.color = color || 'var(--color-text-muted)';
    }
    function setProgress(pct) {
        progFill.style.width = pct + '%';
    }

    // ── Fill form fields ───────────────────────────────────────────────────
    function setVal(id, val) {
        var el = document.getElementById(id);
        if (!el || val === null || val === undefined) return;
        if (el.tagName === 'SELECT') {
            for (var i = 0; i < el.options.length; i++) {
                if (el.options[i].value === String(val)) { el.selectedIndex = i; break; }
            }
        } else if (el.type === 'checkbox') {
            el.checked = !!val;
        } else {
            el.value = val !== null ? val : '';
        }
    }

    function fillMonths(fieldName, months) {
        if (!Array.isArray(months) || !months.length) return;
        var activeSet = {};
        months.forEach(function(m) { activeSet[parseInt(m)] = true; });
        document.querySelectorAll('input[name="' + fieldName + '[]"]').forEach(function(cb) {
            var m = parseInt(cb.value);
            cb.checked = !!activeSet[m];
            // Trigger the inline style handler each label has via onchange
            cb.dispatchEvent(new Event('change'));
        });
        dbg('filled_' + fieldName, months.join(', '));
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
        fillMonths('planting_months', f.planting_months);
        fillMonths('harvest_months',  f.harvest_months);
        if (f.notes) {
            var notesEl = document.getElementById('fNotes');
            if (notesEl && !notesEl.value.trim()) notesEl.value = f.notes;
        }
        var nameEl = document.getElementById('fName');
        if (nameEl) nameEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    window.seedFillForm   = fillForm;
    window.seedFillMonths = fillMonths;
}());
</script>

<!-- ── Duplicate name check ─────────────────────────────────────────────── -->
<script>
(function () {
    var nameInput = document.getElementById('fName');
    var dupeBox   = document.getElementById('fNameDupe');
    if (!nameInput || !dupeBox) return;

    var excludeId = <?= isset($seed['id']) ? (int)$seed['id'] : 0 ?>;

    nameInput.addEventListener('blur', function () {
        var val = nameInput.value.trim();
        dupeBox.style.display = 'none';
        if (!val) return;
        var url = '<?= url('/api/seeds/check-name') ?>?name=' + encodeURIComponent(val) + (excludeId ? '&exclude=' + excludeId : '');
        fetch(url).then(function(r){ return r.json(); }).then(function(data) {
            if (!data.exists) return;
            var label = data.name + (data.variety ? ' (' + data.variety + ')' : '');
            dupeBox.innerHTML = '⚠ <strong>' + escH(label) + '</strong> already exists in your catalog — <a href="<?= url('/seeds/') ?>' + data.id + '" style="color:#856404;font-weight:600">View it</a>';
            dupeBox.style.display = 'block';
        }).catch(function(){});
    });

    function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
}());
</script>
