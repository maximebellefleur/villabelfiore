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

<?php
// Seed spacing lookup (needed for SVG fill proportions too)
$seedSpacingMap = [];
foreach ($allSeeds as $_s) { $seedSpacingMap[(int)$_s['id']] = (int)($_s['spacing_cm'] ?? 0); }
// Total available cm per line
$capCm = $lengthM > 0 ? (int)round($lengthM * 100) : 0;
?>
<!-- Schematic -->
<div class="bed-schematic">
    <svg width="<?= $svgW ?>" height="<?= $svgH ?>" viewBox="0 0 <?= $svgW ?> <?= $svgH ?>" xmlns="http://www.w3.org/2000/svg">
        <rect x="0" y="0" width="<?= $svgW ?>" height="<?= $svgH ?>" fill="#f1f5f9" rx="4"/>
        <?php if ($lineDir === 'EW'): ?>
            <?php $stripeW = $svgW / $numLines; ?>
            <?php for ($li = 0; $li < $numLines; $li++):
                $lNum = $li + 1;
                $linePls  = $plantingMap[$lNum] ?? [];
                $usedCm   = 0;
                foreach ($linePls as $_p) {
                    $sp = $seedSpacingMap[(int)($_p['seed_id'] ?? 0)] ?? 0;
                    $cnt = (int)($_p['plant_count'] ?? 0);
                    if ($sp > 0 && $cnt > 0) $usedCm += $sp * $cnt;
                }
                $status   = !empty($linePls) ? ($linePls[0]['status'] ?? 'empty') : 'empty';
                $color    = $statusColors[$status];
                $fillR    = ($capCm > 0 && $usedCm > 0) ? min(1.0, $usedCm / $capCm) : (!empty($linePls) ? 0.85 : 0.0);
                $x = round($li * $stripeW);
                $w = round($stripeW);
                $cropLabel = !empty($linePls) ? ($linePls[0]['crop_name'] ?? '') : '';
            ?>
            <rect x="<?= $x ?>" y="0" width="<?= $w ?>" height="<?= $svgH ?>" fill="#e2e8f0" fill-opacity="0.5"/>
            <rect x="<?= $x ?>" y="0" width="<?= round($w * $fillR) ?>" height="<?= $svgH ?>" fill="<?= $color ?>" fill-opacity="0.85"/>
            <?php if ($li > 0): ?><line x1="<?= $x ?>" y1="0" x2="<?= $x ?>" y2="<?= $svgH ?>" stroke="#fff" stroke-width="1.5"/><?php endif; ?>
            <?php if ($usedCm > $capCm && $capCm > 0): ?>
            <text x="<?= $x + $w - 3 ?>" y="10" text-anchor="end" font-size="9" fill="#dc2626">⚠</text>
            <?php endif; ?>
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
                $linePls  = $plantingMap[$lNum] ?? [];
                $usedCm   = 0;
                foreach ($linePls as $_p) {
                    $sp = $seedSpacingMap[(int)($_p['seed_id'] ?? 0)] ?? 0;
                    $cnt = (int)($_p['plant_count'] ?? 0);
                    if ($sp > 0 && $cnt > 0) $usedCm += $sp * $cnt;
                }
                $status   = !empty($linePls) ? ($linePls[0]['status'] ?? 'empty') : 'empty';
                $color    = $statusColors[$status];
                $fillR    = ($capCm > 0 && $usedCm > 0) ? min(1.0, $usedCm / $capCm) : (!empty($linePls) ? 0.85 : 0.0);
                $y = round($li * $stripeH);
                $h = round($stripeH);
                $cropLabel = !empty($linePls) ? ($linePls[0]['crop_name'] ?? '') : '';
            ?>
            <rect x="0" y="<?= $y ?>" width="<?= $svgW ?>" height="<?= $h ?>" fill="#e2e8f0" fill-opacity="0.5"/>
            <rect x="0" y="<?= $y ?>" width="<?= round($svgW * $fillR) ?>" height="<?= $h ?>" fill="<?= $color ?>" fill-opacity="0.85"/>
            <?php if ($li > 0): ?><line x1="0" y1="<?= $y ?>" x2="<?= $svgW ?>" y2="<?= $y ?>" stroke="#fff" stroke-width="1.5"/><?php endif; ?>
            <?php if ($usedCm > $capCm && $capCm > 0): ?>
            <text x="<?= $svgW - 3 ?>" y="<?= $y + 10 ?>" text-anchor="end" font-size="9" fill="#dc2626">⚠</text>
            <?php endif; ?>
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

