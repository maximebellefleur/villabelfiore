<?php
$csrfToken    = \App\Support\CSRF::getToken();
$numLines     = max(1, $bedRows);
$currentMonth = $currentMonth ?? (int)date('n');
$monthNames   = ['January','February','March','April','May','June','July','August','September','October','November','December'];

// SVG dimensions
$svgMaxW = 380; $svgMaxH = 260;
if ($widthM > 0 && $lengthM > 0) {
    $ratio = $widthM / $lengthM;
    if ($ratio >= 1) {
        $svgW = $svgMaxW; $svgH = round($svgMaxW / $ratio);
        if ($svgH > $svgMaxH) { $svgH = $svgMaxH; $svgW = round($svgMaxH * $ratio); }
    } else {
        $svgH = $svgMaxH; $svgW = round($svgMaxH * $ratio);
        if ($svgW > $svgMaxW) { $svgW = $svgMaxW; $svgH = round($svgMaxW / $ratio); }
    }
} else {
    $svgW = 280; $svgH = 160;
}
$svgW = max($svgW, 100); $svgH = max($svgH, 60);

$statusColors = ['growing'=>'#22c55e','planned'=>'#f59e0b','harvested'=>'#3b82f6','empty'=>'#e2e8f0'];
$statusLabels = ['growing'=>'Growing','planned'=>'Planned','harvested'=>'Harvested','empty'=>'Empty'];
?>
<style>
.bed-page { max-width:680px;margin:0 auto; }
.bed-header { display:flex;align-items:center;gap:10px;margin-bottom:var(--spacing-4); }
.bed-back { color:var(--color-text-muted);text-decoration:none;display:flex;align-items:center;flex-shrink:0; }
.bed-title { font-size:1.15rem;font-weight:800;flex:1;min-width:0; }
.bed-garden-badge { font-size:.72rem;font-weight:700;color:var(--color-primary);background:var(--color-primary-soft);padding:2px 10px;border-radius:999px;white-space:nowrap;text-decoration:none; }
.bed-edit-link { font-size:.75rem;color:var(--color-text-muted);text-decoration:none;white-space:nowrap; }
.bed-edit-link:hover { color:var(--color-primary); }

/* Schematic */
.bed-schematic { display:flex;flex-direction:column;align-items:center;margin-bottom:var(--spacing-5); }
.bed-schematic svg { display:block;border-radius:6px;box-shadow:0 2px 12px rgba(0,0,0,.1); }
.bed-dim-label { font-size:.72rem;color:var(--color-text-muted);margin-top:6px; }

