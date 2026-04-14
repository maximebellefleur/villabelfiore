<?php
$monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$plantMonths = !empty($seed['planting_months']) ? json_decode($seed['planting_months'], true) : [];
$harvMonths  = !empty($seed['harvest_months'])  ? json_decode($seed['harvest_months'], true)  : [];
$companions  = !empty($seed['companions'])  ? implode(', ', json_decode($seed['companions'],  true)) : '';
$antagonists = !empty($seed['antagonists']) ? implode(', ', json_decode($seed['antagonists'], true)) : '';
?>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Identity</legend>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-input" required value="<?= e($seed['name'] ?? '') ?>" placeholder="e.g. Tomato">
        </div>
        <div class="form-group">
            <label class="form-label">Variety</label>
            <input type="text" name="variety" class="form-input" value="<?= e($seed['variety'] ?? '') ?>" placeholder="e.g. Roma">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Botanical Family</label>
            <input type="text" name="botanical_family" class="form-input" value="<?= e($seed['botanical_family'] ?? '') ?>" placeholder="e.g. Solanaceae">
        </div>
        <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-input">
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
            <select name="sowing_type" class="form-input">
                <option value="direct"  <?= ($seed['sowing_type'] ?? '') === 'direct'  ? 'selected' : '' ?>>Direct sow</option>
                <option value="nursery" <?= ($seed['sowing_type'] ?? '') === 'nursery' ? 'selected' : '' ?>>Nursery / transplant</option>
                <option value="both"    <?= ($seed['sowing_type'] ?? '') === 'both'    ? 'selected' : '' ?>>Both</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Sun Exposure</label>
            <input type="text" name="sun_exposure" class="form-input" value="<?= e($seed['sun_exposure'] ?? '') ?>" placeholder="e.g. Full sun">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Days to Germinate</label>
            <input type="number" name="days_to_germinate" class="form-input" min="1" max="365" value="<?= e($seed['days_to_germinate'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Days to Maturity</label>
            <input type="number" name="days_to_maturity" class="form-input" min="1" max="730" value="<?= e($seed['days_to_maturity'] ?? '') ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Spacing (cm)</label>
            <input type="number" name="spacing_cm" class="form-input" min="1" value="<?= e($seed['spacing_cm'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Row Spacing (cm)</label>
            <input type="number" name="row_spacing_cm" class="form-input" min="1" value="<?= e($seed['row_spacing_cm'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Sowing Depth (mm)</label>
            <input type="number" name="sowing_depth_mm" class="form-input" min="0" value="<?= e($seed['sowing_depth_mm'] ?? '') ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Soil Notes</label>
        <input type="text" name="soil_notes" class="form-input" value="<?= e($seed['soil_notes'] ?? '') ?>" placeholder="e.g. Well-drained, slightly acidic">
    </div>
    <div class="form-group">
        <label class="form-label">
            <input type="checkbox" name="frost_hardy" value="1" <?= !empty($seed['frost_hardy']) ? 'checked' : '' ?>>
            Frost Hardy
        </label>
    </div>
</fieldset>

<fieldset class="fieldset">
    <legend class="fieldset-legend">Planting Calendar</legend>
    <div class="form-group">
        <label class="form-label">Sowing / Planting Months</label>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px">
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
            <input type="text" name="companions" class="form-input" value="<?= e($companions) ?>" placeholder="Basil, Carrot, Marigold (comma-separated)">
        </div>
        <div class="form-group">
            <label class="form-label">Antagonists</label>
            <input type="text" name="antagonists" class="form-input" value="<?= e($antagonists) ?>" placeholder="Fennel, Brassicas (comma-separated)">
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Yield per Plant (kg)</label>
        <input type="number" step="0.01" name="yield_per_plant_kg" class="form-input" value="<?= e($seed['yield_per_plant_kg'] ?? '') ?>" placeholder="e.g. 2.5">
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
        <textarea name="notes" class="form-input" rows="4" placeholder="Extra growing tips, provenance, supplier…"><?= e($seed['notes'] ?? '') ?></textarea>
    </div>
</fieldset>