<?php for ($li = 1; $li <= $numLines; $li++):
    $linePlantings = $plantingMap[$li] ?? [];
    // Line-level capacity: sum of all plantings' used cm
    $totalUsedCm = 0;
    foreach ($linePlantings as $_p) {
        $sp = $seedSpacingMap[(int)($_p['seed_id'] ?? 0)] ?? 0;
        $cnt = (int)($_p['plant_count'] ?? 0);
        if ($sp > 0 && $cnt > 0) $totalUsedCm += $sp * $cnt;
    }
    $overCap = $capCm > 0 && $totalUsedCm > $capCm;
    $fillPct = $capCm > 0 ? min(100, round($totalUsedCm / $capCm * 100)) : 0;
?>
<div class="bed-line-row<?= $overCap ? ' bed-line-row--overcap' : '' ?>" id="lineRow<?= $li ?>">
    <!-- Line header: number + capacity bar + Add button -->
    <div class="bed-line-main" style="gap:8px">
        <div class="bed-line-num"><?= $li ?></div>
        <?php if ($capCm > 0 && !empty($linePlantings)): ?>
        <div style="flex:1;min-width:60px">
            <div style="height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden">
                <div id="lineFill<?= $li ?>" style="height:5px;width:<?= $fillPct ?>%;background:<?= $overCap ? '#ef4444' : '#22c55e' ?>;border-radius:3px;transition:width .2s"></div>
            </div>
            <span id="lineFillLabel<?= $li ?>" style="font-size:.6rem;color:<?= $overCap ? '#dc2626' : 'var(--color-text-muted)' ?>">
                <?= $totalUsedCm ?>cm / <?= $capCm ?>cm<?= $overCap ? ' ⚠' : '' ?>
            </span>
        </div>
        <?php else: ?>
        <div style="flex:1"></div>
        <?php endif; ?>
        <button class="bed-line-btn" onclick="toggleAddForm(<?= $li ?>)" style="font-size:.68rem;padding:3px 9px">＋ Add</button>
    </div>

    <!-- Plantings on this line -->
    <?php if (empty($linePlantings)): ?>
    <div style="padding:4px 14px 10px 52px;font-size:.82rem;color:var(--color-text-muted)">Empty — use ＋ Add to plant something</div>
    <?php else: foreach ($linePlantings as $_pidx => $planting):
        $pid      = (int)$planting['id'];
        $pStatus  = $planting['status'] ?? 'empty';
        $pCrop    = $planting['crop_name'] ?? '';
        $pVariety = $planting['variety'] ?? '';
        $pAt      = $planting['planted_at'] ?? '';
        $pHarAt   = $planting['expected_harvest_at'] ?? '';
        $pNotes   = $planting['notes'] ?? '';
        $pCount   = (int)($planting['plant_count'] ?? 0);
        $pSpacing = $seedSpacingMap[(int)($planting['seed_id'] ?? 0)] ?? 0;
        $pSub = [];
        if ($pAt) $pSub[] = 'Planted ' . date('d M Y', strtotime($pAt));
        if ($pHarAt) $pSub[] = 'Harvest ' . date('d M Y', strtotime($pHarAt));
        if ($pVariety) $pSub[] = $pVariety;
    ?>
    <div id="plantingRow<?= $pid ?>" data-line="<?= $li ?>" data-spacing="<?= $pSpacing ?>"
         style="border-top:1px solid #f0f4f8">
        <div class="bed-line-main" style="padding:8px 14px 8px 14px">
            <div style="width:8px;height:8px;border-radius:50%;background:<?= $statusColors[$pStatus] ?? '#e2e8f0' ?>;flex-shrink:0;margin-left:18px"></div>
            <div class="bed-line-crop">
                <div class="bed-line-name"><?= $pCrop ? e($pCrop) : '<span style="color:var(--color-text-muted);font-weight:400">Empty</span>' ?></div>
                <?php if ($pSub): ?><div class="bed-line-sub"><?= e(implode(' · ', $pSub)) ?></div><?php endif; ?>
            </div>
            <?php if ($pid && $pCount > 0): ?>
            <div style="display:flex;align-items:center;gap:2px;flex-shrink:0">
                <button class="bed-line-btn" onclick="adjustQty(<?= $pid ?>,<?= $li ?>,-1)" style="padding:3px 7px">−</button>
                <span id="lineQty<?= $pid ?>" data-spacing="<?= $pSpacing ?>" style="font-size:.78rem;font-weight:700;min-width:28px;text-align:center"><?= $pCount ?></span>
                <button class="bed-line-btn" onclick="adjustQty(<?= $pid ?>,<?= $li ?>,1)" style="padding:3px 7px">+</button>
            </div>
            <?php endif; ?>
            <span class="bed-status-badge bed-status-badge--<?= $pStatus ?>"><?= $statusLabels[$pStatus] ?></span>
            <div class="bed-line-btns">
                <?php if ($pid && in_array($pStatus, ['planned','growing'], true)): ?>
                <button class="bed-line-btn" onclick="openHarvestModal(<?= $pid ?>, '<?= e(addslashes($pCrop)) ?>')" style="color:#15803d;border-color:#86efac">🌾 Harvest</button>
                <?php endif; ?>
                <?php if ($pCrop): ?>
                <button class="bed-line-btn" onclick="toggleCompanions(<?= $pid ?>,<?= $li ?>,'<?= e(addslashes($pCrop)) ?>')" id="companionBtn<?= $pid ?>">☘ Companions</button>
                <?php endif; ?>
                <button class="bed-line-btn" onclick="toggleEditP(<?= $pid ?>)">✏ Edit</button>
                <?php if ($pid): ?>
                <button class="bed-line-btn bed-line-btn--danger" onclick="clearLine(<?= $pid ?>)">✕</button>
                <?php endif; ?>
            </div>
        </div>
        <!-- Companions panel per planting -->
        <div class="bed-companions-panel" id="companionsPanel<?= $pid ?>">
            <div class="bed-companions-loading" id="companionsLoading<?= $pid ?>">Loading…</div>
            <div id="companionsResult<?= $pid ?>" style="display:none"></div>
        </div>
        <!-- Edit form per planting (uses planting_id → UPDATE) -->
        <div class="bed-edit-form" id="editFormP<?= $pid ?>">
            <div id="suggPanelP<?= $pid ?>" style="margin-bottom:10px">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:5px">Suggestions — tap to fill</div>
                <div id="suggChipsP<?= $pid ?>" style="display:flex;flex-wrap:wrap;gap:5px"></div>
            </div>
            <div class="bed-edit-grid">
                <div class="bed-edit-grid--full">
                    <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Crop</label>
                    <input type="text" class="bed-edit-input" id="editCropP<?= $pid ?>" value="<?= e($pCrop) ?>" list="seedDatalist" autocomplete="off" placeholder="Crop name">
                    <input type="hidden" id="editSeedIdP<?= $pid ?>" value="<?= (int)($planting['seed_id'] ?? 0) ?>">
                </div>
                <div>
                    <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Variety</label>
                    <input type="text" class="bed-edit-input" id="editVarietyP<?= $pid ?>" value="<?= e($pVariety) ?>" placeholder="optional">
                </div>
                <div>
                    <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Plants</label>
                    <input type="number" class="bed-edit-input" id="editPlantCountP<?= $pid ?>" value="<?= $pCount ?: '' ?>" min="1" placeholder="qty">
                </div>
                <div>
                    <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Status</label>
                    <select class="bed-edit-input" id="editStatusP<?= $pid ?>">
                        <?php foreach ($statusLabels as $sv => $sl): ?>
                        <option value="<?= $sv ?>" <?= $pStatus === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Planted</label>
                    <input type="date" class="bed-edit-input" id="editPlantedP<?= $pid ?>" value="<?= e($pAt) ?>">
                </div>
                <div>
                    <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Expected harvest</label>
                    <input type="date" class="bed-edit-input" id="editHarvestP<?= $pid ?>" value="<?= e($pHarAt) ?>">
                </div>
                <div class="bed-edit-grid--full">
                    <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Notes</label>
                    <input type="text" class="bed-edit-input" id="editNotesP<?= $pid ?>" value="<?= e($pNotes) ?>" placeholder="optional">
                </div>
            </div>
            <div class="bed-edit-actions">
                <button class="btn btn-ghost btn-sm" onclick="toggleEditP(<?= $pid ?>)">Cancel</button>
                <button class="btn btn-primary btn-sm" onclick="savePlanting(<?= $pid ?>,<?= $li ?>)">Save</button>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>

    <!-- Add new planting to this line (no planting_id → INSERT) -->
    <div class="bed-edit-form" id="addForm<?= $li ?>" style="border-top:2px solid var(--color-primary)">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--color-primary);margin-bottom:8px">Add to line <?= $li ?></div>
        <div id="suggPanelN<?= $li ?>" style="margin-bottom:10px">
            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:5px">Suggestions — tap to fill</div>
            <div id="suggChipsN<?= $li ?>" style="display:flex;flex-wrap:wrap;gap:5px"></div>
        </div>
        <div class="bed-edit-grid">
            <div class="bed-edit-grid--full">
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Crop</label>
                <input type="text" class="bed-edit-input" id="editCropN<?= $li ?>" value="" list="seedDatalist" autocomplete="off" placeholder="Type or choose from suggestions">
                <input type="hidden" id="editSeedIdN<?= $li ?>" value="0">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Variety</label>
                <input type="text" class="bed-edit-input" id="editVarietyN<?= $li ?>" value="" placeholder="optional">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Plants</label>
                <input type="number" class="bed-edit-input" id="editPlantCountN<?= $li ?>" value="" min="1" placeholder="qty">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Status</label>
                <select class="bed-edit-input" id="editStatusN<?= $li ?>">
                    <?php foreach ($statusLabels as $sv => $sl): ?>
                    <option value="<?= $sv ?>"><?= $sl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Planted</label>
                <input type="date" class="bed-edit-input" id="editPlantedN<?= $li ?>" value="">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Expected harvest</label>
                <input type="date" class="bed-edit-input" id="editHarvestN<?= $li ?>" value="">
            </div>
            <div class="bed-edit-grid--full">
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Notes</label>
                <input type="text" class="bed-edit-input" id="editNotesN<?= $li ?>" value="" placeholder="optional">
            </div>
        </div>
        <div class="bed-edit-actions">
            <button class="btn btn-ghost btn-sm" onclick="toggleAddForm(<?= $li ?>)">Cancel</button>
            <button class="btn btn-primary btn-sm" onclick="saveNewPlanting(<?= $li ?>)">Save</button>
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
    'status' => !empty($plantingMap[$i]) ? ($plantingMap[$i][0]['status'] ?? 'empty') : 'empty',
    'crop'   => !empty($plantingMap[$i]) ? ($plantingMap[$i][0]['crop_name'] ?? '') : '',
    'pid'    => !empty($plantingMap[$i]) ? (int)($plantingMap[$i][0]['id'] ?? 0) : 0,
], range(1, $numLines))) ?>;
var BED_CAP_CM      = <?= $capCm ?>;
var _companionCache = {};
var _companionData  = {}; // keyed by pid, stores companion array for quick-add