/* Lines */
.bed-lines-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-2); }
.bed-lines-title { font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted); }
.bed-line-row { background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-lg);margin-bottom:6px;overflow:hidden;transition:border-color .2s,background .2s; }
.bed-line-row--overcap { border-color:#fca5a5 !important;background:#fef2f2 !important; }
.bed-line-main { display:flex;align-items:center;gap:10px;padding:11px 14px; }
.bed-line-num { width:26px;height:26px;border-radius:50%;background:var(--color-border);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:var(--color-text-muted);flex-shrink:0; }
.bed-line-crop { flex:1;min-width:0; }
.bed-line-name { font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.bed-line-sub  { font-size:.72rem;color:var(--color-text-muted);margin-top:1px; }
.bed-status-badge { font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;flex-shrink:0;white-space:nowrap; }
.bed-status-badge--growing   { background:#dcfce7;color:#15803d; }
.bed-status-badge--planned   { background:#fef3c7;color:#92400e; }
.bed-status-badge--harvested { background:#dbeafe;color:#1e40af; }
.bed-status-badge--empty     { background:var(--color-border);color:var(--color-text-muted); }
.bed-line-btns { display:flex;gap:4px;flex-shrink:0; }
.bed-line-btn { background:none;border:1px solid var(--color-border);border-radius:var(--radius);padding:4px 8px;font-size:.7rem;font-weight:600;cursor:pointer;color:var(--color-text-muted);transition:background .12s,color .12s,border-color .12s;white-space:nowrap; }
.bed-line-btn:hover { background:var(--color-primary-soft);color:var(--color-primary);border-color:var(--color-primary); }
.bed-line-btn--danger:hover { background:#fee2e2;color:#dc2626;border-color:#dc2626; }

/* Companions panel */
.bed-companions-panel { border-top:1px solid var(--color-border);padding:12px 14px;background:#f8fafc;display:none; }
.bed-companions-panel.open { display:block; }
.bed-companions-loading { color:var(--color-text-muted);font-size:.82rem; }
.bed-companion-list { list-style:none;padding:0;margin:0 0 8px;display:flex;flex-direction:column;gap:4px; }
.bed-companion-item { font-size:.82rem;display:flex;gap:6px;align-items:center; }
.bed-companion-item strong { flex-shrink:0; }
.bed-companion-item span { color:var(--color-text-muted);flex:1;min-width:0; }
.bed-antagonist-item { color:#b91c1c; }
.bed-companions-tip { font-size:.78rem;font-style:italic;color:var(--color-text-muted);border-top:1px solid var(--color-border);padding-top:8px;margin-top:4px; }
.companion-add-btn { flex-shrink:0;background:#15803d;color:#fff;border:none;border-radius:var(--radius);padding:3px 9px;font-size:.72rem;font-weight:700;cursor:pointer;white-space:nowrap; }
.companion-add-btn:hover { background:#166534; }
.companion-add-btn:disabled { background:#9ca3af;cursor:default; }

/* Edit form */
.bed-edit-form { border-top:1px solid var(--color-border);padding:12px 14px;background:#f8fafc;display:none; }
.bed-edit-form.open { display:block; }
.bed-edit-grid { display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px; }
.bed-edit-grid--full { grid-column:1/-1; }
.bed-edit-input { width:100%;padding:7px 10px;border:1.5px solid var(--color-border);border-radius:var(--radius);font-size:.85rem;font-family:inherit;background:var(--color-surface);box-sizing:border-box; }
.bed-edit-input:focus { outline:none;border-color:var(--color-primary); }
.bed-edit-actions { display:flex;gap:6px;justify-content:flex-end; }

/* Config panel */
.bed-config-bar { background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:12px 16px;margin-bottom:var(--spacing-4);display:flex;align-items:center;gap:10px;flex-wrap:wrap; }
.bed-config-summary { flex:1;min-width:0;font-size:.83rem;color:var(--color-text-muted); }
.bed-config-summary strong { color:var(--color-text);font-weight:700; }
.bed-config-form { border-top:1px solid var(--color-border);padding:14px 16px;background:#f8fafc;display:none; }
.bed-config-form.open { display:block; }
.bed-config-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;margin-bottom:10px; }
.bed-config-spacing { font-size:.82rem;color:#15803d;font-weight:700;padding:6px 0; }

/* Prep section */
.bed-prep { background:linear-gradient(135deg,rgba(34,197,94,.07),rgba(34,197,94,.02));border:1.5px solid #bbf7d0;border-radius:var(--radius-lg);padding:16px 18px;margin-top:var(--spacing-4); }
.bed-prep-title { font-size:.8rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#15803d;margin-bottom:10px;display:flex;align-items:center;gap:6px; }
.bed-prep-item { display:flex;align-items:flex-start;gap:8px;font-size:.83rem;padding:6px 0;border-bottom:1px solid #dcfce7; }
.bed-prep-item:last-child { border-bottom:none; }
.bed-prep-icon { flex-shrink:0;width:20px;text-align:center; }
</style>

<div class="bed-page">

<div class="bed-header">
    <a href="<?= url('/items/' . (int)$item['id']) ?>" class="bed-back">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <span class="bed-title"><?= e($item['name']) ?></span>
    <?php if ($parentGarden): ?>
    <a href="<?= url('/garden') ?>" class="bed-garden-badge"><?= e($parentGarden['name']) ?></a>
    <?php endif; ?>
    <a href="<?= url('/items/' . (int)$item['id'] . '/edit') ?>" class="bed-edit-link">Edit bed ✏</a>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Bed configuration -->
<?php
$_spacing = ($numLines > 0 && $widthM > 0) ? round($widthM / $numLines * 100) / 100 : null;
?>
<div class="bed-config-bar" id="bedConfigBar">
    <div class="bed-config-summary">
        <?php if ($bedRows > 0): ?>
            <strong><?= $bedRows ?> line<?= $bedRows !== 1 ? 's' : '' ?></strong>
            · <?= $lineDir === 'NS' ? 'N–S' : 'E–W' ?> rows
            <?php if ($widthM > 0): ?> · <?= $widthM ?>m wide<?php endif; ?>
            <?php if ($lengthM > 0): ?> × <?= $lengthM ?>m long<?php endif; ?>
            <?php if ($_spacing): ?> · <span style="color:#15803d;font-weight:700"><?= $_spacing ?>m spacing</span><?php endif; ?>
        <?php else: ?>
            <span style="color:#f59e0b;font-weight:600">⚠ Lines not configured yet</span>
        <?php endif; ?>
    </div>
    <button class="btn btn-ghost btn-sm" onclick="toggleBedConfig()">⚙ Configure Lines</button>
</div>
<div class="bed-config-form" id="bedConfigForm">
    <div class="bed-config-grid">
        <div>
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Lines</label>
            <input type="number" min="1" max="50" class="bed-edit-input" id="cfgRows" value="<?= (int)($bedRows ?: 1) ?>" oninput="updateSpacing()">
        </div>
        <div>
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Direction</label>
            <select class="bed-edit-input" id="cfgDir" onchange="updateSpacing()">
                <option value="NS" <?= $lineDir === 'NS' ? 'selected' : '' ?>>N–S (across width)</option>
                <option value="EW" <?= $lineDir === 'EW' ? 'selected' : '' ?>>E–W (across length)</option>
            </select>
        </div>
        <div>
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Width (m)</label>
            <input type="number" min="0" step="0.1" class="bed-edit-input" id="cfgWidth" value="<?= $widthM ?: '' ?>" placeholder="e.g. 1.2" oninput="updateSpacing()">
        </div>
        <div>
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Length (m)</label>
            <input type="number" min="0" step="0.1" class="bed-edit-input" id="cfgLength" value="<?= $lengthM ?: '' ?>" placeholder="e.g. 5">
        </div>
    </div>
    <div class="bed-config-spacing" id="cfgSpacing"></div>
    <div class="bed-edit-actions">
        <button class="btn btn-ghost btn-sm" onclick="toggleBedConfig()">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="saveBedConfig()">Save</button>
    </div>
</div>

<!-- Schematic -->
<div class="bed-schematic">
    <svg width="<?= $svgW ?>" height="<?= $svgH ?>" viewBox="0 0 <?= $svgW ?> <?= $svgH ?>" xmlns="http://www.w3.org/2000/svg">
        <rect x="0" y="0" width="<?= $svgW ?>" height="<?= $svgH ?>" fill="#f1f5f9" rx="4"/>
        <?php if ($lineDir === 'EW'): ?>
            <?php $stripeW = $svgW / $numLines; ?>
            <?php for ($li = 0; $li < $numLines; $li++):
                $lNum = $li + 1;
                $planting = $plantingMap[$lNum] ?? null;
                $status = $planting['status'] ?? 'empty';
                $color = $statusColors[$status];
                $x = round($li * $stripeW);
                $w = round($stripeW);
                $cropLabel = $planting['crop_name'] ?? '';
            ?>
            <rect x="<?= $x ?>" y="0" width="<?= $w ?>" height="<?= $svgH ?>" fill="<?= $color ?>" fill-opacity="0.75"/>
            <?php if ($li > 0): ?><line x1="<?= $x ?>" y1="0" x2="<?= $x ?>" y2="<?= $svgH ?>" stroke="#fff" stroke-width="1.5"/><?php endif; ?>
            <?php if ($cropLabel):
                $cx = round($x + $w/2);
                $cy = round($svgH/2);
                $fs = min(11, max(7, $svgH / max(1, mb_strlen($cropLabel)) * 0.85));
            ?>
            <text x="<?= $cx ?>" y="<?= $cy + 4 ?>" text-anchor="middle" transform="rotate(-90 <?= $cx ?> <?= $cy ?>)" font-size="<?= $fs ?>" fill="#1e293b" font-family="sans-serif" font-weight="600"><?= e(mb_substr($cropLabel, 0, 22)) ?></text>
            <?php endif; ?>
            <text x="<?= $x + $w/2 ?>" y="<?= $svgH - 6 ?>" text-anchor="middle" font-size="9" fill="#64748b" font-family="sans-serif"><?= $lNum ?></text>
            <?php endfor; ?>
        <?php else: ?>
            <?php $stripeH = $svgH / $numLines; ?>
            <?php for ($li = 0; $li < $numLines; $li++):
                $lNum = $li + 1;
                $planting = $plantingMap[$lNum] ?? null;
                $status = $planting['status'] ?? 'empty';
                $color = $statusColors[$status];
                $y = round($li * $stripeH);
                $h = round($stripeH);
                $cropLabel = $planting['crop_name'] ?? '';
            ?>
            <rect x="0" y="<?= $y ?>" width="<?= $svgW ?>" height="<?= $h ?>" fill="<?= $color ?>" fill-opacity="0.75"/>
            <?php if ($li > 0): ?><line x1="0" y1="<?= $y ?>" x2="<?= $svgW ?>" y2="<?= $y ?>" stroke="#fff" stroke-width="1.5"/><?php endif; ?>
            <?php if ($cropLabel): ?>
            <text x="<?= $svgW/2 ?>" y="<?= $y + $h/2 + 4 ?>" text-anchor="middle" font-size="<?= min(12, max(8, $h * 0.5)) ?>" fill="#1e293b" font-family="sans-serif" font-weight="600"><?= e(mb_substr($cropLabel, 0, 20)) ?></text>
            <?php endif; ?>
            <text x="14" y="<?= $y + $h/2 + 4 ?>" font-size="9" fill="#64748b" font-family="sans-serif"><?= $lNum ?></text>
            <?php endfor; ?>
        <?php endif; ?>
        <rect x="0" y="0" width="<?= $svgW ?>" height="<?= $svgH ?>" fill="none" stroke="#94a3b8" stroke-width="1.5" rx="4"/>
    </svg>
    <?php if ($widthM > 0 && $lengthM > 0): ?>
    <div class="bed-dim-label"><?= $widthM ?>m wide × <?= $lengthM ?>m long · <?= $numLines ?> line<?= $numLines !== 1 ? 's' : '' ?> · <?= $lineDir === 'NS' ? 'N–S rows' : 'E–W rows' ?></div>
    <?php endif; ?>
</div>

<!-- Lines -->
<div class="bed-lines-head">
    <span class="bed-lines-title">Lines</span>
    <?php if ($widthM > 0 && $numLines > 0): ?>
    <span style="font-size:.72rem;color:var(--color-text-muted)"><?= round($widthM / $numLines * 100) / 100 ?>m per line</span>
    <?php endif; ?>
</div>

<?php
// Build seed spacing lookup for capacity warnings
$seedSpacingMap = [];
foreach ($allSeeds as $_s) { $seedSpacingMap[(int)$_s['id']] = (int)($_s['spacing_cm'] ?? 0); }
?>
<?php for ($li = 1; $li <= $numLines; $li++):
    $planting = $plantingMap[$li] ?? null;
    $status   = $planting['status'] ?? 'empty';
    $cropName = $planting['crop_name'] ?? '';
    $variety  = $planting['variety']   ?? '';
    $pid      = $planting['id']        ?? 0;
    $plantedAt = $planting['planted_at'] ?? '';
    $harvestAt = $planting['expected_harvest_at'] ?? '';
    $notes     = $planting['notes'] ?? '';
    $plantCount = (int)($planting['plant_count'] ?? 0);
    $subParts  = [];
    if ($plantedAt) $subParts[] = 'Planted ' . date('d M Y', strtotime($plantedAt));
    if ($harvestAt) $subParts[] = 'Harvest by ' . date('d M Y', strtotime($harvestAt));
    if ($variety)   $subParts[] = $variety;
    // Capacity
    $lineSpacing = $seedSpacingMap[(int)($planting['seed_id'] ?? 0)] ?? 0;
    $lineCapacity = ($lineSpacing > 0 && $lengthM > 0) ? (int)floor($lengthM * 100 / $lineSpacing) : 0;
    $overCap = ($lineCapacity > 0 && $plantCount > $lineCapacity);
?>
<div class="bed-line-row<?= $overCap ? ' bed-line-row--overcap' : '' ?>" id="lineRow<?= $li ?>"
     data-capacity="<?= $lineCapacity ?>" data-pid="<?= $pid ?>">
    <div class="bed-line-main">
        <div class="bed-line-num"><?= $li ?></div>
        <div class="bed-line-crop">
            <div class="bed-line-name"><?= $cropName ? e($cropName) : '<span style="color:var(--color-text-muted);font-weight:400">Empty</span>' ?></div>
            <?php if ($subParts): ?><div class="bed-line-sub"><?= e(implode(' · ', $subParts)) ?></div><?php endif; ?>
        </div>
        <?php if ($pid && $plantCount > 0): ?>
        <div style="display:flex;flex-direction:column;align-items:center;gap:1px;flex-shrink:0">
            <div style="display:flex;align-items:center;gap:2px">
                <button class="bed-line-btn" onclick="adjustQty(<?= $pid ?>,<?= $li ?>,-1)" style="padding:3px 7px">−</button>
                <span id="lineQty<?= $li ?>" style="font-size:.78rem;font-weight:700;min-width:28px;text-align:center"><?= $plantCount ?></span>
                <button class="bed-line-btn" onclick="adjustQty(<?= $pid ?>,<?= $li ?>,1)" style="padding:3px 7px">+</button>
            </div>
            <?php if ($overCap): ?>
            <span id="lineCapWarn<?= $li ?>" style="font-size:.62rem;color:#dc2626;font-weight:700;white-space:nowrap">⚠ over capacity</span>
            <?php else: ?>
            <span id="lineCapWarn<?= $li ?>" style="font-size:.62rem;color:#dc2626;font-weight:700;white-space:nowrap;display:none">⚠ over capacity</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <span class="bed-status-badge bed-status-badge--<?= $status ?>"><?= $statusLabels[$status] ?></span>
        <div class="bed-line-btns">
            <?php if ($pid && in_array($status, ['planned','growing'], true)): ?>
            <button class="bed-line-btn" onclick="openHarvestModal(<?= $pid ?>, '<?= e(addslashes($cropName)) ?>')" style="color:#15803d;border-color:#86efac">🌾 Harvest</button>
            <?php endif; ?>
            <?php if ($cropName): ?>
            <button class="bed-line-btn" onclick="toggleCompanions(<?= $li ?>, '<?= e(addslashes($cropName)) ?>')" id="companionBtn<?= $li ?>">☘ Companions</button>
            <?php endif; ?>
            <button class="bed-line-btn" onclick="toggleEdit(<?= $li ?>)">✏ Edit</button>
            <?php if ($pid): ?>
            <button class="bed-line-btn bed-line-btn--danger" onclick="clearLine(<?= $pid ?>, <?= $li ?>)">✕</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Companions panel -->
    <div class="bed-companions-panel" id="companionsPanel<?= $li ?>">
        <div class="bed-companions-loading" id="companionsLoading<?= $li ?>">Loading suggestions…</div>
        <div id="companionsResult<?= $li ?>" style="display:none"></div>
    </div>

    <!-- Edit form -->
    <div class="bed-edit-form" id="editForm<?= $li ?>">

        <!-- Suggestions panel -->
        <div id="suggPanel<?= $li ?>" style="margin-bottom:10px">
            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:5px">
                Suggestions — tap to fill
            </div>
            <div id="suggChips<?= $li ?>" style="display:flex;flex-wrap:wrap;gap:5px"></div>
        </div>

        <div class="bed-edit-grid">
            <div class="bed-edit-grid--full">
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Crop</label>
                <input type="text" class="bed-edit-input" id="editCrop<?= $li ?>" value="<?= e($cropName) ?>" placeholder="Type or choose from suggestions above" list="seedDatalist" autocomplete="off">
                <input type="hidden" id="editSeedId<?= $li ?>" value="<?= (int)($planting['seed_id'] ?? 0) ?>">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Variety</label>
                <input type="text" class="bed-edit-input" id="editVariety<?= $li ?>" value="<?= e($variety) ?>" placeholder="optional">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Plants</label>
                <input type="number" class="bed-edit-input" id="editPlantCount<?= $li ?>" value="<?= e($planting['plant_count'] ?? '') ?>" min="1" placeholder="qty"
                       oninput="updateCapacityWarning(<?= $li ?>, document.getElementById('editSeedId<?= $li ?>').getAttribute('data-spacing')||0, this.value)">
                <div id="capWarn<?= $li ?>" style="display:none;font-size:.7rem;color:#dc2626;margin-top:3px"></div>
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Status</label>
                <select class="bed-edit-input" id="editStatus<?= $li ?>">
                    <?php foreach ($statusLabels as $sv => $sl): ?>
                    <option value="<?= $sv ?>" <?= $status === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Planted</label>
                <input type="date" class="bed-edit-input" id="editPlanted<?= $li ?>" value="<?= e($plantedAt) ?>"
                       oninput="autoHarvestDate(<?= $li ?>)">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Expected harvest</label>
                <input type="date" class="bed-edit-input" id="editHarvest<?= $li ?>" value="<?= e($harvestAt) ?>">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Notes</label>
                <input type="text" class="bed-edit-input" id="editNotes<?= $li ?>" value="<?= e($notes) ?>" placeholder="optional">
            </div>
        </div>
        <div class="bed-edit-actions">
            <button class="btn btn-ghost btn-sm" onclick="toggleEdit(<?= $li ?>)">Cancel</button>
            <button class="btn btn-primary btn-sm" onclick="saveLine(<?= $li ?>)">Save</button>
        </div>
    </div>
</div>
<?php endfor; ?>

<!-- What to prepare next -->
<?php if (!empty($prepNext)): ?>
<div class="bed-prep">
    <div class="bed-prep-title">🌱 What to prepare next</div>
    <?php foreach ($prepNext as $prep):
        $pLine = $prep['line_number'];
        $pCrop = $prep['crop_name'] ?? 'this line';
    ?>
    <div class="bed-prep-item">
        <?php if ($prep['reason'] === 'ready_to_plant'): ?>
        <span class="bed-prep-icon">🪴</span>
        <div>
            <strong>Line <?= $pLine ?></strong> is <?= $prep['status'] === 'harvested' ? 'harvested' : 'empty' ?> — ready to plant.
            <?php if (!empty($prep['notes'])): ?><span style="color:var(--color-text-muted)"> <?= e($prep['notes']) ?></span><?php endif; ?>
            <div style="margin-top:3px"><a href="<?= url('/garden') ?>" style="font-size:.75rem;color:var(--color-primary);text-decoration:none">See <?= e($monthNames[$currentMonth-1]) ?> planting suggestions →</a></div>
        </div>
        <?php else: ?>
        <span class="bed-prep-icon">🍅</span>
        <div>
            <strong><?= e($pCrop) ?></strong> on Line <?= $pLine ?> expected harvest by <strong><?= date('d M Y', strtotime($prep['expected_harvest_at'])) ?></strong>.
            <div style="margin-top:3px;font-size:.78rem;color:var(--color-text-muted)">Start planning succession planting for this line.</div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Planting backlog calendar -->
<?php if (!empty($backlog)): ?>
<div style="margin-top:var(--spacing-4);background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:16px 18px">
    <div style="font-size:.8rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:12px;display:flex;align-items:center;gap:6px">
        📅 Planting Backlog — next 6 months
    </div>
    <?php
    $mnShort = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    foreach ($backlog as $blEntry):
        $bMonth = $blEntry['month'];
        $isNow  = ($bMonth === $currentMonth);
    ?>
    <div style="margin-bottom:12px">
        <div style="font-size:.78rem;font-weight:700;color:<?= $isNow ? 'var(--color-primary)' : 'var(--color-text-muted)' ?>;margin-bottom:5px">
            <?= $isNow ? '▶ ' : '' ?><?= $mnShort[$bMonth - 1] ?>
            <?php if ($isNow): ?><span style="font-size:.65rem;background:var(--color-primary);color:#fff;padding:1px 7px;border-radius:999px;margin-left:4px">THIS MONTH</span><?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px">
        <?php foreach ($blEntry['items'] as $bi): ?>
        <div style="display:flex;align-items:center;gap:8px;padding:5px 8px;background:<?= $bi['already_in_bed'] ? 'var(--color-bg)' : ($isNow ? 'rgba(45,90,39,.06)' : 'var(--color-bg)') ?>;border:1px solid <?= $bi['already_in_bed'] ? 'var(--color-border)' : ($isNow ? 'rgba(45,90,39,.25)' : 'var(--color-border)') ?>;border-radius:6px;font-size:.82rem">
            <span style="flex:1;font-weight:<?= $bi['already_in_bed'] ? '400' : '600' ?>;color:<?= $bi['already_in_bed'] ? 'var(--color-text-muted)' : 'var(--color-text)' ?>">
                <?= e($bi['name']) ?><?= $bi['variety'] ? ' <span style="font-weight:400;color:var(--color-text-muted)">('.e($bi['variety']).')</span>' : '' ?>
            </span>
            <?php if ($bi['needs_restock']): ?>
            <a href="<?= url('/seeds/buy-list') ?>" style="font-size:.65rem;background:#dc3545;color:#fff;padding:1px 7px;border-radius:999px;text-decoration:none;flex-shrink:0">BUY FIRST</a>
            <?php elseif ($bi['already_in_bed']): ?>
            <span style="font-size:.65rem;color:#15803d;font-weight:700;flex-shrink:0">✓ in bed</span>
            <?php else: ?>
            <span style="font-size:.65rem;color:var(--color-text-muted);flex-shrink:0">#{<?= $bi['priority'] ?>}</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div>

<!-- Harvest modal -->
<div id="harvestModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:var(--color-surface);border-radius:var(--radius-lg);padding:var(--spacing-4);width:min(90vw,380px);box-shadow:0 8px 32px rgba(0,0,0,.25)">
        <div style="font-weight:700;font-size:1rem;margin-bottom:var(--spacing-3)">🌾 Harvest — <span id="harvestCropName"></span></div>
        <div class="bed-edit-grid" style="margin-bottom:var(--spacing-3)">
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Quantity</label>
                <input type="number" id="harvestQty" class="bed-edit-input" min="0" step="0.01" placeholder="0">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Unit</label>
                <select id="harvestUnit" class="bed-edit-input">
                    <option value="items">Items (count)</option>
                    <option value="kg">kg</option>
                    <option value="g">grams</option>
                    <option value="packets">Packets</option>
                </select>
            </div>
        </div>
        <div style="margin-bottom:var(--spacing-3)">
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Notes (optional)</label>
            <input type="text" id="harvestNotes" class="bed-edit-input" placeholder="Quality, observations…">
        </div>
        <p style="font-size:.75rem;color:var(--color-text-muted);margin-bottom:var(--spacing-3)">This will mark the line as harvested and free it for the next crop.</p>
        <div class="bed-edit-actions">
            <button class="btn btn-ghost btn-sm" onclick="closeHarvestModal()">Cancel</button>
            <button class="btn btn-primary btn-sm" onclick="submitHarvest()">🌾 Confirm Harvest</button>
        </div>
    </div>
</div>

<!-- Seed datalist for crop input autocomplete -->
<datalist id="seedDatalist">
    <?php foreach ($allSeeds as $s): ?>
    <option value="<?= e($s['name'] . ($s['variety'] ? ' — ' . $s['variety'] : '')) ?>">
    <?php endforeach; ?>
</datalist>

<script>
var BED_CSRF      = '<?= e($csrfToken) ?>';
var BED_ITEM      = <?= (int)$item['id'] ?>;
var BED_BASE      = '<?= url('/') ?>';
var BED_MONTH     = <?= $currentMonth ?>;
var BED_SUGG      = <?= json_encode(array_map(function($fn) {
    $pm = !empty($fn['planting_months']) ? json_decode($fn['planting_months'], true) : [];
    return [
        'seed_id'  => (int)($fn['sid'] ?? 0),
        'name'     => $fn['seed_name'] ?: $fn['vegetable_name'],
        'variety'  => $fn['seed_variety'] ?? '',
        'priority' => (int)$fn['priority'],
        'in_season'=> in_array((int)date('n'), $pm),
        'needs_restock' => !empty($fn['needs_restock']),
        'spacing_cm'=> $fn['spacing_cm'] ?? null,
    ];
}, $familyNeeds)) ?>;
var BED_SEEDS     = <?= json_encode(array_map(fn($s) => [
    'id'              => (int)$s['id'],
    'name'            => $s['name'],
    'variety'         => $s['variety'] ?? '',
    'spacing_cm'      => $s['spacing_cm'] ?? null,
    'row_spacing_cm'  => $s['row_spacing_cm'] ?? null,
    'sowing_depth_mm' => $s['sowing_depth_mm'] ?? null,
    'days_to_maturity'=> $s['days_to_maturity'] ?? null,
    'notes'           => $s['notes'] ?? '',
], $allSeeds)) ?>;
var BED_LINE_M    = <?= ($widthM > 0 && $numLines > 0) ? round($widthM / $numLines * 100) / 100 : 0 ?>;
var BED_LENGTH_M  = <?= $lengthM > 0 ? $lengthM : 0 ?>;
var BED_LINE_CAP  = BED_LENGTH_M > 0 ? BED_LENGTH_M * 100 : 0; // cm available per line
var BED_PLANTED   = <?= json_encode(array_values(array_filter(array_map(fn($p) => $p['crop_name'] ?? '', $plantings)))) ?>;
var BED_LINE_STATUS = <?= json_encode(array_map(fn($i) => [
    'line'   => $i,
    'status' => $plantingMap[$i]['status'] ?? 'empty',
    'crop'   => $plantingMap[$i]['crop_name'] ?? '',
    'pid'    => (int)($plantingMap[$i]['id'] ?? 0),
], range(1, $numLines))) ?>;
var _companionCache = {};
var _companionData  = {}; // keyed by line, stores companion array for quick-add

function calcPlantCount(spacingCm) {
    if (!spacingCm || spacingCm <= 0 || BED_LENGTH_M <= 0) return null;
    return Math.max(1, Math.floor(BED_LENGTH_M * 100 / spacingCm));
}

function renderSuggestions(line) {
    var container = document.getElementById('suggChips' + line);
    if (!container) return;
    container.innerHTML = '';

    // Sort: in-season non-out-of-stock first
    var sorted = BED_SUGG.slice().filter(function(s){ return !s.needs_restock; });
    sorted.sort(function(a, b) {
        if (a.in_season !== b.in_season) return a.in_season ? -1 : 1;
        return a.priority - b.priority;
    });
    sorted = sorted.slice(0, 6);

    if (sorted.length === 0) {
        container.innerHTML = '<span style="font-size:.75rem;color:var(--color-text-muted)">No family needs configured — <a href="<?= url('/seeds/family-needs') ?>">add them here</a>.</span>';
        return;
    }

    sorted.forEach(function (s) {
        var chip = document.createElement('button');
        chip.type = 'button';
        var label = s.name + (s.variety ? ' (' + s.variety + ')' : '');
        chip.textContent = (s.in_season ? '🌱 ' : '') + label;
        chip.style.cssText = 'padding:5px 12px;border:1.5px solid ' + (s.in_season ? 'var(--color-primary)' : 'var(--color-border)') + ';border-radius:999px;background:' + (s.in_season ? 'var(--color-primary-soft)' : 'var(--color-bg)') + ';color:' + (s.in_season ? 'var(--color-primary)' : 'var(--color-text)') + ';font-size:.78rem;font-weight:600;cursor:pointer';
        chip.onclick = function () {
            document.getElementById('editCrop' + line).value    = s.name;
            document.getElementById('editVariety' + line).value = s.variety || '';
            document.getElementById('editSeedId' + line).value  = s.seed_id || 0;
            // Auto-fill plant count from spacing
            if (s.spacing_cm) {
                var cnt = calcPlantCount(s.spacing_cm);
                if (cnt) document.getElementById('editPlantCount' + line).value = cnt;
                updateCapacityWarning(line, s.spacing_cm, cnt || 0);
            }
            // Auto-fill notes from seed data
            var notesEl = document.getElementById('editNotes' + line);
            if (notesEl && !notesEl.value.trim()) {
                var parts = [];
                // Find seed in BED_SEEDS for extra fields
                for (var i = 0; i < BED_SEEDS.length; i++) {
                    if (BED_SEEDS[i].id === s.seed_id) {
                        var sd = BED_SEEDS[i];
                        if (sd.sowing_depth_mm) parts.push(sd.sowing_depth_mm + 'mm deep');
                        if (sd.spacing_cm)      parts.push(sd.spacing_cm + 'cm spacing');
                        if (sd.row_spacing_cm)  parts.push(sd.row_spacing_cm + 'cm rows');
                        if (sd.notes)           parts.push(sd.notes);
                        // Store days_to_maturity for harvest date calc
                        document.getElementById('editSeedId' + line).setAttribute('data-dtm', sd.days_to_maturity || '');
                        break;
                    }
                }
                if (parts.length) notesEl.value = parts.join(' // ');
            }
        };
        container.appendChild(chip);
    });
}

function updateCapacityWarning(line, spacingCm, currentCount) {
    var warnEl = document.getElementById('capWarn' + line);
    if (!warnEl || !spacingCm || BED_LINE_CAP <= 0) return;
    var cap = Math.floor(BED_LINE_CAP / spacingCm);
    if (currentCount > cap) {
        warnEl.textContent = '⚠ Line fits ~' + cap + ' plants at ' + spacingCm + 'cm spacing — reduce count or spacing.';
        warnEl.style.display = '';
    } else {
        warnEl.style.display = 'none';
    }
}

function autoHarvestDate(line) {
    var dtm     = parseInt(document.getElementById('editSeedId' + line).getAttribute('data-dtm') || '0');
    var planted = document.getElementById('editPlanted' + line).value;
    var harvestEl = document.getElementById('editHarvest' + line);
    if (!dtm || !planted || !harvestEl || harvestEl.value) return;
    var d = new Date(planted);
    d.setDate(d.getDate() + dtm);
    harvestEl.value = d.toISOString().slice(0, 10);
}

var _harvestPid = 0;
function openHarvestModal(pid, crop) {
    _harvestPid = pid;
    document.getElementById('harvestCropName').textContent = crop;
    document.getElementById('harvestQty').value   = '';
    document.getElementById('harvestNotes').value = '';
    var modal = document.getElementById('harvestModal');
    modal.style.display = 'flex';
}
function closeHarvestModal() {
    document.getElementById('harvestModal').style.display = 'none';
}
function submitHarvest() {
    var qty   = document.getElementById('harvestQty').value;
    var unit  = document.getElementById('harvestUnit').value;
    var notes = document.getElementById('harvestNotes').value.trim();
    var fd = new FormData();
    fd.append('_token', BED_CSRF);
    fd.append('_ajax', '1');
    fd.append('qty',   qty);
    fd.append('unit',  unit);
    fd.append('notes', notes);
    fetch(BED_BASE + 'garden/plantings/' + _harvestPid + '/harvest-line', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) { closeHarvestModal(); location.reload(); }
        else { alert('Could not save harvest. Please try again.'); }
    }).catch(function(){ alert('Network error.'); });
}

function adjustQty(pid, line, delta) {
    var fd = new FormData();
    fd.append('_token', BED_CSRF);
    fd.append('_ajax', '1');
    fd.append('delta', delta);
    fetch(BED_BASE + 'garden/plantings/' + pid + '/adjust-qty', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.success) return;
        // Update qty display and capacity warning without full reload
        var qtyEl   = document.getElementById('lineQty' + line);
        var warnEl  = document.getElementById('lineCapWarn' + line);
        var rowEl   = document.getElementById('lineRow' + line);
        if (qtyEl) {
            var newQty  = parseInt(qtyEl.textContent || '0') + delta;
            if (newQty < 1) newQty = 1;
            qtyEl.textContent = newQty;
            var cap = rowEl ? parseInt(rowEl.getAttribute('data-capacity') || '0') : 0;
            var over = cap > 0 && newQty > cap;
            if (rowEl) rowEl.classList.toggle('bed-line-row--overcap', over);
            if (warnEl) warnEl.style.display = over ? '' : 'none';
        } else {
            location.reload();
        }
    })
    .catch(function(){});
}

function quickAddCompanion(line, idx) {
    var companion = _companionData[line] && _companionData[line][idx];
    if (!companion) return;
    // Find first empty line
    var emptyLine = null;
    for (var i = 0; i < BED_LINE_STATUS.length; i++) {
        if (BED_LINE_STATUS[i].status === 'empty') { emptyLine = BED_LINE_STATUS[i].line; break; }
    }
    if (!emptyLine) { alert('No empty lines available in this bed.'); return; }
    var btn = document.querySelector('[data-companion-idx="' + line + '-' + idx + '"]');
    if (btn) { btn.disabled = true; btn.textContent = '…'; }
    var fd = new FormData();
    fd.append('_token',     BED_CSRF);
    fd.append('_ajax',      '1');
    fd.append('line',       emptyLine);
    fd.append('crop',       companion.name);
    fd.append('seed_id',    companion.id || '');
    fd.append('plant_count','1');
    fd.append('status',     'planned');
    fetch(BED_BASE + 'items/' + BED_ITEM + '/planting', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) { location.reload(); }
        else { alert(d.error || 'Could not add companion.'); if (btn) { btn.disabled = false; btn.textContent = '+ Add'; } }
    }).catch(function(){ alert('Network error.'); if (btn) { btn.disabled = false; btn.textContent = '+ Add'; } });
}

function toggleEdit(line) {
    var f = document.getElementById('editForm' + line);
    var isOpen = f.classList.contains('open');
    // Close all other edit forms
    document.querySelectorAll('.bed-edit-form.open').forEach(function(el){ el.classList.remove('open'); });
    if (!isOpen) {
        f.classList.add('open');
        renderSuggestions(line);
    }
}

function saveLine(line) {
    var crop       = document.getElementById('editCrop'+line).value.trim();
    var variety    = document.getElementById('editVariety'+line).value.trim();
    var status     = document.getElementById('editStatus'+line).value;
    var planted    = document.getElementById('editPlanted'+line).value;
    var harvest    = document.getElementById('editHarvest'+line).value;
    var notes      = document.getElementById('editNotes'+line).value.trim();
    var seedId     = (document.getElementById('editSeedId'+line) || {}).value || '';
    var plantCount = (document.getElementById('editPlantCount'+line) || {}).value || '';

    // If crop was typed manually (not from chip), try to resolve seed_id from catalog
    if (!seedId || seedId === '0') {
        var lc = crop.toLowerCase();
        for (var i = 0; i < BED_SEEDS.length; i++) {
            if (BED_SEEDS[i].name.toLowerCase() === lc) { seedId = BED_SEEDS[i].id; break; }
        }
    }

    var fd = new FormData();
    fd.append('_token', BED_CSRF);
    fd.append('_ajax', '1');
    fd.append('line_number', line);
    fd.append('crop_name', crop);
    fd.append('variety', variety);
    fd.append('status', status);
    fd.append('planted_at', planted);
    fd.append('expected_harvest_at', harvest);
    fd.append('notes', notes);
    if (seedId) fd.append('seed_id', seedId);
    if (plantCount) fd.append('plant_count', plantCount);

    fetch(BED_BASE + 'items/' + BED_ITEM + '/planting', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) { location.reload(); }
        else { alert('Save failed. Please try again.'); }
    }).catch(function(){ alert('Network error. Please try again.'); });
}

function clearLine(pid, line) {
    if (!confirm('Clear this line?')) return;
    var fd = new FormData();
    fd.append('_token', BED_CSRF);
    fd.append('_ajax', '1');
    fetch(BED_BASE + 'garden/plantings/' + pid + '/trash', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) { location.reload(); }
    }).catch(function(){ alert('Network error. Please try again.'); });
}

function toggleCompanions(line, crop) {
    var panel = document.getElementById('companionsPanel' + line);
    var isOpen = panel.classList.contains('open');
    // Close all other panels
    document.querySelectorAll('.bed-companions-panel.open').forEach(function(el){ el.classList.remove('open'); });
    if (isOpen) return;
    panel.classList.add('open');
    if (_companionCache[line]) {
        renderCompanions(line, _companionCache[line]);
        return;
    }
    document.getElementById('companionsLoading'+line).style.display = 'block';
    document.getElementById('companionsResult'+line).style.display = 'none';
    var otherCrops = BED_PLANTED.filter(function(c){ return c.toLowerCase() !== crop.toLowerCase(); });
    fetch(BED_BASE + 'api/garden/companions?crop=' + encodeURIComponent(crop) + '&bed_crops=' + encodeURIComponent(otherCrops.join(',')))
    .then(function(r){ return r.json(); })
    .then(function(d){
        document.getElementById('companionsLoading'+line).style.display = 'none';
        if (!d.success) {
            document.getElementById('companionsResult'+line).innerHTML = '<span style="color:#dc2626;font-size:.82rem">' + (d.error||'Could not load suggestions') + '</span>';
            document.getElementById('companionsResult'+line).style.display = 'block';
            return;
        }
        _companionCache[line] = d.data;
        renderCompanions(line, d.data);
    }).catch(function(){
        document.getElementById('companionsLoading'+line).style.display = 'none';
        document.getElementById('companionsResult'+line).innerHTML = '<span style="color:#dc2626;font-size:.82rem">Network error</span>';
        document.getElementById('companionsResult'+line).style.display = 'block';
    });
}

function renderCompanions(line, data) {
    _companionData[line] = data.companions || [];
    var html = '';
    if (data.companions && data.companions.length) {
        html += '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#15803d;margin-bottom:6px">Good companions</div>';
        html += '<ul class="bed-companion-list">';
        data.companions.forEach(function(c, i){
            var hasWarn = c.reason && c.reason.indexOf('⚠') !== -1;
            html += '<li class="bed-companion-item' + (hasWarn ? ' bed-antagonist-item' : '') + '">'
                  + (hasWarn ? '⚠️' : '✅') + ' <strong>' + esc(c.name) + '</strong>'
                  + ' <span>— ' + esc(c.reason) + '</span>'
                  + ' <button class="companion-add-btn" data-companion-idx="' + line + '-' + i + '"'
                  + ' onclick="quickAddCompanion(' + line + ',' + i + ')">+ Add</button>'
                  + '</li>';
        });
        html += '</ul>';
    } else {
        html += '<p style="font-size:.82rem;color:var(--color-text-muted)">No companion matches found — add companion data to your seeds.</p>';
    }
    if (data.antagonists && data.antagonists.length) {
        html += '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#b91c1c;margin-bottom:4px;margin-top:8px">⚠ Conflicts in this bed</div>';
        html += '<ul class="bed-companion-list">';
        data.antagonists.forEach(function(c){
            html += '<li class="bed-companion-item bed-antagonist-item">🚫 <strong>' + esc(c.name) + '</strong> <span>— ' + esc(c.reason) + '</span></li>';
        });
        html += '</ul>';
    }
    var el = document.getElementById('companionsResult'+line);
    el.innerHTML = html;
    el.style.display = 'block';
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function toggleBedConfig() {
    var f = document.getElementById('bedConfigForm');
    f.classList.toggle('open');
    if (f.classList.contains('open')) updateSpacing();
}

function updateSpacing() {
    var rows  = parseInt(document.getElementById('cfgRows').value) || 0;
    var dir   = document.getElementById('cfgDir').value;
    var w     = parseFloat(document.getElementById('cfgWidth').value) || 0;
    var l     = parseFloat(document.getElementById('cfgLength').value) || 0;
    var dim   = dir === 'NS' ? w : l;
    var el    = document.getElementById('cfgSpacing');
    if (rows > 0 && dim > 0) {
        el.textContent = '→ ' + (Math.round(dim / rows * 100) / 100) + 'm spacing per line';
    } else {
        el.textContent = '';
    }
}

function saveBedConfig() {
    var fd = new FormData();
    fd.append('_token',         BED_CSRF);
    fd.append('_ajax',          '1');
    fd.append('bed_rows',       document.getElementById('cfgRows').value);
    fd.append('line_direction', document.getElementById('cfgDir').value);
    fd.append('bed_width_m',    document.getElementById('cfgWidth').value);
    fd.append('bed_length_m',   document.getElementById('cfgLength').value);
    fetch(BED_BASE + 'items/' + BED_ITEM + '/bed-config', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.success) location.reload(); else alert('Save failed.'); })
    .catch(function(){ alert('Network error.'); });
}
</script>
