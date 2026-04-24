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
.bed-line-row { background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-lg);margin-bottom:6px;overflow:hidden; }
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
.bed-companion-item { font-size:.82rem;display:flex;gap:6px;align-items:flex-start; }
.bed-companion-item strong { flex-shrink:0; }
.bed-companion-item span { color:var(--color-text-muted); }
.bed-antagonist-item { color:#b91c1c; }
.bed-companions-tip { font-size:.78rem;font-style:italic;color:var(--color-text-muted);border-top:1px solid var(--color-border);padding-top:8px;margin-top:4px; }

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
            <?php if ($cropLabel): ?>
            <text x="<?= $x + $w/2 ?>" y="<?= $svgH/2 + 4 ?>" text-anchor="middle" font-size="<?= min(11, max(8, $w/strlen($cropLabel)*1.4)) ?>" fill="#1e293b" font-family="sans-serif" font-weight="600"><?= e(mb_substr($cropLabel, 0, 10)) ?></text>
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

<?php for ($li = 1; $li <= $numLines; $li++):
    $planting = $plantingMap[$li] ?? null;
    $status   = $planting['status'] ?? 'empty';
    $cropName = $planting['crop_name'] ?? '';
    $variety  = $planting['variety']   ?? '';
    $pid      = $planting['id']        ?? 0;
    $plantedAt = $planting['planted_at'] ?? '';
    $harvestAt = $planting['expected_harvest_at'] ?? '';
    $notes     = $planting['notes'] ?? '';
    $subParts  = [];
    if ($plantedAt)  $subParts[] = 'Planted ' . date('d M Y', strtotime($plantedAt));
    if ($harvestAt)  $subParts[] = 'Harvest by ' . date('d M Y', strtotime($harvestAt));
    if ($variety)    $subParts[] = $variety;
?>
<div class="bed-line-row" id="lineRow<?= $li ?>">
    <div class="bed-line-main">
        <div class="bed-line-num"><?= $li ?></div>
        <div class="bed-line-crop">
            <div class="bed-line-name"><?= $cropName ? e($cropName) : '<span style="color:var(--color-text-muted);font-weight:400">Empty</span>' ?></div>
            <?php if ($subParts): ?><div class="bed-line-sub"><?= e(implode(' · ', $subParts)) ?></div><?php endif; ?>
        </div>
        <span class="bed-status-badge bed-status-badge--<?= $status ?>"><?= $statusLabels[$status] ?></span>
        <div class="bed-line-btns">
            <?php if ($hasCompanionApi && $cropName): ?>
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
        <div class="bed-edit-grid">
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Crop</label>
                <input type="text" class="bed-edit-input" id="editCrop<?= $li ?>" value="<?= e($cropName) ?>" placeholder="e.g. Tomatoes">
            </div>
            <div>
                <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:3px">Variety</label>
                <input type="text" class="bed-edit-input" id="editVariety<?= $li ?>" value="<?= e($variety) ?>" placeholder="optional">
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
                <input type="date" class="bed-edit-input" id="editPlanted<?= $li ?>" value="<?= e($plantedAt) ?>">
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

</div>

<script>
var BED_CSRF   = '<?= e($csrfToken) ?>';
var BED_ITEM   = <?= (int)$item['id'] ?>;
var BED_BASE   = '<?= url('/') ?>';
var BED_MONTH  = <?= $currentMonth ?>;
var _companionCache = {};

function toggleEdit(line) {
    var f = document.getElementById('editForm' + line);
    var isOpen = f.classList.contains('open');
    // Close all other edit forms
    document.querySelectorAll('.bed-edit-form.open').forEach(function(el){ el.classList.remove('open'); });
    if (!isOpen) f.classList.add('open');
}

function saveLine(line) {
    var crop    = document.getElementById('editCrop'+line).value.trim();
    var variety = document.getElementById('editVariety'+line).value.trim();
    var status  = document.getElementById('editStatus'+line).value;
    var planted = document.getElementById('editPlanted'+line).value;
    var harvest = document.getElementById('editHarvest'+line).value;
    var notes   = document.getElementById('editNotes'+line).value.trim();

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
    fetch(BED_BASE + 'api/garden/companions?crop=' + encodeURIComponent(crop) + '&month=' + BED_MONTH)
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
    var html = '';
    if (data.companions && data.companions.length) {
        html += '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#15803d;margin-bottom:4px">Good companions</div>';
        html += '<ul class="bed-companion-list">';
        data.companions.forEach(function(c){
            html += '<li class="bed-companion-item">✅ <strong>' + esc(c.name) + '</strong> <span>— ' + esc(c.reason) + '</span></li>';
        });
        html += '</ul>';
    }
    if (data.antagonists && data.antagonists.length) {
        html += '<div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#b91c1c;margin-bottom:4px;margin-top:8px">Avoid nearby</div>';
        html += '<ul class="bed-companion-list">';
        data.antagonists.forEach(function(c){
            html += '<li class="bed-companion-item bed-antagonist-item">🚫 <strong>' + esc(c.name) + '</strong> <span>— ' + esc(c.reason) + '</span></li>';
        });
        html += '</ul>';
    }
    if (data.tip) {
        html += '<div class="bed-companions-tip">💡 ' + esc(data.tip) + '</div>';
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