function calcPlantCount(spacingCm) {
    if (!spacingCm || spacingCm <= 0 || BED_LENGTH_M <= 0) return null;
    return Math.max(1, Math.floor(BED_LENGTH_M * 100 / spacingCm));
}

// Generic suggestion renderer: fills inputs identified by cropId, varId, seedId, cntId using suggestion chips
function renderSugsFor(cropId, varId, seedId, cntId, notesId, chipsId) {
    var container = document.getElementById(chipsId);
    if (!container) return;
    container.innerHTML = '';
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
    sorted.forEach(function(s) {
        var chip = document.createElement('button');
        chip.type = 'button';
        chip.textContent = (s.in_season ? '🌱 ' : '') + s.name + (s.variety ? ' (' + s.variety + ')' : '');
        chip.style.cssText = 'padding:5px 12px;border:1.5px solid ' + (s.in_season ? 'var(--color-primary)' : 'var(--color-border)') + ';border-radius:999px;background:' + (s.in_season ? 'var(--color-primary-soft)' : 'var(--color-bg)') + ';color:' + (s.in_season ? 'var(--color-primary)' : 'var(--color-text)') + ';font-size:.78rem;font-weight:600;cursor:pointer';
        chip.onclick = function() {
            if (document.getElementById(cropId)) document.getElementById(cropId).value = s.name;
            if (document.getElementById(varId))  document.getElementById(varId).value  = s.variety || '';
            if (document.getElementById(seedId)) document.getElementById(seedId).value = s.seed_id || 0;
            if (s.spacing_cm && document.getElementById(cntId)) {
                var cnt = calcPlantCount(s.spacing_cm);
                if (cnt) document.getElementById(cntId).value = cnt;
            }
            var notesEl = document.getElementById(notesId);
            if (notesEl && !notesEl.value.trim()) {
                for (var i = 0; i < BED_SEEDS.length; i++) {
                    if (BED_SEEDS[i].id === s.seed_id) {
                        var sd = BED_SEEDS[i], parts = [];
                        if (sd.sowing_depth_mm) parts.push(sd.sowing_depth_mm + 'mm deep');
                        if (sd.spacing_cm)      parts.push(sd.spacing_cm + 'cm spacing');
                        if (sd.row_spacing_cm)  parts.push(sd.row_spacing_cm + 'cm rows');
                        if (sd.notes)           parts.push(sd.notes);
                        if (parts.length) notesEl.value = parts.join(' // ');
                        if (document.getElementById(seedId)) document.getElementById(seedId).setAttribute('data-dtm', sd.days_to_maturity || '');
                        break;
                    }
                }
            }
        };
        container.appendChild(chip);
    });
}

// Refresh the line fill bar after qty changes
function updateLineFill(li) {
    var rows = document.querySelectorAll('[data-line="' + li + '"]');
    var used = 0;
    rows.forEach(function(row) {
        var sp  = parseInt(row.getAttribute('data-spacing') || '0');
        var qEl = row.querySelector('[data-qty]');
        var qty = qEl ? parseInt(qEl.textContent || '0') : 0;
        if (sp > 0 && qty > 0) used += sp * qty;
    });
    var fillEl  = document.getElementById('lineFill' + li);
    var labelEl = document.getElementById('lineFillLabel' + li);
    var rowEl   = document.getElementById('lineRow' + li);
    var over    = BED_CAP_CM > 0 && used > BED_CAP_CM;
    var pct     = BED_CAP_CM > 0 ? Math.min(100, Math.round(used / BED_CAP_CM * 100)) : 0;
    if (fillEl)  { fillEl.style.width = pct + '%'; fillEl.style.background = over ? '#ef4444' : '#22c55e'; }
    if (labelEl) { labelEl.textContent = used + 'cm / ' + BED_CAP_CM + 'cm' + (over ? ' ⚠' : ''); labelEl.style.color = over ? '#dc2626' : ''; }
    if (rowEl)   rowEl.classList.toggle('bed-line-row--overcap', over);
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
        var qtyEl = document.getElementById('lineQty' + pid);
        if (qtyEl) {
            var newQty = Math.max(1, parseInt(qtyEl.textContent || '0') + delta);
            qtyEl.textContent = newQty;
            updateLineFill(line);
        }
    })
    .catch(function(){});
}

// Add companion/seed to the SAME line (no empty-line search)
function quickAddCompanion(line, companion) {
    if (typeof companion === 'number') companion = _companionData[line] && _companionData[line][companion];
    if (!companion) return;
    var fd = new FormData();
    fd.append('_token',      BED_CSRF);
    fd.append('_ajax',       '1');
    fd.append('line_number', line);
    fd.append('crop_name',   companion.name);
    fd.append('variety',     companion.variety || '');
    fd.append('seed_id',     companion.id || '');
    fd.append('plant_count', '1');
    fd.append('status',      'planned');
    fetch(BED_BASE + 'items/' + BED_ITEM + '/planting', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) location.reload();
        else alert(d.error || 'Could not add companion.');
    }).catch(function(){ alert('Network error.'); });
}

// Toggle edit form for an existing planting (keyed by pid)
function toggleEditP(pid) {
    var f = document.getElementById('editFormP' + pid);
    if (!f) return;
    var isOpen = f.classList.contains('open');
    document.querySelectorAll('.bed-edit-form.open').forEach(function(el){ el.classList.remove('open'); });
    if (!isOpen) {
        f.classList.add('open');
        renderSugsFor('editCropP'+pid,'editVarietyP'+pid,'editSeedIdP'+pid,'editPlantCountP'+pid,'editNotesP'+pid,'suggChipsP'+pid);
    }
}

// Toggle add-new-planting form for a line
function toggleAddForm(li) {
    var f = document.getElementById('addForm' + li);
    if (!f) return;
    var isOpen = f.classList.contains('open');
    document.querySelectorAll('.bed-edit-form.open').forEach(function(el){ el.classList.remove('open'); });
    if (!isOpen) {
        f.classList.add('open');
        renderSugsFor('editCropN'+li,'editVarietyN'+li,'editSeedIdN'+li,'editPlantCountN'+li,'editNotesN'+li,'suggChipsN'+li);
    }
}

// Save existing planting (sends planting_id → UPDATE)
function savePlanting(pid, li) {
    var g = function(id){ return (document.getElementById(id) || {}).value || ''; };
    var crop = g('editCropP'+pid).trim(), seedId = g('editSeedIdP'+pid);
    if (!seedId || seedId === '0') {
        var lc = crop.toLowerCase();
        for (var i = 0; i < BED_SEEDS.length; i++) { if (BED_SEEDS[i].name.toLowerCase() === lc) { seedId = BED_SEEDS[i].id; break; } }
    }
    var fd = new FormData();
    fd.append('_token',''); fd.append('_token', BED_CSRF);
    fd.append('_ajax','1');
    fd.append('planting_id', pid);
    fd.append('line_number', li);
    fd.append('crop_name',   crop);
    fd.append('variety',     g('editVarietyP'+pid).trim());
    fd.append('status',      g('editStatusP'+pid));
    fd.append('planted_at',  g('editPlantedP'+pid));
    fd.append('expected_harvest_at', g('editHarvestP'+pid));
    fd.append('notes',       g('editNotesP'+pid).trim());
    if (seedId) fd.append('seed_id', seedId);
    var cnt = g('editPlantCountP'+pid); if (cnt) fd.append('plant_count', cnt);
    fetch(BED_BASE + 'items/' + BED_ITEM + '/planting', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.success) location.reload(); else alert('Save failed.'); })
    .catch(function(){ alert('Network error.'); });
}

// Save new planting on a line (no planting_id → INSERT)
function saveNewPlanting(li) {
    var g = function(id){ return (document.getElementById(id) || {}).value || ''; };
    var crop = g('editCropN'+li).trim(), seedId = g('editSeedIdN'+li);
    if (!seedId || seedId === '0') {
        var lc = crop.toLowerCase();
        for (var i = 0; i < BED_SEEDS.length; i++) { if (BED_SEEDS[i].name.toLowerCase() === lc) { seedId = BED_SEEDS[i].id; break; } }
    }
    var fd = new FormData();
    fd.append('_token', BED_CSRF);
    fd.append('_ajax','1');
    fd.append('line_number', li);
    fd.append('crop_name',   crop);
    fd.append('variety',     g('editVarietyN'+li).trim());
    fd.append('status',      g('editStatusN'+li));
    fd.append('planted_at',  g('editPlantedN'+li));
    fd.append('expected_harvest_at', g('editHarvestN'+li));
    fd.append('notes',       g('editNotesN'+li).trim());
    if (seedId) fd.append('seed_id', seedId);
    var cnt = g('editPlantCountN'+li); if (cnt) fd.append('plant_count', cnt);
    fetch(BED_BASE + 'items/' + BED_ITEM + '/planting', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.success) location.reload(); else alert('Save failed.'); })
    .catch(function(){ alert('Network error.'); });
}

function clearLine(pid) {
    if (!confirm('Remove this planting?')) return;
    var fd = new FormData();
    fd.append('_token', BED_CSRF);
    fd.append('_ajax', '1');
    fetch(BED_BASE + 'garden/plantings/' + pid + '/trash', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.success) location.reload(); })
    .catch(function(){ alert('Network error.'); });
}

// Toggle companions panel — keyed by planting pid, but uses line for bed_crops context
function toggleCompanions(pid, line, crop) {
    var panel = document.getElementById('companionsPanel' + pid);
    var isOpen = panel.classList.contains('open');
    document.querySelectorAll('.bed-companions-panel.open').forEach(function(el){ el.classList.remove('open'); });
    if (isOpen) return;
    panel.classList.add('open');
    if (_companionCache[pid]) { renderCompanions(pid, line, _companionCache[pid]); return; }
    document.getElementById('companionsLoading'+pid).style.display = 'block';
    document.getElementById('companionsResult'+pid).style.display = 'none';
    var otherCrops = BED_PLANTED.filter(function(c){ return c.toLowerCase() !== crop.toLowerCase(); });
    var plantingRow = document.getElementById('plantingRow' + pid);
    var seedId = plantingRow ? (plantingRow.getAttribute('data-seed-id') || '0') : '0';
    fetch(BED_BASE + 'api/garden/companions?crop=' + encodeURIComponent(crop) + '&bed_crops=' + encodeURIComponent(otherCrops.join(',')) + '&seed_id=' + encodeURIComponent(seedId))
    .then(function(r){ return r.json(); })
    .then(function(d){
        document.getElementById('companionsLoading'+pid).style.display = 'none';
        if (!d.success) {
            document.getElementById('companionsResult'+pid).innerHTML = '<span style="color:#dc2626;font-size:.82rem">' + (d.error||'Could not load suggestions') + '</span>';
            document.getElementById('companionsResult'+pid).style.display = 'block';
            return;
        }
        _companionCache[pid] = d.data;
        renderCompanions(pid, line, d.data);
    }).catch(function(){
        document.getElementById('companionsLoading'+pid).style.display = 'none';
        document.getElementById('companionsResult'+pid).innerHTML = '<span style="color:#dc2626;font-size:.82rem">Network error</span>';
        document.getElementById('companionsResult'+pid).style.display = 'block';
    });
}

function renderCompanions(pid, line, data) {
    _companionData[pid] = data.companions || [];
    var html = '';

    // Similar varieties section
    if (data.similar && data.similar.length) {
        html += '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#7c3aed;margin-bottom:5px">🔄 Other varieties</div>';
        html += '<div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px">';
        data.similar.forEach(function(s){
            var label = s.name + (s.variety ? ' — ' + s.variety : '');
            html += '<button class="companion-add-btn" style="background:#7c3aed;border-radius:999px;padding:3px 11px;font-size:.72rem"'
                  + ' onclick="quickAddCompanion(' + line + ',' + JSON.stringify(s).replace(/"/g,'&quot;') + ')">' + esc(label) + '</button>';
        });
        html += '</div>';
    }

    if (data.companions && data.companions.length) {
        html += '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#15803d;margin-bottom:6px">Good companions</div>';
        html += '<ul class="bed-companion-list">';
        data.companions.forEach(function(c){
            var hasWarn = c.reason && c.reason.indexOf('⚠') !== -1;
            var displayName = esc(c.name) + (c.variety ? ' <span style="font-weight:400;color:var(--color-text-muted)">— ' + esc(c.variety) + '</span>' : '');
            var seedObj = (c.variants && c.variants.length) ? c.variants[0] : {id: c.id, name: c.name, variety: c.variety || ''};
            html += '<li class="bed-companion-item' + (hasWarn ? ' bed-antagonist-item' : '') + '">'
                  + (hasWarn ? '⚠️' : '✅') + ' <strong>' + displayName + '</strong>'
                  + ' <span>— ' + esc(c.reason) + '</span>'
                  + ' <button class="companion-add-btn" onclick="quickAddCompanion(' + line + ',' + JSON.stringify(seedObj).replace(/"/g,'&quot;') + ')">+ Add</button>'
                  + '</li>';
        });
        html += '</ul>';
    } else {
        html += '<p style="font-size:.82rem;color:var(--color-text-muted)">No companion matches found — add companion data to your seeds.</p>';
    }

    // Full seed dropdown
    if (data.all_seeds && data.all_seeds.length) {
        html += '<div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--color-border)">';
        html += '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--color-text-muted);margin-bottom:5px">Add any seed to this line</div>';
        html += '<div style="display:flex;gap:6px;align-items:center">';
        html += '<select id="companionSelect' + pid + '" style="flex:1;padding:5px 8px;border:1.5px solid var(--color-border);border-radius:var(--radius);font-size:.82rem;font-family:inherit;background:var(--color-surface)">';
        html += '<option value="">— choose a seed —</option>';
        var grouped = {};
        data.all_seeds.forEach(function(s){
            if (!grouped[s.name]) grouped[s.name] = [];
            grouped[s.name].push(s);
        });
        var antagonistNames = data.antagonist_names || [];
        Object.keys(grouped).forEach(function(name){
            var variants = grouped[name];
            var isAntagonist = antagonistNames.indexOf(name.toLowerCase()) !== -1;
            if (variants.length > 1) {
                html += '<optgroup label="' + esc(name) + (isAntagonist ? ' ⚠' : '') + '">';
                variants.forEach(function(s){
                    var disabled = isAntagonist ? ' disabled' : '';
                    var label = s.variety ? s.variety : s.name;
                    html += '<option value="' + s.id + '" data-name="' + esc(s.name) + '" data-variety="' + esc(s.variety) + '"' + disabled + '>' + esc(label) + '</option>';
                });
                html += '</optgroup>';
            } else {
                var s = variants[0];
                var disabled = isAntagonist ? ' disabled' : '';
                var label = s.name + (s.variety ? ' — ' + s.variety : '');
                html += '<option value="' + s.id + '" data-name="' + esc(s.name) + '" data-variety="' + esc(s.variety) + '"' + disabled + '>' + esc(label) + '</option>';
            }
        });
        html += '</select>';
        html += '<button id="companionAddSelBtn' + pid + '" class="companion-add-btn" onclick="quickAddFromSelect(' + pid + ',' + line + ')" style="white-space:nowrap">＋ Add to this line</button>';
        html += '</div></div>';
    }

    if (data.antagonists && data.antagonists.length) {
        html += '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#b91c1c;margin-bottom:4px;margin-top:8px">⚠ Conflicts in this bed</div>';
        html += '<ul class="bed-companion-list">';
        data.antagonists.forEach(function(c){
            html += '<li class="bed-companion-item bed-antagonist-item">🚫 <strong>' + esc(c.name) + '</strong> <span>— ' + esc(c.reason) + '</span></li>';
        });
        html += '</ul>';
    }
    var el = document.getElementById('companionsResult'+pid);
    el.innerHTML = html;
    el.style.display = 'block';
}

// Add selected seed from dropdown to the SAME line
function quickAddFromSelect(pid, line) {
    var sel = document.getElementById('companionSelect' + pid);
    if (!sel || !sel.value) return;
    var opt = sel.options[sel.selectedIndex];
    var seedId = parseInt(sel.value);
    var name = opt.getAttribute('data-name') || opt.text;
    var variety = opt.getAttribute('data-variety') || '';
    var btn = document.getElementById('companionAddSelBtn' + pid);
    if (btn) btn.disabled = true;
    var fd = new FormData();
    fd.append('_token', BED_CSRF);
    fd.append('_ajax', '1');
    fd.append('line_number', line);
    fd.append('crop_name', name);
    fd.append('variety', variety);
    fd.append('seed_id', seedId);
    fd.append('plant_count', '1');
    fd.append('status', 'planned');
    fetch(BED_BASE + 'items/' + BED_ITEM + '/planting', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) location.reload();
        else { alert(d.error || 'Could not add.'); if (btn) btn.disabled = false; }
    }).catch(function(){ if (btn) btn.disabled = false; });
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
